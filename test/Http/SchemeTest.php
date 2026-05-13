<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Scheme;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

final class SchemeTest extends TestCase
{
    public function testMatching(): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('https://example.com/'));

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
    }

    public function testNoMatchingOnDifferentScheme(): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/'));

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertNull($match);
    }

    public function testAssembling(): void
    {
        $uri    = new Uri();
        $route  = new Scheme('https');
        $result = $route->assemble([], ['uri' => $uri]);

        $this->assertEquals('', (string) $result);
        $this->assertSame('https', $result->scheme);
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = new Scheme('https');
        $request = new Request();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams(): void
    {
        $route = new Scheme('https');
        $route->assemble(['foo' => 'bar']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester();
        $tester->testFactory(
            Scheme::class,
            [
                'scheme' => 'Missing "scheme" in options array',
            ],
            [
                'scheme' => 'http',
            ]
        );
    }
}
