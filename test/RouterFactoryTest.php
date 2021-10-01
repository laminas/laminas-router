<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RouterFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class RouterFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'HttpRouter'         => HttpRouterFactory::class,
                'RoutePluginManager' => function ($services) {
                    return new RoutePluginManager($services);
                },
            ],
        ];

        $this->factory  = new RouterFactory();
    }

    public function testFactoryCanCreateRouterBasedOnConfiguredName()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [ 'config' => [
                'router' => [
                    'router_class' => TestAsset\Router::class,
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [ 'config' => [
                'router' => [
                    'router_class' => TestAsset\Router::class,
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }
}
