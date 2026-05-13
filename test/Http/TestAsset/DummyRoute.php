<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\ReturnOfAssemble;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\RequestInterface;

/**
 * Dummy route.
 */
class DummyRoute implements HttpRouteInterface
{
    /** @inheritDoc */
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null
    ): RouteMatch {
        return new HttpRouteMatch(['offset' => $pathOffset], -4);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        return new ReturnOfAssemble();
    }

    /** @inheritDoc */
    public static function factory(array $options = []): static
    {
        return new static();
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return [];
    }
}
