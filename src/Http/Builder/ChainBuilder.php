<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Chain;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\RouteBuilderInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Chain<TRoute>>
 */
final readonly class ChainBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Chain
    {
        return Chain::factory($options);
    }
}
