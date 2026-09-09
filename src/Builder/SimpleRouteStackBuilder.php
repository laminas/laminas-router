<?php

declare(strict_types=1);

namespace Laminas\Router\Builder;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;
use Laminas\Router\SimpleRouteStack;

/**
 * @template TRoute of RouteInterface
 * @implements RouteBuilderInterface<SimpleRouteStack<TRoute>>
 */
final readonly class SimpleRouteStackBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): SimpleRouteStack
    {
        return SimpleRouteStack::factory($options);
    }
}
