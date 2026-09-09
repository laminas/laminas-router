<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\RouteMatchInterface;
use Override;
use Psr\Http\Message\RequestInterface;

use function strlen;
use function strpos;

/**
 * Literal route.
 */
final readonly class Literal implements HttpRouteInterface
{
    /**
     * Create a new literal route.
     *
     * @param  array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        /**
         * RouteInterface to match.
         */
        private string $route,
        /**
         * Default values.
         */
        private array $defaults = [],
        private int|null $priority = null
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatchInterface {
        $path = $request->getUri()->getPath();

        if ($pathOffset !== null) {
            if ($pathOffset >= 0 && strlen($path) >= $pathOffset && ! empty($this->route)) {
                if (strpos($path, $this->route, $pathOffset) === $pathOffset) {
                    return new HttpRouteMatch($this->defaults, $this->name, strlen($this->route));
                }
            }

            return null;
        }

        if ($path === $this->route) {
            return new HttpRouteMatch($this->defaults, $this->name, strlen($this->route));
        }

        if ($this->route === '/' && ($path === '' || $path === '/')) {
            return new HttpRouteMatch($this->defaults, $this->name, strlen($this->route));
        }

        return null;
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl(path:$this->route);
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
