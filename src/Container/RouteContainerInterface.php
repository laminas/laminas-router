<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Countable;
use Iterator;
use Laminas\Router\RouteInterface;

/**
 * Base route container interface.
 *
 * @template TKey of string
 * @template TValue of RouteInterface
 * @extends Iterator<TKey, TValue>
 */
interface RouteContainerInterface extends Iterator, Countable
{
    /**
     * Insert a route with a given key and priority.
     *
     * @param TKey $key
     * @param TValue $value
     */
    public function insert(string $key, RouteInterface $value, ?int $priority = null): void;

    /**
     * Get a route by key.
     *
     * @param TKey $key
     * @return TValue|null
     */
    public function get(string $key): ?RouteInterface;

    /**
     * Remove a route by key.
     *
     * @param TKey $key
     */
    public function remove(string $key): void;

    /**
     * Clear all routes.
     */
    public function clear(): void;
}
