<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Literal;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Literal>
 */
final readonly class LiteralBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Literal
    {
        $name  = $options['name'] ?? null;
        $route = $options['route'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        if (! is_string($route)) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }

        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" in options array');
        }

        return new Literal($name, $route, $defaults, $priority);
    }
}
