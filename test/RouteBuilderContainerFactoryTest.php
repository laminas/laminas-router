<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Builder\LiteralBuilder;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteBuilderContainerFactory;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouterConfig;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class RouteBuilderContainerFactoryTest extends TestCase
{
    private RouteBuilderContainerFactory $factory;

    public function setUp(): void
    {
        $this->factory = new RouteBuilderContainerFactory();
    }

    public function testInvokeWithoutConfigUsesDefaultBuilderMap(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeastOnce())
            ->method('has')
            ->willReturnCallback(static fn(string $id): bool => match ($id) {
                'config'              => false,
                RouterConfig::class   => true,
                LiteralBuilder::class => true,
                default               => false,
            });
        $container->expects($this->once())
            ->method('get')
            ->with(RouterConfig::class)
            ->willReturn(new RouterConfig(
                TreeRouteStack::class,
                ['literal' => LiteralBuilder::class],
                TranslatorInterface::class,
            ));

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('literal'));
    }

    public function testInvokeWithEmptyConfigUsesDefaultBuilderMap(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeastOnce())
            ->method('has')
            ->willReturnCallback(static fn(string $id): bool => match ($id) {
                'config'              => true,
                RouterConfig::class   => true,
                LiteralBuilder::class => true,
                default               => false,
            });
        $container->expects($this->once())
            ->method('get')
            ->with(RouterConfig::class)
            ->willReturn(new RouterConfig(
                TreeRouteStack::class,
                ['literal' => LiteralBuilder::class],
                TranslatorInterface::class,
            ));

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('literal'));
    }

    public function testInvokeWithRouteBuildersConfigUsesConfiguredMap(): void
    {
        $builder   = $this->createMock(RouteBuilderInterface::class);
        $container = $this->createContainerWithCustomBuilders($builder);

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('custom'));
        $this->assertSame($builder, $routeBuilders->get('custom'));
        $this->assertFalse($routeBuilders->has('literal'));
    }

    public function testInvokeWithTraversableConfigUsesConfiguredMap(): void
    {
        $builder   = $this->createMock(RouteBuilderInterface::class);
        $container = $this->createContainerWithCustomBuilders($builder);

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('custom'));
        $this->assertSame($builder, $routeBuilders->get('custom'));
        $this->assertFalse($routeBuilders->has('literal'));
    }

    public function testBuildUnknownTypeThrows(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn(string $id): bool => $id === RouterConfig::class);
        $container->expects($this->once())
            ->method('get')
            ->with(RouterConfig::class)
            ->willReturn(new RouterConfig(
                TreeRouteStack::class,
                ['literal' => LiteralBuilder::class],
                TranslatorInterface::class,
            ));

        $routeBuilders = $this->factory->__invoke($container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve route builder for type "unknown"');
        $routeBuilders->build(['type' => 'unknown']);
    }

    private function createContainerWithCustomBuilders(
        RouteBuilderInterface $builder,
    ): ContainerInterface&MockObject {
        /** @var class-string<RouteBuilderInterface> $customBuilderId */
        $customBuilderId = 'CustomBuilder';
        $container       = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeastOnce())
            ->method('has')
            ->willReturnCallback(static fn(string $id): bool => match ($id) {
                'config'        => true,
                RouterConfig::class => true,
                'CustomBuilder' => true,
                default         => false,
            });
        $container->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(static fn(string $id): mixed => match ($id) {
                RouterConfig::class => new RouterConfig(
                    TreeRouteStack::class,
                    ['custom' => $customBuilderId],
                    TranslatorInterface::class,
                ),
                'CustomBuilder' => $builder,
                default         => null,
            });

        return $container;
    }
}
