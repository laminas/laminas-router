<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Router;

use PHPUnit\Framework\TestCase;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceManager;

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
            'DummyRoute' => 'ZendTest\Router\TestAsset\DummyRoute',
        ]]);
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf('ZendTest\Router\TestAsset\DummyRoute', $route);
    }
}
