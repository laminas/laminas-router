<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\AbstractRouteStack;
use Laminas\Router\Container\HttpRouteContainer;
use Laminas\Router\Container\HttpRouteContainerInterface;
use Laminas\Router\Container\RouteContainerInterface;
use Laminas\Router\Exception;
use Laminas\Router\RouteInterface;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Uri\Http as HttpUri;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Traversable;

use function array_merge;
use function explode;
use function is_array;
use function is_iterable;
use function is_string;
use function method_exists;
use function rtrim;
use function sprintf;
use function strlen;

/**
 * Tree search implementation.
 *
 * @extends AbstractRouteStack<HttpRouteContainerInterface>
 */
class TreeRouteStack extends AbstractRouteStack
{
    /**
     * Base URL.
     */
    protected ?string $baseUrl = null;

    /**
     * Request URI.
     */
    protected ?HttpUri $requestUri = null;

    /**
     * Prototype routes.
     * We use an ArrayObject in this case so we can easily pass it down the tree
     * by reference.
     *
     * @var ArrayObject<string, HttpRouteInterface>
     */
    protected ArrayObject $prototypes;

    /** @var HttpRouteContainerInterface */
    protected RouteContainerInterface $routes;

    public function __construct(
        ?RoutePluginManager $routePluginManager = null,
        ?HttpRouteContainerInterface $routes = null
    ) {
        $this->routePluginManager = $routePluginManager ?? new RoutePluginManager(new ServiceManager());
        $this->routes             = $routes ?? new HttpRouteContainer();
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes       = new ArrayObject();
        $this->prototypes = $prototypes;
        $this->init();
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        if (! is_array($options)) {
            $options = self::iteratorToArray($options);
        }

        $routePluginManager = $options['route_plugins'] ?? null;
        if ($routePluginManager !== null && ! $routePluginManager instanceof RoutePluginManager) {
            throw new Exception\InvalidArgumentException('route_plugins must be an instance of RoutePluginManager');
        }

        $instance = new static($routePluginManager);

        if (is_iterable($options['routes'] ?? null)) {
            $instance->addRoutes($options['routes']);
        }

        if (is_array($options['default_params'] ?? null)) {
            $defaultParams = (array) $options['default_params'];
            $instance->setDefaultParams($defaultParams);
        }

        if (isset($options['prototypes']) && method_exists($instance, 'addPrototypes')) {
            $instance->addPrototypes($options['prototypes']);
        }

        return $instance;
    }

    /**
     * addRoute(): defined by RouteStackInterface interface.
     *
     * @throws ContainerExceptionInterface
     */
    public function addRoute(
        string $name,
        string|iterable|RouteInterface $route,
        ?int $priority = null
    ): RouteStackInterface {
        if (! $route instanceof HttpRouteInterface) {
            $route = $this->routeFromSpec($route);
        }

        $this->routes->insert($name, $route, $priority ?? $route->getPriority());

        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    protected function routeFromSpec(string|iterable $specs): HttpRouteInterface
    {
        if (is_string($specs)) {
            return $this->getPrototype($specs)
                ?? throw new Exception\RuntimeException(sprintf('Could not find prototype with name %s', $specs));
        }

        if ($specs instanceof Traversable) {
            $specs = self::iteratorToArray($specs);
        }

        if (isset($specs['chain_routes'])) {
            if (! is_array($specs['chain_routes'])) {
                throw new Exception\InvalidArgumentException('Chain routes must be an array or Traversable object');
            }

            $chainRoutes = array_merge([$specs], $specs['chain_routes']);
            unset($chainRoutes[0]['chain_routes']);

            if (isset($specs['child_routes'])) {
                unset($chainRoutes[0]['child_routes']);
            }

            $options = [
                'routes'        => $chainRoutes,
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $route = $this->routePluginManager->build('chain', $options);
        } else {
            $route = $this->routeFromIterable($specs);
        }

        if (isset($specs['child_routes'])) {
            $options = [
                'route'         => $route,
                'may_terminate' => isset($specs['may_terminate']) && $specs['may_terminate'],
                'child_routes'  => $specs['child_routes'],
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $priority = $route->getPriority();

            $route = $this->routePluginManager->build('part', $options);
            $route->setPriority($priority);
        }

        return $route;
    }

    /**
     * Add multiple prototypes at once.
     *
     * @param iterable<array-key, HttpRouteInterface> $routes
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public function addPrototypes(iterable $routes): RouteStackInterface
    {
        foreach ($routes as $name => $route) {
            $this->addPrototype($name, $route);
        }

        return $this;
    }

    /**
     * Add a prototype.
     *
     * @throws ContainerExceptionInterface
     */
    public function addPrototype(string $name, iterable|HttpRouteInterface|string $route): static
    {
        if (! $route instanceof HttpRouteInterface) {
            $route = $this->routeFromSpec($route);
        }

        $this->prototypes[$name] = $route;

        return $this;
    }

    /**
     * Get a prototype.
     */
    public function getPrototype(string $name): ?HttpRouteInterface
    {
        return $this->prototypes[$name] ?? null;
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
            $this->setBaseUrl($request->getBaseUrl());
        }

        $uri           = $request->getUri();
        $baseUrlLength = strlen($this->getBaseUrl()) ?: null;

        if ($pathOffset !== null) {
            $baseUrlLength = ($baseUrlLength ?? 0) + $pathOffset;
        }

        if ($this->requestUri === null) {
            $this->setRequestUri($uri);
        }

        if ($baseUrlLength !== null) {
            $pathLength = strlen($uri->getPath()) - $baseUrlLength;
        } else {
            $pathLength = null;
        }

        foreach ($this->routes as $name => $route) {
            if (
                ($match = $route->match($request, $baseUrlLength, $options)) instanceof RouteMatch
                && ($pathLength === null || $match->getLength() === $pathLength)
            ) {
                $match->setMatchedRouteName((string) $name);

                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam((string) $paramName) === null) {
                        $match->setParam((string) $paramName, $value);
                    }
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        if (! isset($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);

        if ($route === null) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $names[0]));
        }

        if (isset($names[1])) {
            if (! $route instanceof TreeRouteStack) {
                throw new Exception\RuntimeException(sprintf(
                    'Route with name "%s" does not have child routes',
                    $names[0]
                ));
            }
            $options['name'] = $names[1];
        } else {
            unset($options['name']);
        }

        if (isset($options['only_return_path']) && $options['only_return_path']) {
            return $this->getBaseUrl() . $route->assemble(array_merge($this->defaultParams, $params), $options);
        }

        if (! isset($options['uri']) || ! $options['uri'] instanceof HttpUri) {
            $uri = new HttpUri();

            if (isset($options['force_canonical']) && $options['force_canonical']) {
                if ($this->requestUri === null) {
                    throw new Exception\RuntimeException('Request URI has not been set');
                }

                $uri->setScheme($this->requestUri->getScheme())
                    ->setHost($this->requestUri->getHost())
                    ->setPort($this->requestUri->getPort());
            }

            $options['uri'] = $uri;
        } else {
            $uri = $options['uri'];
        }

        $path = $this->getBaseUrl() . $route->assemble(array_merge($this->defaultParams, $params), $options);

        if (isset($options['query'])) {
            $uri->setQuery($options['query']);
        }

        if (isset($options['fragment'])) {
            $uri->setFragment($options['fragment']);
        }

        if (
            (isset($options['force_canonical'])
                && $options['force_canonical'])
            || $uri->getHost() !== null
            || $uri->getScheme() !== null
        ) {
            if (($uri->getHost() === null || $uri->getScheme() === null) && $this->requestUri === null) {
                throw new Exception\RuntimeException('Request URI has not been set');
            }

            if ($uri->getHost() === null) {
                $uri->setHost($this->requestUri->getHost());
            }

            if ($uri->getScheme() === null) {
                $uri->setScheme($this->requestUri->getScheme());
            }

            $uri->setPath($path);

            if (! isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        } elseif (! $uri->isAbsolute() && $uri->isValidRelative()) {
            $uri->setPath($path);

            if (! isset($options['normalize_path']) || $options['normalize_path']) {
                $uri->normalize();
            }

            return $uri->toString();
        }

        return $path;
    }

    /**
     * Set the base URL.
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    /**
     * Get the base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? '';
    }

    /**
     * Set the request URI.
     *
     * Accepts both Laminas\Uri\Http and PSR-7 UriInterface.
     * PSR-7 URIs are converted to Laminas\Uri\Http internally.
     */
    public function setRequestUri(HttpUri|UriInterface $uri): static
    {
        if (! $uri instanceof HttpUri) {
            $uri = new HttpUri((string) $uri);
        }

        $this->requestUri = $uri;

        return $this;
    }

    /**
     * Get the request URI.
     */
    public function getRequestUri(): ?HttpUri
    {
        return $this->requestUri;
    }
}
