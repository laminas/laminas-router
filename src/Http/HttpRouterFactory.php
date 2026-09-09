<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\ConfigProvider;
use Laminas\Router\RouteBuilderContainer;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteStackInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal LaminasTest\Router
 * @psalm-import-type RouterConfigShape from ConfigProvider
 */
final readonly class HttpRouterFactory
{
    /**
     * Create and return the HTTP router
     *
     * Retrieves the "router" key of the Config service, and uses it
     * to instantiate the router. Uses the TreeRouteStack implementation by
     * default.
     */
    public function __invoke(
        ContainerInterface $container
    ): RouteStackInterface {
        /** @psalm-var RouterConfigShape $config */
        $config = $container->has('config') ? $container->get('config') : [
            'router' => [
                'router_class'   => TreeRouteStack::class,
                'route_builders' => RouteBuilderContainer::defaultBuilderMap(),
            ],
        ];

        $class                 = $config['router']['router_class'];
        $routeBuilderContainer = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        $router = $routeBuilderContainer->get($class)->build([]);

        assert($router instanceof RouteStackInterface);

        return $router;
    }
}
