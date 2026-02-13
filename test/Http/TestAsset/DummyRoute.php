<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteInterface;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function strlen;

/**
 * Dummy route.
 */
class DummyRoute implements HttpRouteInterface
{
    use RoutePriorityTrait;

    /**
     * match(): defined by HttpRouteInterface interface.
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

        return new RouteMatch(['offset' => $pathOffset], $matchLength);
    }

    /**
     * assemble(): defined by HttpRouteInterface interface.
     *
     * @see    Route::assemble()
     */
    public function assemble(?array $params = null, ?array $options = null): string
    {
        return '';
    }

    /**
     * factory(): defined by HttpRouteInterface interface
     *
     * @return DummyRoute
     */
    public static function factory(iterable $options = []): RouteInterface
    {
        return new static();
    }

    /**
     * getAssembledParams(): defined by HttpRouteInterface interface.
     *
     * @see    Route::getAssembledParams
     */
    public function getAssembledParams(): array
    {
        return [];
    }
}
