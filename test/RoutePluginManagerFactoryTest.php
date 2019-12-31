<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Router;

use Interop\Container\ContainerInterface;
use Laminas\Router\RouteInterface;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RoutePluginManagerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

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
