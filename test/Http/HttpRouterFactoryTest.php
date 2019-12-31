<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\RoutePluginManager;
use LaminasTest\Router\RouterFactoryTest as TestCase;

class HttpRouterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'RoutePluginManager' => function ($services) {
                    return new RoutePluginManager($services);
                },
            ],
        ];

        $this->factory  = new HttpRouterFactory();
    }
}
