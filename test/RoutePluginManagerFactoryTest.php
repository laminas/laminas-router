<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Router;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Router\RouteInterface;
use Zend\Router\RoutePluginManager;
use Zend\Router\RoutePluginManagerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

class RoutePluginManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new RoutePluginManagerFactory();
    }

    public function testInvocationReturnsAPluginManager()
    {
        $plugins = $this->factory->__invoke($this->container->reveal(), RoutePluginManager::class);
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
    }

    public function testCreateServiceReturnsAPluginManager()
    {
        $container = $this->prophesize(ServiceLocatorInterface::class);
        $container->willImplement(ContainerInterface::class);

        $plugins = $this->factory->createService($container->reveal());
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
    }

    public function testInvocationCanProvideOptionsToThePluginManager()
    {
        $options = ['factories' => [
            'TestRoute' => function ($container) {
                return $this->prophesize(RouteInterface::class)->reveal();
            },
        ]];
        $plugins = $this->factory->__invoke(
            $this->container->reveal(),
            RoutePluginManager::class,
            $options
        );
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
        $route = $plugins->get('TestRoute');
        $this->assertInstanceOf(RouteInterface::class, $route);
    }
}
