<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\PriorityList;
use PHPUnit\Framework\TestCase;

class PriorityListTest extends TestCase
{
    /**
     * @var PriorityList
     */
    protected $list;

    public function setUp(): void
    {
        $this->list = new PriorityList();
    }

    public function testInsert()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);

        $this->assertCount(1, $this->list);

        foreach ($this->list as $key => $value) {
            $this->assertEquals('foo', $key);
        }
    }

    public function testRemove()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);

        $this->assertCount(2, $this->list);

        $this->list->remove('foo');

        $this->assertCount(1, $this->list);
    }

    public function testRemovingNonExistentRouteDoesNotYieldError()
    {
        $this->expectNotToPerformAssertions();
        $this->list->remove('foo');
    }

    public function testClear()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);

        $this->assertCount(2, $this->list);

        $this->list->clear();

        $this->assertCount(0, $this->list);
        $this->assertFalse($this->list->current());
    }

    public function testGet()
    {
        $route = new TestAsset\DummyRoute();

        $this->list->insert('foo', $route, 0);

        $this->assertEquals($route, $this->list->get('foo'));
        $this->assertNull($this->list->get('bar'));
    }

    public function testLIFOOnly()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute(), 0);

        $order = [];

        foreach ($this->list as $key => $value) {
            $orders[] = $key;
        }

        $this->assertEquals(['baz', 'bar', 'foo'], $orders);
    }

    public function testPriorityOnly()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 1);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute(), 2);

        $order = [];

        foreach ($this->list as $key => $value) {
            $orders[] = $key;
        }

        $this->assertEquals(['baz', 'foo', 'bar'], $orders);
    }

    public function testLIFOWithPriority()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute(), 1);

        $order = [];

        foreach ($this->list as $key => $value) {
            $orders[] = $key;
        }

        $this->assertEquals(['baz', 'bar', 'foo'], $orders);
    }

    public function testPriorityWithNegativesAndNull()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), null);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 1);
        $this->list->insert('baz', new TestAsset\DummyRoute(), -1);

        $order = [];

        foreach ($this->list as $key => $value) {
            $orders[] = $key;
        }

        $this->assertEquals(['bar', 'foo', 'baz'], $orders);
    }
}
