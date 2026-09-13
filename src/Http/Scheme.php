<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\RouteMatchInterface;
use Override;
use Psr\Http\Message\RequestInterface;

/**
 * Scheme route.
 */
final readonly class Scheme implements HttpRouteInterface
{
    /**
     * Create a new scheme route.
     *
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        /**
         * Scheme to match.
         *
         * @var non-empty-string
         */
        private string $scheme,
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
        if ($request->getUri()->getScheme() !== $this->scheme) {
            return null;
        }

        return new HttpRouteMatch($this->defaults, $this->name, 0);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl(scheme:$this->scheme);
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
