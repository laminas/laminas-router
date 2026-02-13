<?php

declare(strict_types=1);

namespace Laminas\Router;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function class_exists;
use function sprintf;

trait RouterConfigTrait
{
    /**
     * Create and return a router instance, by calling the appropriate factory.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    private function createRouter(string $class, array $config, ContainerInterface $container): RouteInterface
    {
        // Obtain the configured router class, if any
        if (isset($config['router_class']) && class_exists($config['router_class'])) {
            $class = $config['router_class'];
        }

        // Inject the route plugins
        if (! isset($config['route_plugins']) && $container->has('RoutePluginManager')) {
            $routePluginManager = $container->get('RoutePluginManager');
            if ($routePluginManager instanceof RoutePluginManager) {
                $config['route_plugins'] = $routePluginManager;
            }
        }

        // Obtain an instance
        $factory = sprintf('%s::factory', $class);
        return $factory($config);
    }
}
