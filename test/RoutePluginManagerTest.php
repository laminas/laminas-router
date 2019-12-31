<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Router;

use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class RoutePluginManagerTest extends TestCase
{
    public function testLoadNonExistentRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager());
        $this->expectException(ServiceNotFoundException::class);
        $routes->get('foo');
    }

    public function testCanLoadAnyRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager(), ['invokables' => [
            'DummyRoute' => TestAsset\DummyRoute::class,
        ]]);
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf(TestAsset\DummyRoute::class, $route);
    }
}
