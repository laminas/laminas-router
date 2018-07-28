<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Router\Http;

use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Router\Http\Placeholder;
use Zend\Router\Http\RouteMatch;
use ZendTest\Router\FactoryTester;

class PlaceholderTest extends TestCase
{
    public function testMatch()
    {
        $route = new Placeholder([]);

        $request = new Request();
        $request->setUri('http://example.com/');
        $match = $route->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testAssembling()
    {
        $route = new Placeholder([]);
        $this->assertEquals('', $route->assemble());
    }

    public function testGetAssembledParams()
    {
        $route = new Placeholder([]);
        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(Placeholder::class, [], []);
    }
}
