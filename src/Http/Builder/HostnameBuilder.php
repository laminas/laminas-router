<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Hostname;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Hostname>
 */
final readonly class HostnameBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Hostname
    {
        /** @var string|null $name */
        $name = $options['name'] ?? null;
        /** @var string|null $route */
        $route = $options['route'] ?? null;
        /** @psalm-var array<non-empty-string, string> $constraints */
        $constraints = $options['constraints'] ?? [];
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

        return new Hostname($name, $route, $constraints, $defaults, $priority);
    }
}
