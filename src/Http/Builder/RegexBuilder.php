<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Regex;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Regex>
 */
final readonly class RegexBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Regex
    {
        /** @var string|null $name */
        $name = $options['name'] ?? null;
        /** @var string|null $regex */
        $regex = $options['regex'] ?? null;
        /** @var string|null $spec */
        $spec = $options['spec'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        if (! is_string($regex) || $regex === '') {
            throw new Exception\InvalidArgumentException('Missing "regex" in options array');
        }
        if (! is_string($spec) || $spec === '') {
            throw new Exception\InvalidArgumentException('Missing "spec" in options array');
        }
        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" in options array');
        }

        return new Regex($name, $regex, $spec, $defaults, $priority);
    }
}
