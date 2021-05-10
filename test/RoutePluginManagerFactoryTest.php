<?php

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
    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;
    /**
     * @var RoutePluginManagerFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new RoutePluginManagerFactory();
    }

    public function testInvocationReturnsAPluginManager()
    {
        $plugins = $this->factory->__invoke($this->container, RoutePluginManager::class);
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
    }

    public function testCreateServiceReturnsAPluginManager()
    {
        $container = $this->createMock(ServiceLocatorInterface::class);

        $plugins = $this->factory->createService($container);
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
    }

    public function testInvocationCanProvideOptionsToThePluginManager()
    {
        $options = ['factories' => [
            'TestRoute' => function ($container) {
                return $this->createMock(RouteInterface::class);
            },
        ]];
        $plugins = $this->factory->__invoke(
            $this->container,
            RoutePluginManager::class,
            $options
        );
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
        $route = $plugins->get('TestRoute');
        $this->assertInstanceOf(RouteInterface::class, $route);
    }
}
