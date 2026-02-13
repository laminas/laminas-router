<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteInterface as BaseRoute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP route interface.
 */
interface HttpRouteInterface extends BaseRoute
{
    /**
     * Match a given server request.
     */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch;

    /**
     * Get a list of parameters used while assembling.
     */
    public function getAssembledParams(): array;
}
