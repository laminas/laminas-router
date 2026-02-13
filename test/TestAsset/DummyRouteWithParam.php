<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dummy route.
 */
final class DummyRouteWithParam extends DummyRoute
{
    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     */
    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        return new RouteMatch(['foo' => 'bar']);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     *
     * @return mixed
     */
    public function assemble(?array $params = null, ?array $options = null): string
    {
        return $params['foo'] ?? '';
    }
}
