<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

class RouteMatchTest extends TestCase
{
    public function testParamsAreStored()
    {
        $match = new RouteMatch(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $match->getParams());
    }

    public function testMatchedRouteNameIsSet()
    {
        $match = new RouteMatch([]);
        $match->setMatchedRouteName('foo');

        $this->assertEquals('foo', $match->getMatchedRouteName());
    }

    public function testSetParam()
    {
        $match = new RouteMatch([]);
        $match->setParam('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $match->getParams());
    }

    public function testGetParam()
    {
        $match = new RouteMatch(['foo' => 'bar']);

        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testGetNonExistentParamWithoutDefault()
    {
        $match = new RouteMatch([]);

        $this->assertNull($match->getParam('foo'));
    }

    public function testGetNonExistentParamWithDefault()
    {
        $match = new RouteMatch([]);

        $this->assertEquals('bar', $match->getParam('foo', 'bar'));
    }
}
