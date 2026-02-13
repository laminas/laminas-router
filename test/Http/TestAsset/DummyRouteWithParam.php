<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

use function strlen;

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
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): RouteMatch {
        $pathLength  = strlen($request->getUri()->getPath());
        $matchLength = $pathLength - ($pathOffset ?? 0);

        return new RouteMatch(['foo' => 'bar'], $matchLength);
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
