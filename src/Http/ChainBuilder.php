<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Chain<TRoute>>
 */
final readonly class ChainBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Chain::factory($options);
    }
}
