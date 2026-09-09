<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Builder\SimpleRouteStackBuilder;
use Laminas\Router\Builder\SimpleRouteStackBuilderFactory;
use Laminas\Router\Http\Builder\ChainBuilder;
use Laminas\Router\Http\Builder\ChainBuilderFactory;
use Laminas\Router\Http\Builder\HostnameBuilder;
use Laminas\Router\Http\Builder\HostnameBuilderFactory;
use Laminas\Router\Http\Builder\LiteralBuilder;
use Laminas\Router\Http\Builder\LiteralBuilderFactory;
use Laminas\Router\Http\Builder\MethodBuilder;
use Laminas\Router\Http\Builder\MethodBuilderFactory;
use Laminas\Router\Http\Builder\PartBuilder;
use Laminas\Router\Http\Builder\PartBuilderFactory;
use Laminas\Router\Http\Builder\PlaceholderBuilder;
use Laminas\Router\Http\Builder\PlaceholderBuilderFactory;
use Laminas\Router\Http\Builder\RegexBuilder;
use Laminas\Router\Http\Builder\RegexBuilderFactory;
use Laminas\Router\Http\Builder\SchemeBuilder;
use Laminas\Router\Http\Builder\SchemeBuilderFactory;
use Laminas\Router\Http\Builder\SegmentBuilder;
use Laminas\Router\Http\Builder\SegmentBuilderFactory;
use Laminas\Router\Http\Builder\TranslatorAwareTreeRouteStackBuilder;
use Laminas\Router\Http\Builder\TranslatorAwareTreeRouteStackBuilderFactory;
use Laminas\Router\Http\Builder\TreeRouteStackBuilder;
use Laminas\Router\Http\Builder\TreeRouteStackBuilderFactory;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ConfigInterface;
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
                'router_class'   => TreeRouteStack::class,
                'route_plugins'  => RoutePluginManager::class,
                'route_builders' => RouteBuilderContainer::defaultBuilderMap(),
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
                TreeRouteStack::class          => Http\HttpRouterFactory::class,
                RoutePluginManager::class      => RoutePluginManagerFactory::class,
                RouteStackInterface::class     => RouterFactory::class,
                RouteBuilderContainer::class   => RouteBuilderContainerFactory::class,
                LiteralBuilder::class          => LiteralBuilderFactory::class,
                SegmentBuilder::class          => SegmentBuilderFactory::class,
                HostnameBuilder::class         => HostnameBuilderFactory::class,
                RegexBuilder::class            => RegexBuilderFactory::class,
                MethodBuilder::class           => MethodBuilderFactory::class,
                SchemeBuilder::class           => SchemeBuilderFactory::class,
                PlaceholderBuilder::class      => PlaceholderBuilderFactory::class,
                PartBuilder::class             => PartBuilderFactory::class,
                ChainBuilder::class            => ChainBuilderFactory::class,
                SimpleRouteStackBuilder::class => SimpleRouteStackBuilderFactory::class,
                TreeRouteStackBuilder::class   => TreeRouteStackBuilderFactory::class,
                TranslatorAwareTreeRouteStackBuilder::class
                => TranslatorAwareTreeRouteStackBuilderFactory::class,
            ],
        ];
    }
}
