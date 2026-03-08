<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\PriorityList;
use Laminas\Router\ReturnOfAssemble;
use Laminas\Router\RouteMatch;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

use function array_key_exists;
use function array_merge;
use function assert;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Simple route stack implementation.
 *
 * @template TRoute of RouteInterface
 * @template-implements RouteStackInterface<TRoute>
 */
class SimpleRouteStack implements RouteStackInterface
{
    /**
     * Stack containing all routes.
     *
     * @var PriorityList<non-empty-string|array-key, TRoute>
     */
    protected PriorityList $routes;

    /**
     * @param array<non-empty-string, array|TRoute> $routes
     * @param array<non-empty-string, non-empty-string> $defaultParams
     */
    public function __construct(
        private readonly RoutePluginManager $routePluginManager,
        private readonly UriFactoryInterface $uriFactory,
        array $routes = [],
        /**
         * Default parameters.
         */
        protected array $defaultParams = []
    ) {
        /** @var PriorityList<non-empty-string|array-key, TRoute> $priorityList */
        $priorityList = new PriorityList();
        $this->routes = $priorityList;
        $this->addRoutes($routes);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        /** @psalm-var array<non-empty-string, array|TRoute>  $routes */
        $routes       = $options['routes'] ?? [];
        $routePlugins = $options['route_plugins'] ?? null;
        /** @psalm-var array<non-empty-string, non-empty-string> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];
        $uriFactory    = $options['uri_factory'] ?? null;

        if (! $routePlugins instanceof RoutePluginManager) {
            throw new RuntimeException('Missing "route_plugins" in options array');
        }

        if (! $uriFactory instanceof UriFactoryInterface) {
            throw new RuntimeException('Missing "uri_factory" in options array');
        }

        return new static(
            $routePlugins,
            $uriFactory,
            $routes,
            $defaultParams
        );
    }

    /** @inheritDoc */
    #[Override]
    public function addRoutes(array $routes): void
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function addRoute(string|int $name, array|RouteInterface $route, ?int $priority = null): void
    {
        if (is_array($route)) {
            $route = $this->routeFromArray($route);
        }

        if ($priority === null && isset($route->priority) && is_int($route->priority)) {
            $priority = $route->priority;
        }

        $priority ??= 0;

        $this->routes->insert($name, $route, $priority);
    }

    /** @inheritDoc */
    #[Override]
    public function removeRoute(string $name): void
    {
        $this->routes->remove($name);
    }

    /** @inheritDoc */
    #[Override]
    public function setRoutes(array $routes): void
    {
        $this->routes->clear();
        $this->addRoutes($routes);
    }

    /**
     * Get the added routes
     */
    public function getRoutes(): PriorityList
    {
        return $this->routes;
    }

    /**
     * Check if a route with a specific name exists
     *
     * @param non-empty-string $name
     */
    public function hasRoute(string $name): bool
    {
        return $this->routes->get($name) !== null;
    }

    /**
     * Get a route by name
     *
     * @param non-empty-string $name
     * @return TRoute|null the route
     */
    public function getRoute(string $name): RouteInterface|null
    {
        return $this->routes->get($name);
    }

    /**
     * Set a default parameter.
     *
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    public function setDefaultParam(string $name, string $value): void
    {
        $this->defaultParams[$name] = $value;
    }

    /**
     * Create a route from array specifications.
     *
     * @return TRoute
     * @throws Exception\InvalidArgumentException
     */
    protected function routeFromArray(array $specs): RouteInterface
    {
        $type = $specs['type'] ?? null;
        /** @var array<string, string> $option */
        $option = $specs['options'] ?? [];

        if (! is_string($type) || $type === '') {
            throw new Exception\InvalidArgumentException('Missing "type" option');
        }

        if (! array_key_exists('uri_factory', $option)) {
            $option['uri_factory'] = $this->uriFactory;
        }

        $route = $this->routePluginManager->build($type, $option);

        /** @psalm-var TRoute $route */

        if (isset($specs['priority'])) {
            $route->priority = $specs['priority'];
        }

        return $route;
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request): ?RouteMatch
    {
        foreach ($this->routes as $name => $route) {
            assert($name !== "");
            assert($route instanceof RouteInterface);
            $match = $route->match($request);
            if ($match instanceof RouteMatch) {
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
     * @throws RuntimeException
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        $name = $options['name'] ?? null;
        if (! is_string($name) || $name === '') {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $route = $this->routes->get($name);

        if (! $route instanceof RouteInterface) {
            throw new RuntimeException(sprintf('Route with name "%s" not found', $name));
        }

        unset($options['name']);

        return $route->assemble(array_merge($this->defaultParams, $params), $options);
    }
}
