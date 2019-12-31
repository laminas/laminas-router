<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Http\Request;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Scheme;
use Laminas\Stdlib\Request as BaseRequest;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

class SchemeTest extends TestCase
{
    public function testMatching()
    {
        $request = new Request();
        $request->setUri('https://example.com/');

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testNoMatchingOnDifferentScheme()
    {
        $request = new Request();
        $request->setUri('http://example.com/');

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

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Scheme('https');
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams()
    {
        $route = new Scheme('https');
        $route->assemble(['foo' => 'bar']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
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
