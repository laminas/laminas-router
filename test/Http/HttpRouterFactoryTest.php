<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\RoutePluginManager;
use LaminasTest\Router\RouterFactoryTest as TestCase;

class HttpRouterFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'RoutePluginManager' => static fn($services) => new RoutePluginManager($services),
            ],
        ];

        $this->factory = new HttpRouterFactory();
    }
}
