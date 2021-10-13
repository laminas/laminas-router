<?php

/**
 * @see       https://github.com/laminas/laminas-router for the canonical source repository
 * @copyright https://github.com/laminas/laminas-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-router/blob/master/LICENSE.md New BSD License
 */

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

        $list = iterator_to_array($this->list);
        $this->assertSame(['foo'], array_keys($list));
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

        $list = iterator_to_array($this->list);

        $this->assertEquals(['baz', 'bar', 'foo'], array_keys($list));
    }

    public function testPriorityOnly()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 1);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute(), 2);

        $list = iterator_to_array($this->list);

        $this->assertEquals(['baz', 'foo', 'bar'], array_keys($list));
    }

    public function testLIFOWithPriority()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), 0);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 0);
        $this->list->insert('baz', new TestAsset\DummyRoute(), 1);

        $list = iterator_to_array($this->list);

        $this->assertEquals(['baz', 'bar', 'foo'], array_keys($list));
    }

    public function testPriorityWithNegativesAndNull()
    {
        $this->list->insert('foo', new TestAsset\DummyRoute(), null);
        $this->list->insert('bar', new TestAsset\DummyRoute(), 1);
        $this->list->insert('baz', new TestAsset\DummyRoute(), -1);

        $list = iterator_to_array($this->list);

        $this->assertEquals(['bar', 'foo', 'baz'], array_keys($list));
    }
}
