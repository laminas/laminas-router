<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function strlen;
use function substr;

use const SEEK_SET;

/**
 * Simple mock implementation of StreamInterface for testing purposes.
 */
final class MockStream implements StreamInterface
{
    private bool $readable = true;
    private bool $writable = true;
    private bool $seekable = true;

    public function __construct(
        private string $content = ''
    ) {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        $this->content  = '';
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
    }

    public function detach(): void
    {
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (! $this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if (! $this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $this->content .= $string;

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if (! $this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        return substr($this->content, 0, $length);
    }

    public function getContents(): string
    {
        if (! $this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        return $this->content;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key === null ? [] : null;
    }
}
