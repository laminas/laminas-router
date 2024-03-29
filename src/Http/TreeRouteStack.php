<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\RouteInvokableFactory;
use Laminas\Router\SimpleRouteStack;
use Laminas\ServiceManager\Config;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Uri\Http as HttpUri;
use Traversable;

use function array_merge;
use function explode;
use function is_array;
use function is_string;
use function method_exists;
use function rtrim;
use function sprintf;
use function strlen;

/**
 * Tree search implementation.
 *
 * @template TRoute of RouteInterface
 * @template-extends SimpleRouteStack<TRoute>
 */
class TreeRouteStack extends SimpleRouteStack
{
    /**
     * Base URL.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Request URI.
     *
     * @var HttpUri
     */
    protected $requestUri;

    /**
     * Prototype routes.
     *
     * We use an ArrayObject in this case so we can easily pass it down the tree
     * by reference.
     *
     * @var ArrayObject<string, TRoute>
     */
    protected $prototypes;

    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     *
     * @var int|null
     */
    public $priority;

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::factory()
     *
     * @param  iterable $options
     * @return SimpleRouteStack
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($options = [])
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable set of options',
                __METHOD__
            ));
        }

        $instance = parent::factory($options);

        if (isset($options['prototypes'])) {
            $instance->addPrototypes($options['prototypes']);
        }

        return $instance;
    }

    /**
     * init(): defined by SimpleRouteStack.
     *
     * @see    SimpleRouteStack::init()
     */
    protected function init()
    {
        /** @var ArrayObject<string, TRoute> $this->prototypes */
        $this->prototypes = new ArrayObject();

        (new Config([
            'aliases'   => [
                'chain'    => Chain::class,
                'Chain'    => Chain::class,
                'hostname' => Hostname::class,
                'Hostname' => Hostname::class,
                'hostName' => Hostname::class,
                'HostName' => Hostname::class,
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'method'   => Method::class,
                'Method'   => Method::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'regex'    => Regex::class,
                'Regex'    => Regex::class,
                'scheme'   => Scheme::class,
                'Scheme'   => Scheme::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
                'wildcard' => Wildcard::class,
                'Wildcard' => Wildcard::class,
                'wildCard' => Wildcard::class,
                'WildCard' => Wildcard::class,
            ],
            'factories' => [
                Chain::class    => RouteInvokableFactory::class,
                Hostname::class => RouteInvokableFactory::class,
                Literal::class  => RouteInvokableFactory::class,
                Method::class   => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Regex::class    => RouteInvokableFactory::class,
                Scheme::class   => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
                Wildcard::class => RouteInvokableFactory::class,

                // v2 normalized names
                'laminasmvcrouterhttpchain'    => RouteInvokableFactory::class,
                'laminasmvcrouterhttphostname' => RouteInvokableFactory::class,
                'laminasmvcrouterhttpliteral'  => RouteInvokableFactory::class,
                'laminasmvcrouterhttpmethod'   => RouteInvokableFactory::class,
                'laminasmvcrouterhttppart'     => RouteInvokableFactory::class,
                'laminasmvcrouterhttpregex'    => RouteInvokableFactory::class,
                'laminasmvcrouterhttpscheme'   => RouteInvokableFactory::class,
                'laminasmvcrouterhttpsegment'  => RouteInvokableFactory::class,
                'laminasmvcrouterhttpwildcard' => RouteInvokableFactory::class,
            ],
        ]))->configureServiceManager($this->routePluginManager);
    }

    /**
     * addRoute(): defined by RouteStackInterface interface.
     *
     * @param string                 $name
     * @param string|iterable|TRoute $route
     * @param int                    $priority
     * @return $this
     */
    public function addRoute($name, $route, $priority = null)
    {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromArray($route);
        }

        return parent::addRoute($name, $route, $priority);
    }

    /**
     * @inheritDoc
     * @param  string|iterable $specs
     * @return TRoute
     * @throws Exception\InvalidArgumentException When route definition is not an array nor traversable.
     * @throws Exception\InvalidArgumentException When chain routes are not an array nor traversable.
     * @throws Exception\RuntimeException         When a generated routes does not implement the HTTP route interface.
     */
    protected function routeFromArray($specs)
    {
        if (is_string($specs)) {
            if (null === ($route = $this->getPrototype($specs))) {
                throw new Exception\RuntimeException(sprintf('Could not find prototype with name %s', $specs));
            }

            return $route;
        } elseif ($specs instanceof Traversable) {
            $specs = ArrayUtils::iteratorToArray($specs);
        } elseif (! is_array($specs)) {
            throw new Exception\InvalidArgumentException('Route definition must be an array or Traversable object');
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

            $route = $this->routePluginManager->get('chain', $options);
        } else {
            $route = parent::routeFromArray($specs);
        }

        if (! $route instanceof RouteInterface) {
            throw new Exception\RuntimeException('Given route does not implement HTTP route interface');
        }

        if (isset($specs['child_routes'])) {
            $options = [
                'route'         => $route,
                'may_terminate' => isset($specs['may_terminate']) && $specs['may_terminate'],
                'child_routes'  => $specs['child_routes'],
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $priority = $route->priority ?? null;

            $route           = $this->routePluginManager->get('part', $options);
            $route->priority = $priority;
        }

        return $route;
    }

    /**
     * Add multiple prototypes at once.
     *
     * @param iterable<string|iterable|TRoute> $routes
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function addPrototypes($routes)
    {
        if (! is_array($routes) && ! $routes instanceof Traversable) {
            throw new Exception\InvalidArgumentException('addPrototypes expects an array or Traversable set of routes');
        }

        foreach ($routes as $name => $route) {
            $this->addPrototype($name, $route);
        }

        return $this;
    }

    /**
     * Add a prototype.
     *
     * @param string                 $name
     * @param string|iterable|TRoute $route
     * @return $this
     */
    public function addPrototype($name, $route)
    {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromArray($route);
        }

        $this->prototypes[$name] = $route;

        return $this;
    }

    /**
     * Get a prototype.
     *
     * @param  string $name
     * @return TRoute|null
     */
    public function getPrototype($name)
    {
        if (isset($this->prototypes[$name])) {
            return $this->prototypes[$name];
        }

        return null;
    }

    /**
     * match(): defined by \Laminas\Router\RouteInterface
     *
     * @see    \Laminas\Router\RouteInterface::match()
     *
     * @param  int|null $pathOffset
     * @param  array $options
     * @return RouteMatch|null
     */
    public function match(Request $request, $pathOffset = null, array $options = [])
    {
        if (! method_exists($request, 'getUri')) {
            return null;
        }

        if ($this->baseUrl === null && method_exists($request, 'getBaseUrl')) {
            $this->setBaseUrl($request->getBaseUrl());
        }

        $uri           = $request->getUri();
        $baseUrlLength = strlen((string) $this->baseUrl) ?: null;

        if ($pathOffset !== null) {
            $baseUrlLength += $pathOffset;
        }

        if ($this->requestUri === null) {
            $this->setRequestUri($uri);
        }

        if ($baseUrlLength !== null) {
            $pathLength = strlen((string) $uri->getPath()) - $baseUrlLength;
        } else {
            $pathLength = null;
        }

        foreach ($this->routes as $name => $route) {
            if (
                ($match = $route->match($request, $baseUrlLength, $options)) instanceof RouteMatch
                && ($pathLength === null || $match->getLength() === $pathLength)
            ) {
                $match->setMatchedRouteName($name);

                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam($paramName) === null) {
                        $match->setParam($paramName, $value);
                    }
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * assemble(): defined by \Laminas\Router\RouteInterface interface.
     *
     * @see    \Laminas\Router\RouteInterface::assemble()
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = [])
    {
        if (! isset($options['name'])) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $options['name'], 2);
        $route = $this->routes->get($names[0]);

        if (! $route) {
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
            return $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);
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

        $path = $this->baseUrl . $route->assemble(array_merge($this->defaultParams, $params), $options);

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
     *
     * @param  string $baseUrl
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Get the base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set the request URI.
     *
     * @return TreeRouteStack
     */
    public function setRequestUri(HttpUri $uri)
    {
        $this->requestUri = $uri;
        return $this;
    }

    /**
     * Get the request URI.
     *
     * @return HttpUri
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }
}
