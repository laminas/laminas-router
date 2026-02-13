<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Laminas\Router\RouteInterface;

/**
 * Route container interface for simple route storage.
 *
 * @extends RouteContainerInterface<string, RouteInterface>
 */
interface SimpleRouteContainerInterface extends RouteContainerInterface
{
    /**
     * Insert a route with a given key and priority.
     */
    public function insert(string $key, RouteInterface $value, ?int $priority = null): void;

    /**
     * Get a route by key.
     */
    public function get(string $key): ?RouteInterface;

    /**
     * Get the current route.
     */
    public function current(): RouteInterface;
}
