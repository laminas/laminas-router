<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouterConfig;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class TranslatorAwareTreeRouteStackBuilderFactory
{
    public function __invoke(ContainerInterface $container): TranslatorAwareTreeRouteStackBuilder
    {
        $routeBuilder = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilder instanceof RouteBuilderContainerInterface);

        return new TranslatorAwareTreeRouteStackBuilder($routeBuilder);
    }
}
