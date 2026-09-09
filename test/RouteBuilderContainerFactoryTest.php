<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use ArrayIterator;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Builder\LiteralBuilder;
use Laminas\Router\RouteBuilderContainerFactory;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;
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
                LiteralBuilder::class => true,
                default               => false,
            });
        $container->expects($this->never())->method('get');

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
                LiteralBuilder::class => true,
                default               => false,
            });
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn([]);

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('literal'));
    }

    public function testInvokeWithRouteBuildersConfigUsesConfiguredMap(): void
    {
        $builder   = $this->createMock(RouteBuilderInterface::class);
        $container = $this->createContainerWithCustomBuilders($builder, [
            'router' => [
                'route_builders' => [
                    'custom' => 'CustomBuilder',
                ],
            ],
        ]);

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('custom'));
        $this->assertSame($builder, $routeBuilders->get('custom'));
        $this->assertFalse($routeBuilders->has('literal'));
    }

    public function testInvokeWithTraversableConfigUsesConfiguredMap(): void
    {
        $builder   = $this->createMock(RouteBuilderInterface::class);
        $container = $this->createContainerWithCustomBuilders($builder, new ArrayIterator([
            'router' => [
                'route_builders' => [
                    'custom' => 'CustomBuilder',
                ],
            ],
        ]));

        $routeBuilders = $this->factory->__invoke($container);

        $this->assertInstanceOf(RouteBuilderContainerInterface::class, $routeBuilders);
        $this->assertTrue($routeBuilders->has('custom'));
        $this->assertSame($builder, $routeBuilders->get('custom'));
        $this->assertFalse($routeBuilders->has('literal'));
    }

    public function testBuildUnknownTypeThrows(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->expects($this->never())->method('get');

        $routeBuilders = $this->factory->__invoke($container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve route builder for type "unknown"');
        $routeBuilders->build(['type' => 'unknown']);
    }

    /**
     * @param array<string, mixed>|ArrayIterator<string, mixed> $config
     */
    private function createContainerWithCustomBuilders(
        RouteBuilderInterface $builder,
        array|ArrayIterator $config,
    ): ContainerInterface&MockObject {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeastOnce())
            ->method('has')
            ->willReturnCallback(static fn(string $id): bool => match ($id) {
                'config'        => true,
                'CustomBuilder' => true,
                default         => false,
            });
        $container->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(static fn(string $id): mixed => match ($id) {
                'config'        => $config,
                'CustomBuilder' => $builder,
                default         => null,
            });

        return $container;
    }
}
