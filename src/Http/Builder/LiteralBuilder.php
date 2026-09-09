<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Literal;
use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Literal>
 */
final readonly class LiteralBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Literal
    {
        return Literal::factory($options);
    }
}
