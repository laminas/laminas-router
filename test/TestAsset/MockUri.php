<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Uri\Http as HttpUri;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 UriInterface adapter wrapping Laminas\Uri\Http.
 */
final class MockUri implements UriInterface
{
    private HttpUri $uri;

    public function __construct(string $uri = '')
    {
        $this->uri = new HttpUri($uri);
    }

    public function getScheme(): string
    {
        return $this->uri->getScheme() ?? '';
    }

    public function getAuthority(): string
    {
        $host = $this->getHost();
        if ($host === '') {
            return '';
        }

        $authority = $host;
        $userInfo  = $this->getUserInfo();

        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        $port = $this->getPort();
        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->uri->getUserInfo() ?? '';
    }

    public function getHost(): string
    {
        return $this->uri->getHost() ?? '';
    }

    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    public function getPath(): string
    {
        $path = $this->uri->getPath() ?? '';

        // For HTTP(S) URIs with a host but no path, normalize to '/'
        if ($this->getHost() !== '' && $path === '') {
            return '/';
        }

        return $path;
    }

    public function getQuery(): string
    {
        return $this->uri->getQuery() ?? '';
    }

    public function getFragment(): string
    {
        return $this->uri->getFragment() ?? '';
    }

    public function withScheme(string $scheme): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setScheme($scheme);
        return $new;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setUserInfo($password !== null ? $user . ':' . $password : $user);
        return $new;
    }

    public function withHost(string $host): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setHost($host);
        return $new;
    }

    public function withPort(?int $port): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setPort($port);
        return $new;
    }

    public function withPath(string $path): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setPath($path);
        return $new;
    }

    public function withQuery(string $query): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setQuery($query);
        return $new;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $new      = clone $this;
        $new->uri = clone $this->uri;
        $new->uri->setFragment($fragment);
        return $new;
    }

    public function __toString(): string
    {
        return $this->uri->toString();
    }
}
