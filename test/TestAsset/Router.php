<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\ReturnOfAssemble;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteStackInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @template TRoute of RouteInterface
 * @template-implements RouteStackInterface<TRoute>
 */
final class Router implements RouteStackInterface
{
    /** @inheritDoc */
    public static function factory(array $options = []): static
    {
        return new self();
    }

    /** @inheritDoc */
    public function match(RequestInterface $request): null
    {
        return null;
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        return new ReturnOfAssemble();
    }

    /** @inheritDoc */
    public function addRoute(string|int $name, array|RouteInterface $route, ?int $priority = null): void
    {
    }

    /** @inheritDoc */
    public function addRoutes(array $routes): void
    {
    }

    /** @inheritDoc */
    public function removeRoute(string $name): void
    {
    }

    /** @inheritDoc */
    public function setRoutes(array $routes): void
    {
    }
}
