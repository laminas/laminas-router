<?php

declare(strict_types=1);

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
        $routes = new RoutePluginManager(new ServiceManager(), [
            'invokables' => [
                'DummyRoute' => TestAsset\DummyRoute::class,
            ],
        ]);
        $route  = $routes->get('DummyRoute');

        $this->assertInstanceOf(TestAsset\DummyRoute::class, $route);
    }
}
