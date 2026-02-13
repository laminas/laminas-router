<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\RouteInterface as BaseRouteInterface;

/**
 * Route container interface for HTTP route storage.
 *
 * @extends RouteContainerInterface<string, HttpRouteInterface>
 */
interface HttpRouteContainerInterface extends RouteContainerInterface
{
    /**
     * Insert a route with a given key and priority.
     */
    public function insert(string $key, BaseRouteInterface $value, ?int $priority = null): void;

    /**
     * Get a route by key.
     */
    public function get(string $key): ?HttpRouteInterface;

    /**
     * Get the current route.
     */
    public function current(): HttpRouteInterface;
}
