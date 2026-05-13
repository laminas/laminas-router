<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\ReturnOfAssemble;
use Override;
use Psr\Http\Message\RequestInterface;

use function is_string;

/**
 * Scheme route.
 */
final class Scheme implements HttpRouteInterface
{
    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * Create a new scheme route.
     *
     * @param array<string, string> $defaults
     */
    public function __construct(
        /**
         * Scheme to match.
         */
        private readonly string $scheme,
        /**
         * Default values.
         */
        private readonly array $defaults = []
    ) {
    }

    /**
     * @param array{'scheme'?:string, 'defaults'?: array<string, string>} $options
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $scheme   = $options['scheme'] ?? null;
        $defaults = $options['defaults'] ?? [];

        if (! is_string($scheme) || $scheme === '') {
            throw new Exception\InvalidArgumentException('Missing "scheme" in options array');
        }

        return new self($scheme, $defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null): ?HttpRouteMatch
    {
        if ($request->getUri()->getScheme() !== $this->scheme) {
            return null;
        }

        return new HttpRouteMatch($this->defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        return new ReturnOfAssemble(scheme:$this->scheme);
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return [];
    }
}
