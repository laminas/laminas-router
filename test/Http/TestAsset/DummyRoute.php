<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\HttpRouteMatch;
use Psr\Http\Message\RequestInterface;

/**
 * Dummy route.
 */
final readonly class DummyRoute implements HttpRouteInterface
{
    public function __construct(private string $name)
    {
    }

    /** @inheritDoc */
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): HttpRouteMatch {
        return new HttpRouteMatch(['offset' => $pathOffset], $this->name, -4);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl();
    }

    public function getPriority(): ?int
    {
        return null;
    }
}
