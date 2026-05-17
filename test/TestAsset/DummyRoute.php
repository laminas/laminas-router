<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\AssembledUrl;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\RequestInterface;

/**
 * Dummy route.
 */
class DummyRoute implements RouteInterface
{
    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public ?int $priority = null;

    /** @inheritDoc */
    public function match(RequestInterface $request): RouteMatch
    {
        return new RouteMatch([]);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl();
    }

    /** @inheritDoc */
    public static function factory(array $options = []): static
    {
        return new static();
    }
}
