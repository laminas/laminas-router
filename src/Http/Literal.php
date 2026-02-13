<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function strlen;
use function strpos;

/**
 * Literal route.
 */
final class Literal implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Create a new literal route.
     */
    public function __construct(
        private readonly string $route,
        private readonly array $defaults = []
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Literal
    {
        $options = self::processRouteOptions(
            $options,
            ['route'],
            ['defaults' => []],
        );

        return new Literal(
            $options['route'],
            $options['defaults']
        );
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        $path = $request->getUri()->getPath();

        if ($pathOffset !== null) {
            if ($pathOffset >= 0 && strlen($path) >= $pathOffset && ! empty($this->route)) {
                if (strpos($path, $this->route, $pathOffset) === $pathOffset) {
                    return new RouteMatch($this->defaults, strlen($this->route));
                }
            }

            return null;
        }

        if ($path === $this->route) {
            return new RouteMatch($this->defaults, strlen($this->route));
        }

        return null;
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string
    {
        return $this->route;
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return [];
    }
}
