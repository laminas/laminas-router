<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;

/**
 * Provide base configuration for using the component.
 *
 * Provides base configuration expected in order to:
 *
 * - seed and configure the default routers and route plugin manager.
 * - provide routes to the given routers.
 *
 * @see ConfigInterface
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-type RouterConfigShape = array{
 *      dependencies: ServiceManagerConfiguration,
 *      router: array{
 *          router_class: class-string<RouteStackInterface>,
 *          route_plugins: class-string<RoutePluginManager>,
 *          translator?: class-string<TranslatorInterface>,
 *      }
 *  }
 */
final readonly class ConfigProvider
{
    /**
     * Provide default configuration.
     *
     * @return RouterConfigShape
     */
    public function __invoke(): array
    {
        return [
            'router'       => [
                'router_class'  => TreeRouteStack::class,
                'route_plugins' => RoutePluginManager::class,
            ],
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencyConfig(): array
    {
        return [
            'factories' => [
                TreeRouteStack::class      => Http\HttpRouterFactory::class,
                RoutePluginManager::class  => RoutePluginManagerFactory::class,
                RouteStackInterface::class => RouterFactory::class,
            ],
        ];
    }
}
