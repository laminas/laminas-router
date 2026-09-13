<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Placeholder>
 */
final readonly class PlaceholderBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Placeholder
    {
        /** @var string|null $name */
        $name = $options['name'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" in options array');
        }

        return new Placeholder($name, $defaults, $priority);
    }
}
