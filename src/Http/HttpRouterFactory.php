<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouterConfig;
use Laminas\Router\RouteStackInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal LaminasTest\Router
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
        $config                = $container->get(RouterConfig::class);
        $routeBuilderContainer = $container->get(RouteBuilderContainerInterface::class);

        assert($config instanceof RouterConfig);
        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        $router = $container->get($config->routerClass);

        assert($router instanceof RouteStackInterface);

        return $router;
    }
}
