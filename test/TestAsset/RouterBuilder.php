<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Router>
 */
final readonly class RouterBuilder implements RouteBuilderInterface
{
    public function build(array $options): Router
    {
        return new Router();
    }
}
