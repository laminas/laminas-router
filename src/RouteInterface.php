<?php

declare(strict_types=1);

namespace Laminas\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * RouteInterface interface.
 */
interface RouteInterface
{
    /**
     * Create a new route with given options.
     */
    public static function factory(iterable $options = []): RouteInterface;

    /**
     * Match a given request.
     */
    public function match(ServerRequestInterface $request): ?RouteMatch;

    /**
     * Assemble the route.
     */
    public function assemble(array $params = [], array $options = []): mixed;

    /**
     * Set the route priority.
     */
    public function setPriority(?int $priority): void;

    /**
     * Get the route priority.
     */
    public function getPriority(): int;
}
