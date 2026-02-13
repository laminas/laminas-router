<?php

declare(strict_types=1);

namespace Laminas\Router;

use function array_key_exists;

/**
 * RouteInterface match.
 */
class RouteMatch implements RouteMatchInterface
{
    /**
     * Match parameters.
     */
    protected array $params = [];

    /**
     * Matched route name.
     */
    protected ?string $matchedRouteName = null;

    /**
     * Create a RouteMatch with given parameters.
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Set name of matched route.
     */
    public function setMatchedRouteName(string $name): RouteMatchInterface
    {
        $this->matchedRouteName = $name;
        return $this;
    }

    /**
     * Get name of matched route.
     */
    public function getMatchedRouteName(): ?string
    {
        return $this->matchedRouteName;
    }

    /**
     * Set a parameter.
     */
    public function setParam(string $name, mixed $value): RouteMatchInterface
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Get all parameters.
     *
     * @return array<array-key, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get a specific parameter.
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return $default;
    }
}
