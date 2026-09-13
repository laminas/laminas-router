<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Segment;
use Laminas\Router\RouteBuilderInterface;
use Laminas\Translator\TranslatorInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<Segment>
 */
final readonly class SegmentBuilder implements RouteBuilderInterface
{
    public function __construct(
        private ?TranslatorInterface $translator,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): Segment
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

        return new Segment($name, $route, $constraints, $defaults, $priority, $this->translator);
    }
}
