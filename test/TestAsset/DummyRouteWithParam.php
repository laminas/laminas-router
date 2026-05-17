<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\AssembledUrl;
use Laminas\Router\RouteMatch;
use Psr\Http\Message\RequestInterface;

use function array_key_exists;

/**
 * Dummy route.
 */
final class DummyRouteWithParam extends DummyRoute
{
    /** @inheritDoc */
    public function match(RequestInterface $request): RouteMatch
    {
        return new RouteMatch(['foo' => 'bar']);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl(array_key_exists('foo', $params) ? (string) $params['foo'] : '');
    }

    /** @inheritDoc */
    public static function factory(array $options = []): static
    {
        return new self();
    }
}
