<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\ConfigProvider;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use LaminasTest\Router\Http\TestAsset\DummyRoute as HttpDummyRoute;
use LaminasTest\Router\Http\TestAsset\DummyRouteBuilder as HttpDummyRouteBuilder;
use LaminasTest\Router\Http\TestAsset\DummyRouteWithParam as HttpDummyRouteWithParam;
use LaminasTest\Router\Http\TestAsset\DummyRouteWithParamBuilder as HttpDummyRouteWithParamBuilder;
use LaminasTest\Router\TestAsset\DummyRoute;
use LaminasTest\Router\TestAsset\DummyRouteBuilder;
use LaminasTest\Router\TestAsset\DummyRouteWithParam;
use LaminasTest\Router\TestAsset\DummyRouteWithParamBuilder;
use LaminasTest\Router\TestAsset\Router;
use LaminasTest\Router\TestAsset\RouterBuilder;

use function array_merge;
use function assert;
use function is_array;

/**
 * Builds a RouteBuilderContainer (and ServiceManager) with production builders
 * plus test dummy route builders.
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class RouteBuilderContainerTestHelper
{
    /**
     * @param array<string, mixed> $routerConfig Merged into config['router']
     * @param array<string, mixed> $services Extra service-manager services
     * @param array<string, callable|class-string> $extraFactories Extra service-manager factories
     */
    public static function createServiceManager(
        array $routerConfig = [],
        array $services = [],
        array $extraFactories = [],
        ?TranslatorInterface $translator = null,
    ): ServiceManager {
        $provider       = new ConfigProvider();
        $providerConfig = $provider();

        $extraBuilderMap = [];
        if (isset($routerConfig['route_builders']) && is_array($routerConfig['route_builders'])) {
            /** @var array<string, class-string> $extraBuilderMap */
            $extraBuilderMap = $routerConfig['route_builders'];
        }

        $builderMap = array_merge(
            $providerConfig['router']['route_builders'],
            [
                DummyRoute::class              => DummyRouteBuilder::class,
                DummyRouteWithParam::class     => DummyRouteWithParamBuilder::class,
                HttpDummyRoute::class          => HttpDummyRouteBuilder::class,
                HttpDummyRouteWithParam::class => HttpDummyRouteWithParamBuilder::class,
                Router::class                  => RouterBuilder::class,
            ],
            $extraBuilderMap,
        );

        $config                             = $providerConfig;
        $config['router']                   = array_merge($config['router'], $routerConfig);
        $config['router']['route_builders'] = $builderMap;
        if ($translator !== null) {
            $config['router']['translator'] = TranslatorInterface::class;
        }

        $dependencies = $provider->getDependencyConfig();
        assert(isset($dependencies['factories']));

        $dependencies['factories'] = array_merge(
            $dependencies['factories'],
            [
                DummyRouteBuilder::class              => static fn(): DummyRouteBuilder => new DummyRouteBuilder(),
                DummyRouteWithParamBuilder::class     => static fn(): DummyRouteWithParamBuilder
                    => new DummyRouteWithParamBuilder(),
                HttpDummyRouteBuilder::class          => static fn(): HttpDummyRouteBuilder
                    => new HttpDummyRouteBuilder(),
                HttpDummyRouteWithParamBuilder::class => static fn(): HttpDummyRouteWithParamBuilder
                    => new HttpDummyRouteWithParamBuilder(),
                RouterBuilder::class                  => static fn(): RouterBuilder => new RouterBuilder(),
            ],
            $extraFactories,
        );

        $defaultServices = ['config' => $config];
        if ($translator !== null) {
            $defaultServices[TranslatorInterface::class] = $translator;
        }

        $dependencies['services'] = array_merge($defaultServices, $services);

        /** @psalm-var ServiceManagerConfiguration $dependencies */
        return new ServiceManager($dependencies);
    }

    public static function create(): RouteBuilderContainerInterface
    {
        // @mago-ignore analysis:mixed-assignment
        $routeBuilderContainer = self::createServiceManager()->get(RouteBuilderContainerInterface::class);
        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        return $routeBuilderContainer;
    }
}
