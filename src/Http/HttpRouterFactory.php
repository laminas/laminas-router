<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\ConfigProvider;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal LaminasTest\Router
 * @psalm-import-type RouterConfigShape from ConfigProvider
 */
final readonly class HttpRouterFactory implements FactoryInterface
{
    /**
     * Create and return the HTTP router
     *
     * Retrieves the "router" key of the Config service, and uses it
     * to instantiate the router. Uses the TreeRouteStack implementation by
     * default.
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RouteStackInterface {
        /** @psalm-var RouterConfigShape $config */
        $config = $container->has('config') ? $container->get('config') : [
            'router' => [
                'router_class'  => TreeRouteStack::class,
                'route_plugins' => RoutePluginManager::class,
            ],
        ];

        $class              = $config['router']['router_class'];
        $routePluginManager = $container->get($config['router']['route_plugins']);

        assert($routePluginManager instanceof RoutePluginManager);

        $config['route_plugins'] = $routePluginManager;

        if ($class === TranslatorAwareTreeRouteStack::class) {
            $translaterServiceName = $config['router']['translator']
                             ?? TranslatorInterface::class;

            $translator = $container->get($translaterServiceName);
            assert($translator instanceof TranslatorInterface);
            $config['translator'] = $translator;
        }

        $router = $class::factory($config);

        assert($router instanceof RouteStackInterface);

        return $router;
    }
}
