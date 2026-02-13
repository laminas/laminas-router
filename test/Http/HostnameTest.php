<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\RouteMatch;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class HostnameTest extends TestCase
{
    /**
     * @psalm-return array<string, array{
     *     0: Hostname,
     *     1: string,
     *     2: null|array<string, null|string>
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                                                   => [
                new Hostname(':foo.example.com'),
                'bar.example.com',
                ['foo' => 'bar'],
            ],
            'no-match-on-different-hostname'                                 => [
                new Hostname('foo.example.com'),
                'bar.example.com',
                null,
            ],
            'no-match-with-different-number-of-parts'                        => [
                new Hostname('foo.example.com'),
                'example.com',
                null,
            ],
            'no-match-with-different-number-of-parts-2'                      => [
                new Hostname('example.com'),
                'foo.example.com',
                null,
            ],
            'match-overrides-default'                                        => [
                new Hostname(':foo.example.com', [], ['foo' => 'baz']),
                'bat.example.com',
                ['foo' => 'bat'],
            ],
            'constraints-prevent-match'                                      => [
                new Hostname(':foo.example.com', ['foo' => '\d+']),
                'bar.example.com',
                null,
            ],
            'constraints-allow-match'                                        => [
                new Hostname(':foo.example.com', ['foo' => '\d+']),
                '123.example.com',
                ['foo' => '123'],
            ],
            'constraints-allow-match-2'                                      => [
                new Hostname(
                    'www.:domain.com',
                    ['domain' => '(mydomain|myaltdomain1|myaltdomain2)'],
                    ['domain' => 'mydomain']
                ),
                'www.mydomain.com',
                ['domain' => 'mydomain'],
            ],
            'optional-subdomain'                                             => [
                new Hostname('[:foo.]example.com'),
                'bar.example.com',
                ['foo' => 'bar'],
            ],
            'two-optional-subdomain'                                         => [
                new Hostname('[:foo.][:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'missing-optional-subdomain'                                     => [
                new Hostname('[:foo.]example.com'),
                'example.com',
                ['foo' => null],
            ],
            'one-of-two-missing-optional-subdomain'                          => [
                new Hostname('[:foo.][:bar.]example.com'),
                'bat.example.com',
                ['foo' => null, 'bar' => 'bat'],
            ],
            'two-missing-optional-subdomain'                                 => [
                new Hostname('[:foo.][:bar.]example.com'),
                'example.com',
                ['foo' => null, 'bar' => null],
            ],
            'two-optional-subdomain-nested'                                  => [
                new Hostname('[[:foo.]:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'one-of-two-missing-optional-subdomain-nested'                   => [
                new Hostname('[[:foo.]:bar.]example.com'),
                'bat.example.com',
                ['foo' => null, 'bar' => 'bat'],
            ],
            'two-missing-optional-subdomain-nested'                          => [
                new Hostname('[[:foo.]:bar.]example.com'),
                'example.com',
                ['foo' => null, 'bar' => null],
            ],
            'no-match-on-different-hostname-and-optional-subdomain'          => [
                new Hostname('[:foo.]example.com'),
                'bar.test.com',
                null,
            ],
            'no-match-with-different-number-of-parts-and-optional-subdomain' => [
                new Hostname('[:foo.]example.com'),
                'bar.baz.example.com',
                null,
            ],
            'match-overrides-default-optional-subdomain'                     => [
                new Hostname('[:foo.]:bar.example.com', [], ['bar' => 'baz']),
                'bat.qux.example.com',
                ['foo' => 'bat', 'bar' => 'qux'],
            ],
            'constraints-prevent-match-optional-subdomain'                   => [
                new Hostname('[:foo.]example.com', ['foo' => '\d+']),
                'bar.example.com',
                null,
            ],
            'constraints-allow-match-optional-subdomain'                     => [
                new Hostname('[:foo.]example.com', ['foo' => '\d+']),
                '123.example.com',
                ['foo' => '123'],
            ],
            'middle-subdomain-optional'                                      => [
                new Hostname(':foo.[:bar.]example.com'),
                'baz.bat.example.com',
                ['foo' => 'baz', 'bar' => 'bat'],
            ],
            'missing-middle-subdomain-optional'                              => [
                new Hostname(':foo.[:bar.]example.com'),
                'baz.example.com',
                ['foo' => 'baz'],
            ],
            'non-standard-delimeter'                                         => [
                new Hostname('user-:username.example.com'),
                'user-jdoe.example.com',
                ['username' => 'jdoe'],
            ],
            'non-standard-delimeter-optional'                                => [
                new Hostname(':page{-}[-:username].example.com'),
                'article-jdoe.example.com',
                ['page' => 'article', 'username' => 'jdoe'],
            ],
            'missing-non-standard-delimeter-optional'                        => [
                new Hostname(':page{-}[-:username].example.com'),
                'article.example.com',
                ['page' => 'article'],
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(Hostname $route, string $hostname, ?array $params = null): void
    {
        $request = new MockServerRequest(new MockUri('https://' . $hostname . '/'));
        $match   = $route->match($request);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(RouteMatch::class, $match);

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    #[DataProvider('routeProvider')]
    public function testAssembling(Hostname $route, string $hostname, ?array $params = null): void
    {
        if ($params === null) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $uri  = new HttpUri();
        $path = $route->assemble($params, ['uri' => $uri]);

        $this->assertEquals('', $path);
        $this->assertEquals($hostname, $uri->getHost());
    }

    public function testNoMatchWithRelativeUri(): void
    {
        $route   = new Hostname('example.com');
        $request = new MockServerRequest(new MockUri('/relative/path'));

        self::assertNull($route->match($request));
    }

    public function testNoMatchWithPlaceholderOnRelativeUri(): void
    {
        $route   = new Hostname(':domain');
        $request = new MockServerRequest(new MockUri('/relative/path'));

        self::assertNull($route->match($request));
    }

    public function testMatchesRelativeUriWithFullyOptionalDefinition(): void
    {
        $route   = new Hostname('[:domain]');
        $request = new MockServerRequest(new MockUri('/relative/path'));

        $match = $route->match($request);
        self::assertInstanceOf(RouteMatch::class, $match);
        self::assertArrayNotHasKey('domain', $match->getParams());
    }

    public function testAssemblingWithMissingParameter(): void
    {
        $route = new Hostname(':foo.example.com');
        $uri   = new HttpUri();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "foo"');
        $route->assemble([], ['uri' => $uri]);
    }

    public function testGetAssembledParams(): void
    {
        $route = new Hostname(':foo.example.com');
        $uri   = new HttpUri();
        $route->assemble(['foo' => 'bar', 'baz' => 'bat'], ['uri' => $uri]);

        $this->assertEquals(['foo'], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Hostname::class,
            [
                'route' => 'Missing "route" option',
            ],
            [
                'route' => 'example.com',
            ]
        );
    }

    #[Group('laminas5656')]
    public function testFailedHostnameSegmentMatchDoesNotEmitErrors(): void
    {
        $this->expectException(RuntimeException::class);
        new Hostname(':subdomain.with_underscore.com');
    }
}
