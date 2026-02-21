<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\TestAsset;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_merge_recursive;

final class HttpRouterFactoryTest extends TestCase
{
    private array $defaultServiceConfig;

    private HttpRouterFactory $factory;

    public function setUp(): void
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'RoutePluginManager' => static fn($services) => new RoutePluginManager($services),
            ],
        ];

        $this->factory = new HttpRouterFactory();
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
