<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Router\Http;

use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Router\Http\Scheme;
use Zend\Stdlib\Request as BaseRequest;
use Zend\Uri\Http as HttpUri;
use ZendTest\Router\FactoryTester;

class SchemeTest extends TestCase
{
    public function testMatching()
    {
        $request = new Request();
        $request->setUri('https://example.com/');

        $route = new Scheme('https');
        $match = $route->match($request);

        $this->assertInstanceOf('Zend\Router\Http\RouteMatch', $match);
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
            'Zend\Router\Http\Scheme',
            [
                'scheme' => 'Missing "scheme" in options array',
            ],
            [
                'scheme' => 'http',
            ]
        );
    }
}
