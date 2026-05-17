<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\SimpleRouteStack;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use function array_merge;
use function assert;
use function explode;
use function is_array;
use function is_string;
use function property_exists;
use function sprintf;
use function strlen;

/**
 * Tree search implementation.
 *
 * @template TRoute of HttpRouteInterface
 * @template-extends SimpleRouteStack<TRoute>
 * @psalm-consistent-constructor
 */
class TreeRouteStack extends SimpleRouteStack
{
    /**
     * Request URI.
     */
    private UriInterface|null $requestUri = null;

    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * @param ArrayObject<string, TRoute> $prototypes
     * @param array<non-empty-string, array|TRoute> $routes
     * @param array<non-empty-string, non-empty-string> $defaultParams
     */
    public function __construct(
        private readonly RoutePluginManager $routePluginManager,
        /**
         * Prototype routes.
         *
         * We use an ArrayObject in this case so we can easily pass it down the tree
         * by reference.
         */
        private readonly ArrayObject $prototypes,
        array $routes = [],
        array $defaultParams = [],
    ) {
        parent::__construct($this->routePluginManager, $routes, $defaultParams);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        /** @psalm-var array<non-empty-string, array|TRoute>  $routes */
        $routes = $options['routes'] ?? [];
        /** @var ArrayObject<string, TRoute> $prototypes */
        $prototypes   = $options['prototypes'] ?? new ArrayObject();
        $routePlugins = $options['route_plugins'] ?? null;
        /** @psalm-var array<non-empty-string, non-empty-string> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];

        if (! $routePlugins instanceof RoutePluginManager) {
            throw new RuntimeException('Missing "route_plugins" in options array');
        }

        return new static(
            $routePlugins,
            $prototypes,
            $routes,
            $defaultParams,
        );
    }

    /** @inheritDoc */
    #[Override]
    public function addRoute(string|int $name, int|string|array|RouteInterface $route, ?int $priority = null): void
    {
        if (! $route instanceof HttpRouteInterface && $route instanceof RouteInterface) {
            throw new Exception\InvalidArgumentException(
                'Only HttpRouteInterface instances or array/string specifications are allowed.'
            );
        }
        if (! $route instanceof HttpRouteInterface) {
            $route = $this->routeFromArray($route);
        }

        parent::addRoute($name, $route, $priority);
    }

    /**
     * @inheritDoc
     * @param  string|array $specs
     * @return TRoute
     * @throws Exception\InvalidArgumentException When route definition is not an array nor traversable.
     * @throws Exception\InvalidArgumentException When chain routes are not an array nor traversable.
     * @throws Exception\RuntimeException         When a generated routes does not implement the HTTP route interface.
     */
    #[Override]
    final protected function routeFromArray(string|array $specs): RouteInterface
    {
        if (is_string($specs)) {
            return $this->getPrototype($specs);
        }

        if (isset($specs['chain_routes'])) {
            if (! is_array($specs['chain_routes'])) {
                throw new Exception\InvalidArgumentException('Chain routes must be an array');
            }

            $chainRoutes = array_merge([$specs], $specs['chain_routes']);
            if (isset($chainRoutes[0]['chain_routes'])) {
                unset($chainRoutes[0]['chain_routes']);
            }

            if (isset($specs['child_routes']) && isset($chainRoutes[0]['child_routes'])) {
                unset($chainRoutes[0]['child_routes']);
            }

            $options = [
                'routes'        => $chainRoutes,
                'route_plugins' => $this->routePluginManager,
                'prototypes'    => $this->prototypes,
            ];

            $route = $this->routePluginManager->build(Chain::class, $options);
        } else {
            $route = parent::routeFromArray($specs);
        }

        if (! $route instanceof HttpRouteInterface) {
            throw new Exception\RuntimeException('Given route does not implement HTTP route interface');
        }

        if (isset($specs['child_routes'])) {
            $options = [
                'route'          => $route,
                'may_terminate'  => isset($specs['may_terminate']) && $specs['may_terminate'] === true,
                'child_routes'   => $specs['child_routes'],
                'default_params' => $specs['default_params'] ?? [],
                'route_plugins'  => $this->routePluginManager,
                'prototypes'     => $this->prototypes,
            ];

            $priority = $route->priority ?? null;

            $route           = $this->routePluginManager->build(Part::class, $options);
            $route->priority = $priority;
        }

        return $route;
    }

    /**
     * Get a prototype.
     *
     * @return TRoute
     */
    private function getPrototype(string $name): RouteInterface
    {
        if (! property_exists($this->prototypes, $name)) {
            throw new Exception\RuntimeException(sprintf('Could not find prototype with name %s', $name));
        }
        return $this->prototypes[$name];
    }

    /**
     * @inheritDoc
     * @param int|null $pathOffset
     */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        $baseUrlLength = null;

        if ($pathOffset !== null) {
            $baseUrlLength = $baseUrlLength !== null ? $baseUrlLength + $pathOffset : $pathOffset;
        }

        if ($this->requestUri === null) {
            $this->setRequestUri($request->getUri());
        }

        $pathLength = null;
        if ($baseUrlLength !== null) {
            $pathLength = strlen($request->getUri()->getPath()) - $baseUrlLength;
        }

        foreach ($this->routes as $name => $route) {
            assert($route instanceof HttpRouteInterface);
            $match = $route->match($request, $baseUrlLength, $options);
            if ($match instanceof HttpRouteMatch && ($pathLength === null || $match->getLength() === $pathLength)) {
                $match->setMatchedRouteName((string) $name);

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
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        $name = $options['name'] ?? '';
        if (! is_string($name) || $name === '') {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $name, 2);

        if ($names[0] === '') {
            throw new Exception\RuntimeException('Invalid route name');
        }

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

        if (isset($options['only_return_path']) && $options['only_return_path'] === true) {
            return $route->assemble(array_merge($this->defaultParams, $params), $options);
        }

        $assembledUrl = $route->assemble(array_merge($this->defaultParams, $params), $options);

        $forceCanonicalOption = isset($options['force_canonical']) && $options['force_canonical'] === true;

        $contextUri  = isset($options['uri']) && $options['uri'] instanceof UriInterface
            ? $options['uri']
            : null;
        $fallbackUri = $contextUri ?? $this->requestUri;

        if ($forceCanonicalOption && $fallbackUri === null) {
            throw new RuntimeException('Request URI has not been set');
        }

        $childScheme = $assembledUrl->scheme;
        $childHost   = $assembledUrl->host;
        $childPort   = $assembledUrl->port;

        $resolvedScheme = $childScheme;
        if ($resolvedScheme === null || $resolvedScheme === '') {
            $fbScheme       = $fallbackUri?->getScheme();
            $resolvedScheme = $fbScheme !== null && $fbScheme !== '' ? $fbScheme : null;
        }

        $resolvedHost = $childHost;
        if ($resolvedHost === null || $resolvedHost === '') {
            $fbHost       = $fallbackUri?->getHost();
            $resolvedHost = $fbHost !== null && $fbHost !== '' ? $fbHost : null;
        }

        $resolvedPort = $childPort ?? $fallbackUri?->getPort();

        if (
            $childHost !== null
            && $childHost !== ''
            && ($resolvedScheme === null || $resolvedScheme === '')
        ) {
            throw new RuntimeException('Request URI has not been set');
        }

        $childSchemeNonEmpty = is_string($childScheme) && $childScheme !== '';

        $mergedForceCanonical = $forceCanonicalOption
            || ($childHost !== null && $childHost !== '')
            || $childSchemeNonEmpty;

        return new AssembledUrl(
            path: $assembledUrl->path,
            query: $options['query'] ?? $assembledUrl->query,
            host: $resolvedHost,
            scheme: $resolvedScheme,
            fragment: $options['fragment'] ?? $assembledUrl->fragment,
            forceCanonical: $mergedForceCanonical,
            port: $resolvedPort,
        );
    }

    /**
     * Set the request URI.
     */
    public function setRequestUri(UriInterface $uri): void
    {
        $this->requestUri = $uri;
    }

    /**
     * Get the request URI.
     */
    public function getRequestUri(): ?UriInterface
    {
        return $this->requestUri;
    }
}
