<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use ArrayIterator;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Builder\LiteralBuilder;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouterConfigFactory;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class RouterConfigFactoryTest extends TestCase
{
    private RouterConfigFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new RouterConfigFactory();
    }

    public function testInvokeWithoutConfigUsesDefaults(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $config = ($this->factory)($container);

        self::assertSame(TreeRouteStack::class, $config->routerClass);
        self::assertSame(LiteralBuilder::class, $config->routeBuilders['literal'] ?? null);
        self::assertSame(TranslatorInterface::class, $config->translator);
    }

    public function testInvokeAcceptsTraversableAndConfiguredValues(): void
    {
        $container = $this->createContainer(new ArrayIterator([
            'router' => [
                'router_class'   => 'CustomRouter',
                'route_builders' => ['custom' => 'CustomBuilder'],
                'translator'     => 'CustomTranslator',
            ],
        ]));

        $config = ($this->factory)($container);

        self::assertSame('CustomRouter', $config->routerClass);
        /** @var class-string $customBuilder */
        $customBuilder = 'CustomBuilder';
        self::assertSame($customBuilder, $config->routeBuilders['custom'] ?? null);
        self::assertSame('CustomTranslator', $config->translator);
    }

    public function testInvalidConfigServiceThrows(): void
    {
        $container = $this->createContainer('invalid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config service must return an array or Traversable');

        ($this->factory)($container);
    }

    public function testInvalidRouterConfigThrows(): void
    {
        $container = $this->createContainer(['router' => 'invalid']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config key "router" must be an array');

        ($this->factory)($container);
    }

    public function testInvalidRouteBuildersConfigThrows(): void
    {
        $container = $this->createContainer(['router' => ['route_builders' => 'invalid']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config key "router.route_builders"');

        ($this->factory)($container);
    }

    public function testInvalidTranslatorConfigThrows(): void
    {
        $container = $this->createContainer(['router' => ['translator' => 42]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config key "router.translator" must be a class name');

        ($this->factory)($container);
    }

    private function createContainer(mixed $config): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($config);

        return $container;
    }
}
