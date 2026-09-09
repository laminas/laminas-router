<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Override;
use Psr\Http\Message\RequestInterface;

/**
 * Placeholder route.
 */
final readonly class Placeholder implements HttpRouteInterface
{
    /**
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        private array $defaults,
        private int|null $priority = null
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): HttpRouteMatch|null {
        return new HttpRouteMatch($this->defaults, $this->name, 0);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl();
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
