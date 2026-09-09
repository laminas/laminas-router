<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Builder\HostnameBuilder;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\HttpRouteMatch;
use LaminasTest\Router\BuilderTester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Throwable;

final class HostnameTest extends TestCase
{
    /**
     * @psalm-return array<string, array{
     *     0: Hostname,
     *     1: string,
     *     2: null|array<non-empty-string, null|non-empty-string>
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                                                   => [
                new Hostname('foo', ':foo.example.com'),
                'bar.example.com',
                ['foo' => 'bar'],
            ],
            'no-match-on-different-hostname'                                 => [
                new Hostname('foo', 'foo.example.com'),
                'bar.example.com',
                null,
            ],
            'no-match-with-different-number-of-parts'                        => [
                new Hostname('foo', 'foo.example.com'),
                'example.com',
                null,
            ],
            'no-match-with-different-number-of-parts-2'                      => [
                new Hostname('foo', 'example.com'),
                'foo.example.com',
                null,
            ],
            'match-overrides-default'                                        => [
                new Hostname('foo', ':foo.example.com', [], ['foo' => 'baz']),
                'bat.example.com',
                ['foo' => 'bat'],
            ],
            'constraints-prevent-match'                                      => [
                new Hostname('foo', ':foo.example.com', ['foo' => '\d+']),
                'bar.example.com',
                null,
            ],
            'constraints-allow-match'                                        => [
                new Hostname('foo', ':foo.example.com', ['foo' => '\d+']),
                '123.example.com',
                ['foo' => '123'],
            ],
            'constraints-allow-match-2'                                      => [
                new Hostname(
                    'foo',
                    'www.:domain.com',
                    ['domain' => '(mydomain|myaltdomain1|myaltdomain2)'],
                    ['domain' => 'mydomain']
                ),
                'www.mydomain.com',
                ['domain' => 'mydomain'],
            ],
            'optional-subdomain'                                             => [
                new Hostname('foo', '[:foo.]example.com'),
                'bar.example.com',
                ['foo' => 'bar'],
            ],
            'two-optional-subdomain'                                         => [
                new Hostname('foo', '[:foo.][:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'missing-optional-subdomain'                                     => [
                new Hostname('foo', '[:foo.]example.com'),
                'example.com',
                ['foo' => null],
            ],
            'one-of-two-missing-optional-subdomain'                          => [
                new Hostname('foo', '[:foo.][:bar.]example.com'),
                'bat.example.com',
                ['foo' => 'bat'],
            ],
            'two-missing-optional-subdomain'                                 => [
                new Hostname('foo', '[:foo.][:bar.]example.com'),
                'example.com',
                ['foo' => null, 'bar' => null],
            ],
            'two-optional-subdomain-nested'                                  => [
                new Hostname('foo', '[[:foo.]:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'one-of-two-missing-optional-subdomain-nested'                   => [
                new Hostname('foo', '[[:foo.]:bar.]example.com'),
                'bat.example.com',
                ['foo' => null, 'bar' => 'bat'],
            ],
            'two-missing-optional-subdomain-nested'                          => [
                new Hostname('foo', '[[:foo.]:bar.]example.com'),
                'example.com',
                ['foo' => null, 'bar' => null],
            ],
            'no-match-on-different-hostname-and-optional-subdomain'          => [
                new Hostname('foo', '[:foo.]example.com'),
                'bar.test.com',
                null,
            ],
            'no-match-with-different-number-of-parts-and-optional-subdomain' => [
                new Hostname('foo', '[:foo.]example.com'),
                'bar.baz.example.com',
                null,
            ],
            'match-overrides-default-optional-subdomain'                     => [
                new Hostname('foo', '[:foo.]:bar.example.com', [], ['bar' => 'baz']),
                'bat.qux.example.com',
                ['foo' => 'bat', 'bar' => 'qux'],
            ],
            'constraints-prevent-match-optional-subdomain'                   => [
                new Hostname('foo', '[:foo.]example.com', ['foo' => '\d+']),
                'bar.example.com',
                null,
            ],
            'constraints-allow-match-optional-subdomain'                     => [
                new Hostname('foo', '[:foo.]example.com', ['foo' => '\d+']),
                '123.example.com',
                ['foo' => '123'],
            ],
            'middle-subdomain-optional'                                      => [
                new Hostname('foo', ':foo.[:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'missing-middle-subdomain-optional'                              => [
                new Hostname('foo', ':foo.[:bar.]example.com'),
                'baz.example.com',
                ['foo' => 'baz'],
            ],
            'non-standard-delimeter'                                         => [
                new Hostname('foo', 'user-:username.example.com'),
                'user-jdoe.example.com',
                ['username' => 'jdoe'],
            ],
            'non-standard-delimeter-optional'                                => [
                new Hostname('foo', ':page{-}[-:username].example.com'),
                'article-jdoe.example.com',
                ['page' => 'article', 'username' => 'jdoe'],
            ],
            'missing-non-standard-delimeter-optional'                        => [
                new Hostname('foo', ':page{-}[-:username].example.com'),
                'article.example.com',
                ['page' => 'article'],
            ],
        ];
    }

    /**
     * @param array<non-empty-string, non-empty-string|null>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testMatching(Hostname $route, string $hostname, ?array $params = null): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://' . $hostname . '/'));
        $match   = $route->match($request);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string|null>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testAssembling(Hostname $route, string $hostname, ?array $params = null): void
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            $this->expectNotToPerformAssertions();
            return;
        }

        $uri    = new Uri();
        $result = $route->assemble($params, ['uri' => $uri]);

        $this->assertEquals('', $result->toString());
        $this->assertSame($hostname, $result->host);
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = new Hostname('foo', 'example.com');
        $request = new Request();

        $this->assertNull($route->match($request));
    }

    public function testNoMatchWithRelativeUri(): void
    {
        $route   = new Hostname('foo', 'example.com');
        $request = new Request();
        $request = $request->withUri(new Uri('/relative/path'));

        self::assertNull($route->match($request));
    }

    public function testNoMatchWithPlaceholderOnRelativeUri(): void
    {
        $route   = new Hostname('foo', ':domain');
        $request = new Request();
        $request = $request->withUri(new Uri('/relative/path'));

        self::assertNull($route->match($request));
    }

    public function testMatchesRelativeUriWithFullyOptionalDefinition(): void
    {
        $route   = new Hostname('foo', '[:domain]');
        $request = new Request();
        $request = $request->withUri(new Uri('/relative/path'));

        $match = $route->match($request);
        self::assertInstanceOf(HttpRouteMatch::class, $match);
        self::assertArrayNotHasKey('domain', $match->getParams());
    }

    public function testAssemblingWithMissingParameter(): void
    {
        $route = new Hostname('foo', ':foo.example.com');
        $uri   = new Uri();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "foo"');
        $route->assemble([], ['uri' => $uri]);
    }

    public function testGetAssembledParams(): void
    {
        $route = new Hostname('foo', ':foo.example.com');
        $uri   = new Uri();
        $this->assertEquals(
            ['foo'],
            $route->assemble(['foo' => 'bar', 'baz' => 'bat'], ['uri' => $uri])->assembledParams,
        );
    }

    public function testMatchIncludesDefaultsNotCapturedInHostname(): void
    {
        $route   = new Hostname('foo', 'www.example.com', [], ['env' => 'prod']);
        $request = (new Request())->withUri(new Uri('http://www.example.com/'));
        $match   = $route->match($request);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('prod', $match->getParam('env'));
    }

    public function testAssembleUsesDefaultsWhenParameterMissing(): void
    {
        $route  = new Hostname('foo', ':foo.example.com', [], ['foo' => 'baz']);
        $result = $route->assemble([]);

        $this->assertSame('baz.example.com', $result->host);
    }

    public function testAssembleOmitsOptionalWhenParameterEqualsDefault(): void
    {
        $route = new Hostname('foo', '[:foo.]example.com', [], ['foo' => 'bar']);

        $this->assertSame('example.com', $route->assemble([])->host);
        $this->assertSame('example.com', $route->assemble(['foo' => 'bar'])->host);
    }

    public function testAssembleIncludesOptionalWhenParameterDiffersFromDefault(): void
    {
        $route = new Hostname('foo', '[:foo.]example.com', [], ['foo' => 'bar']);

        $this->assertSame('baz.example.com', $route->assemble(['foo' => 'baz'])->host);
    }

    public function testAssembleOmitsNestedOptionalWhenAllParametersEqualDefaults(): void
    {
        $route = new Hostname(
            'foo',
            '[[:foo.]:bar.]example.com',
            [],
            ['foo' => 'a', 'bar' => 'b'],
        );

        $this->assertSame('example.com', $route->assemble([])->host);
    }

    public function testAssembleIncludesNestedOptionalWhenInnerParameterDiffersFromDefault(): void
    {
        $route = new Hostname(
            'foo',
            '[[:foo.]:bar.]example.com',
            [],
            ['foo' => 'a', 'bar' => 'b'],
        );

        $this->assertSame('x.b.example.com', $route->assemble(['foo' => 'x'])->host);
    }

    public function testAssembleNestedOptionalCollectsAssembledParamsFromBothLevels(): void
    {
        $route = new Hostname('foo', '[[:foo.]:bar.]example.com');

        $this->assertSame(
            ['foo', 'bar'],
            $route->assemble(['foo' => 'baz', 'bar' => 'bat'])->assembledParams,
        );
    }

    public function testMatchCapturesThreeParametersInOrder(): void
    {
        $route   = new Hostname('foo', ':one.:two.:three.example.com');
        $request = (new Request())->withUri(new Uri('http://a.b.c.example.com/'));
        $match   = $route->match($request);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('a', $match->getParam('one'));
        $this->assertSame('b', $match->getParam('two'));
        $this->assertSame('c', $match->getParam('three'));

        $this->assertSame(
            ['one', 'two', 'three'],
            $route->assemble(['one' => 'a', 'two' => 'b', 'three' => 'c'])->assembledParams,
        );
    }

    public function testConstructWithEmptyRoute(): void
    {
        $route   = new Hostname('foo', '');
        $request = (new Request())->withUri((new Uri())->withHost(''));
        $match   = $route->match($request);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('', $route->assemble([])->host ?? '');
    }

    /**
     * @psalm-return array<string, array{0: string, 1: class-string<Throwable>, 2: string}>
     */
    public static function parseExceptionsProvider(): array
    {
        return [
            'unbalanced-brackets'     => [
                '[',
                RuntimeException::class,
                'Found unbalanced brackets',
            ],
            'closing-without-opening' => [
                ']',
                RuntimeException::class,
                'Found closing bracket without matching opening bracket',
            ],
            'empty-parameter-name'    => [
                ':',
                RuntimeException::class,
                'Found empty parameter name',
            ],
        ];
    }

    /**
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('parseExceptionsProvider')]
    public function testParseRouteDefinitionExceptions(string $route, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        new Hostname('toto', $route);
    }

    public function testFactoryAppliesConstraintsDefaultsAndPriority(): void
    {
        $route = (new HostnameBuilder())->build([
            'name'        => 'foo',
            'route'       => ':foo.example.com',
            'constraints' => ['foo' => '\d+'],
            'defaults'    => ['foo' => '999'],
            'priority'    => 5,
        ]);

        $this->assertSame(5, $route->getPriority());
        $this->assertSame(
            '999.example.com',
            $route->assemble([])->host
        );

        $match = $route->match(
            (new Request())->withUri(new Uri('http://123.example.com/'))
        );
        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('123', $match->getParam('foo'));

        $this->assertNull(
            $route->match((new Request())->withUri(new Uri('http://abc.example.com/')))
        );
    }

    public function testFactory(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Hostname::class,
            [
                'route' => 'Missing "route" in options array',
                'name'  => 'Missing "name" in options array',
            ],
            [
                'route' => 'example.com',
                'name'  => 'foo',
            ]
        );
    }

    #[Group('laminas5656')]
    public function testFailedHostnameSegmentMatchDoesNotEmitErrors(): void
    {
        $this->expectException(RuntimeException::class);
        new Hostname('toto', ':subdomain.with_underscore.com');
    }
}
