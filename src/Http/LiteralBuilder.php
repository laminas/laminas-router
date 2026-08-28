<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Literal>
 */
final readonly class LiteralBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Literal::factory($options);
    }
}
