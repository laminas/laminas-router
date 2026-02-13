<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * RouteInterface match.
 */
interface RouteMatchInterface
{
    /**
     * Set name of matched route.
     */
    public function setMatchedRouteName(string $name): RouteMatchInterface;

    /**
     * Get name of matched route.
     */
    public function getMatchedRouteName(): ?string;

    /**
     * Set a parameter.
     */
    public function setParam(string $name, mixed $value): RouteMatchInterface;

    /**
     * Get all parameters.
     *
     * @return array<array-key, mixed>
     */
    public function getParams(): array;

    /**
     * Get a specific parameter.
     */
    public function getParam(string $name, mixed $default = null): mixed;
}
