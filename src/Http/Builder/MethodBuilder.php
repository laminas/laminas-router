<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Method;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Method>
 */
final readonly class MethodBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Method
    {
        $name = $options['name'] ?? null;
        /** @var mixed $verb */
        $verb = $options['verb'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        if (! is_string($verb)) {
            throw new Exception\InvalidArgumentException('Missing "verb" in options array');
        }

        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" in options array');
        }

        return new Method($name, $verb, $defaults, $priority);
    }
}
