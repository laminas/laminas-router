<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouteMatch;
use PHPUnit\Framework\TestCase;

final class RouteMatchTest extends TestCase
{
    public function testParamsAreStored(): void
    {
        $match = new HttpRouteMatch(['foo' => 'bar'], 'foo');

        static::assertSame(['foo' => 'bar'], $match->getParams());
    }

    public function testLengthIsStored(): void
    {
        $match = new HttpRouteMatch([], 'foo', 10);

        static::assertSame(10, $match->getLength());
    }

    public function testLengthIsMerged(): void
    {
        $match = new HttpRouteMatch([], 'foo', 10);
        $match = $match->merge(new HttpRouteMatch([], 'foo', 5));

        static::assertSame(15, $match->getLength());
    }

    public function testMatchedRouteNameIsSet(): void
    {
        $match = new HttpRouteMatch([], 'foo');

        static::assertSame('foo', $match->getMatchedRouteName());
    }

    public function testMatchedRouteNameIsPrependedWhenAlreadySet(): void
    {
        $match = new HttpRouteMatch([], 'foo');
        $match = $match->merge(new HttpRouteMatch([], 'bar'));

        static::assertSame('foo/bar', $match->getMatchedRouteName());
    }

    public function testMatchedRouteNameIsOverriddenOnMerge(): void
    {
        $match    = new HttpRouteMatch([], 'foo');
        $subMatch = new HttpRouteMatch([], 'bar');

        $match = $match->merge($subMatch);

        static::assertSame('foo/bar', $match->getMatchedRouteName());
    }
}
