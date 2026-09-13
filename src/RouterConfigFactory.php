<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;
use Traversable;

use function array_filter;
use function is_array;
use function is_string;
use function iterator_to_array;

use const ARRAY_FILTER_USE_BOTH;

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
        /** @var mixed $config */
        $config = $container->has('config') ? $container->get('config') : [];

        if ($config instanceof Traversable) {
            $config = iterator_to_array($config);
        }

        if (! is_array($config)) {
            throw new RuntimeException('Config service must return an array or Traversable');
        }

        /** @var mixed $routerConfig */
        $routerConfig = $config['router'] ?? [];
        if (! is_array($routerConfig)) {
            throw new RuntimeException('Config key "router" must be an array');
        }

        /** @var mixed $routerClassValue */
        $routerClassValue = $routerConfig['router_class'] ?? TreeRouteStack::class;
        if (! is_string($routerClassValue)) {
            throw new RuntimeException('Config key "router.router_class" must be a class name');
        }
        /** @var class-string<RouteStackInterface> $routerClass */
        $routerClass = $routerClassValue;

        /** @var mixed $routeBuildersValue */
        $routeBuildersValue = $routerConfig['route_builders'] ?? RouteBuilderContainer::defaultBuilderMap();
        if (! is_array($routeBuildersValue)) {
            throw new RuntimeException(
                'Config key "router.route_builders" must be an array of type => builder service id'
            );
        }

        $invalidRouteBuilders = array_filter(
            $routeBuildersValue,
            static fn(mixed $builder, int|string $type): bool => ! is_string($type) || ! is_string($builder),
            ARRAY_FILTER_USE_BOTH,
        );
        if ($invalidRouteBuilders !== []) {
            throw new RuntimeException(
                'Config key "router.route_builders" must contain string keys and values'
            );
        }

        /** @var array<string, class-string<RouteBuilderInterface>> $routeBuilders */
        $routeBuilders = $routeBuildersValue;
        /** @var mixed $translator */
        $translator = $routerConfig['translator'] ?? TranslatorInterface::class;
        if (! is_string($translator)) {
            throw new RuntimeException('Config key "router.translator" must be a class name');
        }
        /** @var class-string<TranslatorInterface> $translatorClass */
        $translatorClass = $translator;

        return new RouterConfig($routerClass, $routeBuilders, $translatorClass);
    }
}
