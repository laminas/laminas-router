<?php

declare(strict_types=1);

namespace Laminas\Router;

use function array_merge;
use function http_build_query;
use function sprintf;
use function str_starts_with;
use function strtolower;

readonly final class AssembledUrl
{
    /**
     * @param array<string, scalar> $query
     * @param list<string> $assembledParams
     * @param non-empty-string|null $host
     * @param non-empty-string|null $scheme
     */
    public function __construct(
        public string $path = '',
        public array $query = [],
        public array $assembledParams = [],
        public ?string $host = null,
        public ?string $scheme = null,
        public ?string $fragment = null,
        public bool $forceCanonical = false,
        public ?int $port = null,
    ) {
    }

    public function merge(self $other): self
    {
        return new self(
            path: $this->path . $other->path,
            query: array_merge($this->query, $other->query),
            assembledParams: [...$this->assembledParams, ...$other->assembledParams],
            host: $other->host ?? $this->host,
            scheme: $other->scheme ?? $this->scheme,
            fragment: $other->fragment ?? $this->fragment,
            forceCanonical: $this->forceCanonical || $other->forceCanonical,
            port: $other->port ?? $this->port,
        );
    }

    public function toString(): string
    {
        $uri = $this->path;

        if ($this->forceCanonical && $this->host !== null) {
            $scheme    = $this->scheme ?? 'http';
            $authority = $this->host;
            if ($this->port !== null && $this->shouldEmitPort($scheme, $this->port)) {
                $authority .= ':' . $this->port;
            }

            $uri = sprintf('%s://%s', $scheme, $authority);

            $pathPart = $this->path;
            if ($pathPart === '') {
                $pathPart = '/';
            } elseif (! str_starts_with($pathPart, '/')) {
                $pathPart = '/' . $pathPart;
            }

            $uri .= $pathPart;
        }

        if (count($this->query) !== 0) {
            $uri .= '?' . http_build_query($this->query);
        }

        if ($this->fragment !== null) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function shouldEmitPort(string $scheme, ?int $port): bool
    {
        if ($port === null) {
            return false;
        }

        $scheme = strtolower($scheme);

        return ! (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443));
    }
}
