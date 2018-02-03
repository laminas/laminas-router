<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Router\Http;

use Zend\Router\Http\HttpRouterFactory;
use Zend\Router\RoutePluginManager;
use ZendTest\Router\RouterFactoryTest as TestCase;

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
