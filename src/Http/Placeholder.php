<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\ReturnOfAssemble;
use Override;
use Psr\Http\Message\RequestInterface;

/**
 * Placeholder route.
 */
final class Placeholder implements HttpRouteInterface
{
    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * @param array<string, string> $defaults
     */
    public function __construct(
        /** @var array<string, string> */
        private readonly array $defaults
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        /** @var array<string, string> $defaults */
        $defaults = $options['defaults'] ?? [];

        return new self($defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null
    ): HttpRouteMatch|null {
        return new HttpRouteMatch($this->defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        return new ReturnOfAssemble();
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return [];
    }
}
