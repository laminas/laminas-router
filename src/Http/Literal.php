<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\ReturnOfAssemble;
use Override;
use Psr\Http\Message\RequestInterface;

use function assert;
use function is_array;
use function is_string;
use function strlen;
use function strpos;

/**
 * Literal route.
 */
final class Literal implements HttpRouteInterface
{
    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * Create a new literal route.
     *
     * @param  array<string, string> $defaults
     */
    public function __construct(
        /**
         * RouteInterface to match.
         */
        private readonly string $route,
        /**
         * Default values.
         */
        private readonly array $defaults = []
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $route    = $options['route'] ?? null;
        $defaults = $options['defaults'] ?? [];

        if (! is_string($route)) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }

        assert(is_array($defaults));

        /** @psalm-var array<string, string> $defaults */

        return new self($route, $defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?HttpRouteMatch
    {
        $path = $request->getUri()->getPath();

        if ($pathOffset !== null) {
            if ($pathOffset >= 0 && strlen($path) >= $pathOffset && ! empty($this->route)) {
                if (strpos($path, $this->route, $pathOffset) === $pathOffset) {
                    return new HttpRouteMatch($this->defaults, strlen($this->route));
                }
            }

            return null;
        }

        if ($path === $this->route) {
            return new HttpRouteMatch($this->defaults, strlen($this->route));
        }

        return null;
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        return new ReturnOfAssemble(path:$this->route);
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return [];
    }
}
