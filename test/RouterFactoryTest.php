<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\RouterFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_merge_recursive;

class RouterFactoryTest extends TestCase
{
    protected array $defaultServiceConfig;

    protected RouterFactory|HttpRouterFactory $factory;

    public function setUp(): void
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'HttpRouter'         => HttpRouterFactory::class,
                'RoutePluginManager' => static fn($services) => new RoutePluginManager($services),
            ],
        ];

        $this->factory = new RouterFactory();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryCanCreateRouterBasedOnConfiguredName(): void
    {
        $config   = array_merge_recursive($this->defaultServiceConfig, [
            'services' => [
                'config' => [
                    'router' => [
                        'router_class' => TestAsset\Router::class,
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager($config);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent(): void
    {
        $config   = array_merge_recursive($this->defaultServiceConfig, [
            'services' => [
                'config' => [
                    'router' => [
                        'router_class' => TestAsset\Router::class,
                    ],
                ],
            ],
        ]);
        $services = new ServiceManager($config);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf(TestAsset\Router::class, $router);
    }
}
