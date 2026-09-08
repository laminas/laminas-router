<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

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
