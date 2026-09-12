<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\Http\TreeRouteStackFactory;
use Laminas\Router\RouteBuilderContainerInterface;
use LaminasTest\Router\RouteBuilderContainerTestHelper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class TreeRouteStackFactoryTest extends TestCase
{
    public function testFactoryCreatesTreeRouteStack(): void
    {
        $routeBuilderContainer = $this->createMock(RouteBuilderContainerInterface::class);
        $container             = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RouteBuilderContainerInterface::class)
            ->willReturn($routeBuilderContainer);

        $stack = (new TreeRouteStackFactory())($container);

        $this->assertInstanceOf(TreeRouteStack::class, $stack);
    }

    public function testFactoryIsConfiguredInServiceManager(): void
    {
        $services = RouteBuilderContainerTestHelper::createServiceManager();

        $this->assertInstanceOf(TreeRouteStack::class, $services->get(TreeRouteStack::class));
    }
}
