<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */final class RoutePluginManagerFactory implements FactoryInterface
{
    /**
     * Create and return a route plugin manager.
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RoutePluginManager {
        // If this is in a laminas-mvc application, the ServiceListener will inject
        // merged configuration during bootstrap.
        if ($container->has('ServiceListener')) {
            return new RoutePluginManager($container);
        }

        // If we do not have a config service, nothing more to do
        if (! $container->has('config')) {
            return new RoutePluginManager($container, $options ?? []);
        }

        $config = $container->get('config');

        // If we do not have router configuration, nothing more to do
        if (! isset($config['route_types']) || ! is_array($config['route_types'])) {
            return new RoutePluginManager($container, $options ?? []);
        }

        /** @psalm-var ServiceManagerConfiguration $config['route_types'] */
        return new RoutePluginManager($container, $config['route_types']);
    }
}
