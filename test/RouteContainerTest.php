<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Container\SimpleRouteContainer;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function iterator_to_array;

final class RouteContainerTest extends TestCase
{
    private SimpleRouteContainer $container;

    public function setUp(): void
    {
        $this->container = new SimpleRouteContainer();
    }

    public function testInsert(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute());

        $this->assertCount(1, $this->container);

        $list = iterator_to_array($this->container);
        $this->assertSame(['foo'], array_keys($list));
    }

    public function testRemove(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute());
        $this->container->insert('bar', new TestAsset\DummyRoute());

        $this->assertCount(2, $this->container);

        $this->container->remove('foo');

        $this->assertCount(1, $this->container);
    }

    public function testRemovingNonExistentRouteDoesNotYieldError(): void
    {
        $this->expectNotToPerformAssertions();
        $this->container->remove('foo');
    }

    public function testClear(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute());
        $this->container->insert('bar', new TestAsset\DummyRoute());

        $this->assertCount(2, $this->container);

        $this->container->clear();

        $this->assertCount(0, $this->container);
        $this->assertFalse($this->container->valid());
    }

    public function testGet(): void
    {
        $route = new TestAsset\DummyRoute();

        $this->container->insert('foo', $route);

        $this->assertEquals($route, $this->container->get('foo'));
        $this->assertNull($this->container->get('bar'));
    }

    public function testLIFOOnly(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute());
        $this->container->insert('bar', new TestAsset\DummyRoute());
        $this->container->insert('baz', new TestAsset\DummyRoute());

        $list = iterator_to_array($this->container);

        $this->assertEquals(['baz', 'bar', 'foo'], array_keys($list));
    }

    public function testPriorityOnly(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute(), 1);
        $this->container->insert('bar', new TestAsset\DummyRoute());
        $this->container->insert('baz', new TestAsset\DummyRoute(), 2);

        $list = iterator_to_array($this->container);

        $this->assertEquals(['baz', 'foo', 'bar'], array_keys($list));
    }

    public function testLIFOWithPriority(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute());
        $this->container->insert('bar', new TestAsset\DummyRoute());
        $this->container->insert('baz', new TestAsset\DummyRoute(), 1);

        $list = iterator_to_array($this->container);

        $this->assertEquals(['baz', 'bar', 'foo'], array_keys($list));
    }

    public function testPriorityWithNegativesAndNull(): void
    {
        $this->container->insert('foo', new TestAsset\DummyRoute(), null);
        $this->container->insert('bar', new TestAsset\DummyRoute(), 1);
        $this->container->insert('baz', new TestAsset\DummyRoute(), -1);

        $list = iterator_to_array($this->container);

        $this->assertEquals(['bar', 'foo', 'baz'], array_keys($list));
    }
}
