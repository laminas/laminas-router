<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class SimpleRouteStackBuilderFactory
{
    public function __invoke(): SimpleRouteStackBuilder
    {
        return new SimpleRouteStackBuilder();
    }
}
