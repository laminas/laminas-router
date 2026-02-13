<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

use function array_merge;
use function implode;
use function is_array;
use function strtolower;

/**
 * Simple mock implementation of ServerRequestInterface for testing purposes.
 */
final class MockServerRequest implements ServerRequestInterface
{
    private UriInterface $uri;

    private string $method;

    /** @var array<array-key, mixed> */
    private array $serverParams;

    /** @var array<array-key, mixed> */
    private array $cookieParams = [];

    /** @var array<array-key, mixed> */
    private array $queryParams = [];

    /** @var array<array-key, list<string>> */
    private array $headers = [];

    /** @var array<array-key, mixed> */
    private array $attributes = [];

    private string $protocolVersion = '1.1';

    /** @var array<array-key, UploadedFileInterface> */
    private array $uploadedFiles = [];

    private null|array|object $parsedBody = null;

    private StreamInterface $body;

    public function __construct(
        ?UriInterface $uri = null,
        string $method = 'GET',
        array $serverParams = [],
        ?StreamInterface $body = null
    ) {
        $this->uri          = $uri ?? new MockUri();
        $this->method       = $method;
        $this->serverParams = $serverParams;
        $this->body         = $body ?? new MockStream();
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion(string $version): ServerRequestInterface
    {
        $new                  = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    /**
     * Retrieves all message header values.
     *
     * @return string[][] Returns an associative array of the message's headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $name Case-insensitive header field name.
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string          $name  Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @throws InvalidArgumentException For invalid header names or values.
     * @return static
     */
    public function withHeader(string $name, mixed $value): ServerRequestInterface
    {
        $new                             = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];

        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * @param string          $name  Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @throws InvalidArgumentException For invalid header names or values.
     * @return static
     */
    public function withAddedHeader(string $name, mixed $value): ServerRequestInterface
    {
        $new                             = clone $this;
        $existing                        = $new->headers[strtolower($name)] ?? [];
        $new->headers[strtolower($name)] = array_merge($existing, is_array($value) ? $value : [$value]);

        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader(string $name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);

        return $new;
    }

    /**
     * Gets the body of the message.
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @return static
     */
    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $new       = clone $this;
        $new->body = $body;

        return $new;
    }

    /**
     * Retrieves the message's request target.
     */
    public function getRequestTarget(): string
    {
        return $this->uri->getPath() ?: '/';
    }

    /**
     * Return an instance with the specific request-target.
     *
     * @return static
     */
    public function withRequestTarget(string $requestTarget): ServerRequestInterface
    {
        $uri = $this->getUri()->withPath($requestTarget);

        $new      = clone $this;
        $new->uri = $uri;

        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method Case-sensitive method.
     * @throws InvalidArgumentException For invalid HTTP methods.
     * @return static
     */
    public function withMethod(string $method): ServerRequestInterface
    {
        $new         = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * Retrieves the URI instance.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @param UriInterface $uri          New request URI to use.
     * @param bool         $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
    {
        $new      = clone $this;
        $new->uri = $uri;

        return $new;
    }

    /**
     * Retrieve server parameters.
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Retrieve cookies.
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new               = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * Retrieve query string arguments.
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query Array of query string arguments, typically from $_GET.
     * @return static
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new              = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * @return UploadedFileInterface[] An array tree of UploadedFileInterface instances.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return null|array|object The deserialized body parameters, if any.
     */
    public function getParsedBody(): object|array|null
    {
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object $data The deserialized body data.
     * @throws InvalidArgumentException If an unsupported argument type is provided.
     * @return static
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        $new             = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * Retrieve attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @see getAttributes()
     *
     * @param string $name    The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * @see getAttributes()
     *
     * @param string $name  The attribute name.
     * @param mixed  $value The value of the attribute.
     * @return static
     */
    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        $new                    = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @see getAttributes()
     *
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }
}
