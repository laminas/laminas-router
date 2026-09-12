<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * Builds a route instance from data-only options.
 *
 * Object dependencies must be constructor-injected into the builder
 *
 * @template-covariant TRoute of RouteInterface
 * @psalm-import-type RouteSpec from RouteInterface
 */
interface RouteBuilderInterface
{
    /**
     * @return TRoute
     */
    public function build(array $options): RouteInterface;
}
