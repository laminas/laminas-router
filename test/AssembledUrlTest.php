<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\AssembledUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssembledUrlTest extends TestCase
{
    public function testMergeConcatenatesAssembledParams(): void
    {
        $base  = new AssembledUrl(assembledParams: ['foo']);
        $other = new AssembledUrl(assembledParams: ['bar']);

        static::assertSame(['foo', 'bar'], $base->merge($other)->assembledParams);
    }

    public function testMergeCombinesPathQueryAndScalarProperties(): void
    {
        $base  = new AssembledUrl(
            path: '/base',
            query: ['keep' => 'a', 'override' => 'old'],
            host: 'base.example',
            scheme: 'http',
            fragment: 'base',
            forceCanonical: false,
            port: 8080,
        );
        $other = new AssembledUrl(
            path: '/other',
            query: ['add' => 'b', 'override' => 'new'],
            host: 'other.example',
            scheme: 'https',
            fragment: 'other',
            forceCanonical: true,
            port: 443,
        );

        $merged = $base->merge($other);

        $this->assertSame('/base/other', $merged->path);
        $this->assertSame(['keep' => 'a', 'override' => 'new', 'add' => 'b'], $merged->query);
        $this->assertSame('other.example', $merged->host);
        $this->assertSame('https', $merged->scheme);
        $this->assertSame('other', $merged->fragment);
        $this->assertTrue($merged->forceCanonical);
        $this->assertSame(443, $merged->port);

        $fallback = new AssembledUrl(path: '/x');
        $merged   = $base->merge($fallback);

        $this->assertSame('base.example', $merged->host);
        $this->assertSame('http', $merged->scheme);
        $this->assertSame('base', $merged->fragment);
        $this->assertSame(8080, $merged->port);
    }

    public function testMergeForceCanonicalIsTrueWhenEitherSideIsTrue(): void
    {
        $false = new AssembledUrl(forceCanonical: false);
        $true  = new AssembledUrl(forceCanonical: true);

        $this->assertTrue($false->merge($true)->forceCanonical);
        $this->assertTrue($true->merge($false)->forceCanonical);
        $this->assertFalse($false->merge($false)->forceCanonical);
    }

    /**
     * @param non-empty-string $scheme
     */
    #[DataProvider('defaultPortProvider')]
    public function testToStringOmitsDefaultPorts(string $scheme, int $port, string $expected): void
    {
        $url = new AssembledUrl(
            path: '/foo',
            host: 'example.com',
            scheme: $scheme,
            forceCanonical: true,
            port: $port,
        );

        $this->assertSame($expected, $url->toString());
    }

    /** @return iterable<non-empty-string, array{non-empty-string, int, string}> */
    public static function defaultPortProvider(): iterable
    {
        yield 'http default port' => ['http', 80, 'http://example.com/foo'];
        yield 'https default port' => ['https', 443, 'https://example.com/foo'];
        yield 'uppercase http scheme' => ['HTTP', 80, 'HTTP://example.com/foo'];
    }

    /**
     * @param non-empty-string $scheme
     */
    #[DataProvider('nonDefaultPortProvider')]
    public function testToStringEmitsNonDefaultPorts(string $scheme, int $port, string $expected): void
    {
        $url = new AssembledUrl(
            path: '/foo',
            host: 'example.com',
            scheme: $scheme,
            forceCanonical: true,
            port: $port,
        );

        $this->assertSame($expected, $url->toString());
    }

    /** @return iterable<non-empty-string, array{non-empty-string, int, string}> */
    public static function nonDefaultPortProvider(): iterable
    {
        yield 'http non-default port' => ['http', 8080, 'http://example.com:8080/foo'];
        yield 'https non-default port' => ['https', 8443, 'https://example.com:8443/foo'];
    }
}
