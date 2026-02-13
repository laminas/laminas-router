<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\RouteMatch;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
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
                new Literal('/foo'),
                '/foo',
                null,
                true,
            ],
            'no-match-without-leading-slash'  => [
                new Literal('foo'),
                '/foo',
                null,
                false,
            ],
            'no-match-with-trailing-slash'    => [
                new Literal('/foo'),
                '/foo/',
                null,
                false,
            ],
            'offset-skips-beginning'          => [
                new Literal('foo'),
                '/foo',
                1,
                true,
            ],
            'offset-enables-partial-matching' => [
                new Literal('/foo'),
                '/foo/bar',
                0,
                true,
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(Literal $route, string $path, ?int $offset, bool $shouldMatch): void
    {
        $request = new MockServerRequest(new MockUri('http://example.com' . $path));
        $match   = $route->match($request, $offset);

        if (! $shouldMatch) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(RouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }
        }
    }

    #[DataProvider('routeProvider')]
    public function testAssembling(Literal $route, string $path, ?int $offset, bool $shouldMatch): void
    {
        if (! $shouldMatch) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $result = $route->assemble();

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testGetAssembledParams(): void
    {
        $route  = new Literal('/foo');
        $result = $route->assemble(['foo' => 'bar']);

        $this->assertEquals('/foo', $result);
        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Literal::class,
            [
                'route' => 'Missing "route" option',
            ],
            [
                'route' => '/foo',
            ]
        );
    }

    #[Group('Laminas-436')]
    public function testEmptyLiteral(): void
    {
        $request = new MockServerRequest();
        $route   = new Literal('');
        $this->assertNull($route->match($request, 0));
    }
}
