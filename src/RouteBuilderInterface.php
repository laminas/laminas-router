<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * Builds a route instance from data-only options.
 *
 * Object dependencies must be constructor-injected into the builder
 *
 * @template-covariant TRoute of RouteInterface
 */
interface RouteBuilderInterface
{
    /**
     * @param array<string, mixed> $options
     * @return TRoute
     */
    public function build(array $options = []): RouteInterface;
}
