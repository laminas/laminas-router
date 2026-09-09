<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\ConfigProvider;
use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouterFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class RouterFactoryTest extends TestCase
{
    protected HttpRouterFactory|RouterFactory $factory;

    public function setUp(): void
    {
        $this->factory = new RouterFactory();
    }

    public function testFactoryCanCreateRouterBasedOnConfiguredName(): void
    {
        $services = RouteBuilderContainerTestHelper::createServiceManager(
            routerConfig: ['router_class' => TestAsset\Router::class],
        );

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent(): void
    {
        $services = RouteBuilderContainerTestHelper::createServiceManager(
            routerConfig: ['router_class' => TestAsset\Router::class],
        );

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }

    public function testDefaultConfig(): void
    {
        $services = new ServiceManager((new ConfigProvider())->getDependencyConfig());

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TreeRouteStack::class, $router);
    }
}
