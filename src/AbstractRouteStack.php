<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Container\RouteContainerInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * Abstract route stack implementation.
 *
 * @template TContainer of RouteContainerInterface
 */
abstract class AbstractRouteStack implements RouteStackInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Default parameters.
     *
     * @var array<array-key, mixed>
     */
    protected array $defaultParams = [];

    protected RoutePluginManager $routePluginManager;

    /** @var TContainer */
    protected RouteContainerInterface $routes;

    /**
     * Init method for extending classes.
     */
    protected function init(): void
    {
    }

    /**
     * @param RoutePluginManager<RouteInterface> $routePlugins
     * @return $this
     */
    public function setRoutePluginManager(RoutePluginManager $routePlugins): static
    {
        $this->routePluginManager = $routePlugins;

        return $this;
    }

    /**
     * Get the route plugin manager.
     */
    public function getRoutePluginManager(): RoutePluginManager
    {
        return $this->routePluginManager;
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function addRoutes(iterable $routes): RouteStackInterface
    {
        foreach ($routes as $name => $route) {
            $this->addRoute((string) $name, $route);
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function addRoute(
        string $name,
        iterable|RouteInterface $route,
        ?int $priority = null
    ): RouteStackInterface {
        if (! $route instanceof RouteInterface) {
            $route = $this->routeFromIterable($route);
        }

        $this->routes->insert($name, $route, $priority ?? $route->getPriority());

        return $this;
    }

    /** @inheritDoc */
    public function removeRoute(string $name): RouteStackInterface
    {
        $this->routes->remove($name);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function setRoutes(iterable $routes): RouteStackInterface
    {
        $this->routes->clear();
        $this->addRoutes($routes);

        return $this;
    }

    /**
     * Get the added routes.
     *
     * @return TContainer
     */
    public function getRoutes(): RouteContainerInterface
    {
        return $this->routes;
    }

    /**
     * Check if a route with a specific name exists.
     */
    public function hasRoute(string $name): bool
    {
        return $this->routes->get($name) !== null;
    }

    /**
     * Get a route by name.
     */
    public function getRoute(string $name): ?RouteInterface
    {
        return $this->routes->get($name);
    }

    /**
     * Set default parameters.
     *
     * @param array<array-key, mixed> $params
     */
    public function setDefaultParams(array $params): static
    {
        $this->defaultParams = $params;

        return $this;
    }

    /**
     * Set a default parameter.
     */
    public function setDefaultParam(string $name, mixed $value): static
    {
        $this->defaultParams[$name] = $value;

        return $this;
    }

    /**
     * Create a route from array specifications.
     *
     * @throws ContainerExceptionInterface
     */
    protected function routeFromIterable(iterable $specs): RouteInterface
    {
        $specs = self::processRouteOptions(
            $specs,
            ['type'],
            ['options' => []],
        );
        $type  = (string) $specs['type'];

        $routePluginManager = $this->getRoutePluginManager();
        /** @psalm-var RouteInterface $route */
        $route = $routePluginManager->build($type, (array) $specs['options']);

        $priority = isset($specs['priority']) ? (int) $specs['priority'] : null;
        $route->setPriority($priority);

        return $route;
    }
}
