<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TreeRouteStack<TRoute>>
 */
final readonly class TreeRouteStackBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return TreeRouteStack::factory($options);
    }
}
