<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\SimpleRouteStack;
use Laminas\Router\SimpleRouteStackFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class SimpleRouteStackFactoryTest extends TestCase
{
    public function testFactoryCreatesSimpleRouteStack(): void
    {
        $routeBuilderContainer = $this->createMock(RouteBuilderContainerInterface::class);
        $container             = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RouteBuilderContainerInterface::class)
            ->willReturn($routeBuilderContainer);

        $stack = (new SimpleRouteStackFactory())($container);

        $this->assertInstanceOf(SimpleRouteStack::class, $stack);
    }
}
