<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Http\ChainBuilder;
use Laminas\Router\Http\ChainBuilderFactory;
use Laminas\Router\Http\HostnameBuilder;
use Laminas\Router\Http\HostnameBuilderFactory;
use Laminas\Router\Http\LiteralBuilder;
use Laminas\Router\Http\LiteralBuilderFactory;
use Laminas\Router\Http\MethodBuilder;
use Laminas\Router\Http\MethodBuilderFactory;
use Laminas\Router\Http\PartBuilder;
use Laminas\Router\Http\PartBuilderFactory;
use Laminas\Router\Http\PlaceholderBuilder;
use Laminas\Router\Http\PlaceholderBuilderFactory;
use Laminas\Router\Http\RegexBuilder;
use Laminas\Router\Http\RegexBuilderFactory;
use Laminas\Router\Http\SchemeBuilder;
use Laminas\Router\Http\SchemeBuilderFactory;
use Laminas\Router\Http\SegmentBuilder;
use Laminas\Router\Http\SegmentBuilderFactory;
use Laminas\Router\Http\TranslatorAwareTreeRouteStackBuilder;
use Laminas\Router\Http\TranslatorAwareTreeRouteStackBuilderFactory;
use Laminas\Router\Http\TreeRouteStackBuilder;
use Laminas\Router\Http\TreeRouteStackBuilderFactory;
use Laminas\Router\Http\WildcardBuilder;
use Laminas\Router\Http\WildcardBuilderFactory;
use Laminas\ServiceManager\ConfigInterface;

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
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 * @final
 */
class ConfigProvider
{
    /**
     * Provide default configuration.
     *
     * @return array<string, array>
     */
    public function __invoke()
    {
        return [
            'router'        => [
                'route_builders' => RouteBuilderContainer::defaultBuilderMap(),
            ],
            'dependencies'  => $this->getDependencyConfig(),
            'route_manager' => $this->getRouteManagerConfig(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return ServiceManagerConfigurationType
     */
    public function getDependencyConfig()
    {
        return [
            'aliases'   => [
                'HttpRouter'         => Http\TreeRouteStack::class,
                'router'             => RouteStackInterface::class,
                'Router'             => RouteStackInterface::class,
                'RoutePluginManager' => RoutePluginManager::class,

                // Legacy Zend Framework aliases
                // @deprecated Legacy Zend Framework aliases removed in v4.0
                'Zend\Router\Http\TreeRouteStack' => Http\TreeRouteStack::class,
                'Zend\Router\RoutePluginManager'  => RoutePluginManager::class,
                'Zend\Router\RouteStackInterface' => RouteStackInterface::class,
            ],
            'factories' => [
                Http\TreeRouteStack::class     => Http\HttpRouterFactory::class,
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
                WildcardBuilder::class => WildcardBuilderFactory::class,
            ],
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     *
     * @deprecated Configuration is consolidated in __invoke(); removed in v4.0
     *
     * @return array
     */
    public function getRouteManagerConfig()
    {
        return [];
    }
}
