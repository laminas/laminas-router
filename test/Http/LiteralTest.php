<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Literal;
use LaminasTest\Router\BuilderTester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function strlen;
use function strpos;

final class LiteralTest extends TestCase
{
    /**
     * @psalm-return array<string, array{
     *     0: Literal,
     *     1: string,
     *     2: null|int,
     *     3: bool
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                    => [
                new Literal('foo', '/foo'),
                '/foo',
                null,
                true,
            ],
            'no-match-without-leading-slash'  => [
                new Literal('foo', 'foo'),
                '/foo',
                null,
                false,
            ],
            'no-match-with-trailing-slash'    => [
                new Literal('foo', '/foo'),
                '/foo/',
                null,
                false,
            ],
            'offset-skips-beginning'          => [
                new Literal('foo', 'foo'),
                '/foo',
                1,
                true,
            ],
            'offset-enables-partial-matching' => [
                new Literal('foo', '/foo'),
                '/foo/bar',
                0,
                true,
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(Literal $route, string $path, int|null $offset, bool $shouldMatch): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com' . $path));
        $match   = $route->match($request, $offset);

        if (! $shouldMatch) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }
        }
    }

    #[DataProvider('routeProvider')]
    public function testAssembling(Literal $route, string $path, int|null $offset, bool $shouldMatch): void
    {
        if (! $shouldMatch) {
            // Data which will not match are not tested for assembling.
            $this->expectNotToPerformAssertions();
            return;
        }

        $result = $route->assemble();

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result->toString(), $offset));
        } else {
            $this->assertEquals($path, $result->toString());
        }
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = new Literal('foo', '/foo');
        $request = new Request();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams(): void
    {
        $route = new Literal('foo', '/foo');
        $this->assertEquals([], $route->assemble(['foo' => 'bar'])->assembledParams);
    }

    public function testFactory(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Literal::class,
            [
                'route' => 'Missing "route" in options array',
                'name'  => 'Missing "name" in options array',
            ],
            [
                'route' => '/foo',
                'name'  => 'foo',
            ]
        );
    }

    #[Group('Laminas-436')]
    public function testEmptyLiteral(): void
    {
        $request = new Request();
        $route   = new Literal('foo', '');
        $this->assertNull($route->match($request, 0));
    }

    public function testMatchWithOffsetBeyondPathLengthReturnsNull(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo'));

        $this->assertNull((new Literal('foo', '/foo'))->match($request, 10));
    }

    public function testMatchWithNegativeOffsetReturnsNull(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo'));

        $this->assertNull((new Literal('foo', '/foo'))->match($request, -1));
    }

    public function testMatchWithOffsetMisalignedReturnsNull(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/x/foo'));

        $this->assertNull((new Literal('foo', 'foo'))->match($request, 1));
    }

    public function testMatchWithOffsetReturnsSegmentLength(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo'));
        $match   = (new Literal('foo', 'foo'))->match($request, 1);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame(3, $match->getLength());
    }

    public function testMatchWithOffsetAtPathLengthReturnsNull(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo'));

        $this->assertNull((new Literal('foo', '/foo'))->match($request, 4));
    }

    public function testMatchWithOffsetEnablesMatchAtLastCharacter(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo'));
        $match   = (new Literal('foo', 'o'))->match($request, 3);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame(1, $match->getLength());
    }
}
