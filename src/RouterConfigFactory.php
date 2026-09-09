<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;
use Traversable;

use function is_array;
use function is_string;
use function iterator_to_array;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RouterConfigFactory
{
    public function __invoke(ContainerInterface $container): RouterConfig
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if ($config instanceof Traversable) {
            $config = iterator_to_array($config);
        }

        if (! is_array($config)) {
            throw new RuntimeException('Config service must return an array or Traversable');
        }

        $routerConfig = $config['router'] ?? [];
        if (! is_array($routerConfig)) {
            throw new RuntimeException('Config key "router" must be an array');
        }

        $routerClassValue = $routerConfig['router_class'] ?? TreeRouteStack::class;
        if (! is_string($routerClassValue)) {
            throw new RuntimeException('Config key "router.router_class" must be a class name');
        }
        /** @var class-string<RouteStackInterface> $routerClass */
        $routerClass = $routerClassValue;

        $routeBuildersValue = $routerConfig['route_builders'] ?? RouteBuilderContainer::defaultBuilderMap();
        if (! is_array($routeBuildersValue)) {
            throw new RuntimeException(
                'Config key "router.route_builders" must be an array of type => builder service id'
            );
        }

        foreach ($routeBuildersValue as $type => $builder) {
            if (! is_string($type) || ! is_string($builder)) {
                throw new RuntimeException(
                    'Config key "router.route_builders" must contain string keys and values'
                );
            }
        }

        /** @var array<string, class-string<RouteBuilderInterface>> $routeBuilders */
        $routeBuilders = $routeBuildersValue;
        $translator    = $routerConfig['translator'] ?? TranslatorInterface::class;
        if (! is_string($translator)) {
            throw new RuntimeException('Config key "router.translator" must be a class name');
        }
        /** @var class-string<TranslatorInterface> $translatorClass */
        $translatorClass = $translator;

        return new RouterConfig($routerClass, $routeBuilders, $translatorClass);
    }
}
