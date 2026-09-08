<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TreeRouteStack<TRoute>>
 */
final readonly class TreeRouteStackBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): TreeRouteStack
    {
        return TreeRouteStack::factory($options);
    }
}
