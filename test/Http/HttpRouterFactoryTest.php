<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouterFactory;
use LaminasTest\Router\RouterFactoryTest as TestCase;

final class HttpRouterFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->factory = new HttpRouterFactory();
    }
}
