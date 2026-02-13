<?php

declare(strict_types=1);

namespace Laminas\Router;

use Traversable;

use function array_map;
use function is_array;
use function is_iterable;
use function iterator_to_array;
use function method_exists;
use function sprintf;

trait RouteConfigTrait
{
    /**
     * Convert an iterable to an array.
     *
     * @param iterable<array-key, mixed>|Traversable $iterator
     * @return array<array-key, mixed>
     */
    public static function iteratorToArray(iterable $iterator, bool $recursive = true): array
    {
        if (! $recursive) {
            return is_array($iterator) ? $iterator : iterator_to_array($iterator);
        }

        if (! is_array($iterator)) {
            if (method_exists($iterator, 'toArray')) {
                return $iterator->toArray();
            } else {
                $array = [];
                /** @psalm-suppress MixedAssignment, MixedArrayOffset */
                foreach ($iterator as $key => $value) {
                    $array[$key] = is_iterable($value)
                        ? static::iteratorToArray($value)
                        : $value;
                }

                return $array;
            }
        }

        return array_map(function ($value) {
            return is_iterable($value)
                ? static::iteratorToArray($value)
                : $value;
        }, $iterator);
    }

    /**
     * Provide completed array options for specific routes.
     *
     * @param array<string> $requiredOptions
     * @param array<array-key, mixed> $defaultOptions
     * @return array<array-key, mixed>
     */
    private static function processRouteOptions(
        iterable $options,
        array $requiredOptions = [],
        array $defaultOptions = []
    ): array {
        if (! is_array($options)) {
            $options = self::iteratorToArray($options);
        }

        foreach ($requiredOptions as $requiredOption) {
            if (! isset($options[$requiredOption])) {
                throw new Exception\InvalidArgumentException(sprintf('Missing "%s" option', $requiredOption));
            }
        }

        foreach ($defaultOptions as $defaultOption => $defaultValue) {
            if (! isset($options[$defaultOption])) {
                $options[$defaultOption] = $defaultValue;
            }
        }

        return $options;
    }
}
