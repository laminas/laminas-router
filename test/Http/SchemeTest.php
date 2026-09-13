<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Scheme;
use LaminasTest\Router\BuilderTester;
use PHPUnit\Framework\TestCase;

final class SchemeTest extends TestCase
{
    public function testMatching(): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('https://example.com/'));

        $route = new Scheme('foo', 'https');
        $match = $route->match($request);

        static::assertInstanceOf(HttpRouteMatch::class, $match);
    }

    public function testNoMatchingOnDifferentScheme(): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/'));

        $route = new Scheme('foo', 'https');
        $match = $route->match($request);

        static::assertNull($match);
    }

    public function testAssembling(): void
    {
        $uri    = new Uri();
        $route  = new Scheme('foo', 'https');
        $result = $route->assemble([], ['uri' => $uri]);

        static::assertSame('', $result->toString());
        static::assertSame('https', $result->scheme);
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = new Scheme('foo', 'https');
        $request = new Request();

        static::assertNull($route->match($request));
    }

    public function testGetAssembledParams(): void
    {
        $route = new Scheme('foo', 'https');
        static::assertSame([], $route->assemble(['foo' => 'bar'])->assembledParams);
    }

    public function testFactory(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Scheme::class,
            [
                'scheme' => 'Missing "scheme" in options array',
                'name'   => 'Missing "name" in options array',
            ],
            [
                'scheme' => 'http',
                'name'   => 'foo',
            ]
        );
    }
}
