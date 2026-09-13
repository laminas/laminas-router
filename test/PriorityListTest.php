<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\PriorityList;
use PHPUnit\Framework\TestCase;

use function array_keys;

final class PriorityListTest extends TestCase
{
    private PriorityList $list;

    public function setUp(): void
    {
        $this->list = new PriorityList();
    }

    public function testInsert(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);

        static::assertCount(1, $this->list->getAsArray());
        static::assertSame(['foo'], array_keys([...$this->list->getAsArray()]));
    }

    public function testInsertDuplicateRouteThrowsException(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route with name "foo" already exists');
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
    }

    public function testInsertAfterRemoveSucceeds(): void
    {
        $route = new TestAsset\DummyRoute('foo');

        $this->list->insert('foo', $route, 0);
        $this->list->remove('foo');
        $this->list->insert('foo', $route, 0);

        static::assertSame($route, $this->list->get('foo'));
    }

    public function testRemove(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 0);

        static::assertCount(2, $this->list->getAsArray());

        $this->list->remove('foo');

        static::assertCount(1, $this->list->getAsArray());
    }

    public function testRemovingNonExistentRouteDoesNotYieldError(): void
    {
        $this->expectNotToPerformAssertions();
        $this->list->remove('foo');
    }

    public function testClear(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 0);

        static::assertCount(2, $this->list->getAsArray());

        $this->list->clear();

        static::assertCount(0, $this->list->getAsArray());
    }

    public function testGet(): void
    {
        $route = new TestAsset\DummyRoute('foo');

        $this->list->insert('foo', $route, 0);

        static::assertSame($route, $this->list->get('foo'));
        static::assertNull($this->list->get('bar'));
    }

    public function testLIFOOnly(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute('baz'), 0);

        static::assertSame(['baz', 'bar', 'foo'], array_keys([...$this->list->getAsArray()]));
    }

    public function testPriorityOnly(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 1);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute('baz'), 2);

        static::assertSame(['baz', 'foo', 'bar'], array_keys([...$this->list->getAsArray()]));
    }

    public function testLIFOWithPriority(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute('baz'), 1);

        static::assertSame(['baz', 'bar', 'foo'], array_keys([...$this->list->getAsArray()]));
    }

    public function testPriorityWithNegativesAndNull(): void
    {
        $this->list->insert('foo', new TestAsset\DummyRoute('foo'), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute('bar'), 1);
        $this->list->insert('baz', new TestAsset\DummyRoute('baz'), -1);

        static::assertSame(['bar', 'foo', 'baz'], array_keys([...$this->list->getAsArray()]));
    }
}
