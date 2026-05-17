<?php

declare(strict_types=1);

namespace Laminas\Router;

use Stringable;

use function array_merge;
use function http_build_query;
use function sprintf;
use function str_starts_with;
use function strtolower;

readonly class AssembledUrl implements Stringable
{
    public function __construct(
        public string $path = '',
        public array $query = [],
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
            host: $other->host ?? $this->host,
            scheme: $other->scheme ?? $this->scheme,
            fragment: $other->fragment ?? $this->fragment,
            forceCanonical: $this->forceCanonical || $other->forceCanonical,
            port: $other->port ?? $this->port,
        );
    }

    public function __toString(): string
    {
        $uri = $this->path;

        if ($this->forceCanonical && $this->host !== null && $this->host !== '') {
            $scheme    = $this->scheme !== null && $this->scheme !== '' ? $this->scheme : 'http';
            $authority = $this->host;
            if ($this->shouldEmitPort($scheme, $this->port)) {
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

        if (! empty($this->query)) {
            $uri .= '?' . http_build_query($this->query);
        }

        if ($this->fragment) {
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
