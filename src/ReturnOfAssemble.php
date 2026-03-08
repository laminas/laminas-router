<?php

declare(strict_types=1);

namespace Laminas\Router;

use Stringable;
use function array_merge;
use function http_build_query;
use function sprintf;

readonly final class ReturnOfAssemble implements Stringable
{
    public function __construct(
        public string $path = '',
        public array $query = [],
        public ?string $host = null,
        public ?string $scheme = null,
        public ?string $fragment = null,
        public bool $forceCanonical = false
    ) {
    }

    // Fusionne un autre résultat dans celui-ci (utilisé par la Chain)
    public function merge(self $other): self
    {
        return new self(
            path: $this->path . $other->path,
            query: array_merge($this->query, $other->query),
            host: $other->host ?? $this->host,
            scheme: $other->scheme ?? $this->scheme,
            fragment: $other->fragment ?? $this->fragment,
            forceCanonical: $this->forceCanonical || $other->forceCanonical
        );
    }

    public function __toString(): string
    {
        $uri = $this->path;

        if ($this->forceCanonical && $this->host !== null) {
            $scheme = $this->scheme ?: 'http';
            $uri    = sprintf('%s://%s', $scheme, $this->host);
            if (!str_ends_with($uri, '/') && !str_starts_with($this->path, '/')) {
                $uri .= '/';
            }
            $uri .= $this->path;
        }

        if (! empty($this->query)) {
            $uri .= '?' . http_build_query($this->query);
        }

        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
