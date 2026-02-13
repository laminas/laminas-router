<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Scheme;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\TestCase;

final class SchemeTest extends TestCase
{
    public function testMatching(): void
    {
        $request = new MockServerRequest(new MockUri('https://example.com/'));

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testNoMatchingOnDifferentScheme(): void
    {
        $request = new MockServerRequest(new MockUri('http://example.com/'));

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertNull($match);
    }

    public function testAssembling()
    {
        $uri   = new HttpUri();
        $route = new Scheme('https');
        $path  = $route->assemble([], ['uri' => $uri]);

        $this->assertEquals('', $path);
        $this->assertEquals('https', $uri->getScheme());
    }

    public function testGetAssembledParams(): void
    {
        $route = new Scheme('https');
        $route->assemble(['foo' => 'bar']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Scheme::class,
            [
                'scheme' => 'Missing "scheme" option',
            ],
            [
                'scheme' => 'http',
            ]
        );
    }
}
