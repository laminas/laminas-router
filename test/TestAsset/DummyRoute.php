<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatchInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Dummy route.
 */
final readonly class DummyRoute implements RouteInterface
{
    /**
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        private int|null $priority = null,
        private array $defaults = [],
    ) {
    }

    /** @inheritDoc */
    public function match(RequestInterface $request): RouteMatchInterface
    {
        return new HttpRouteMatch($this->defaults, $this->name);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl();
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
