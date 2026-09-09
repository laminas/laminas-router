<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Method as HttpMethod;
use LaminasTest\Router\BuilderTester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MethodTest extends TestCase
{
    /**
     * @psalm-return array<string, array{
     *     0: HttpMethod,
     *     1: string
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                   => [
                new HttpMethod('foo', 'get'),
                'get',
            ],
            'match-comma-separated-verbs'    => [
                new HttpMethod('foo', 'get,post'),
                'get',
            ],
            'match-comma-separated-verbs-ws' => [
                new HttpMethod('foo', 'get ,   post , put'),
                'post',
            ],
            'match-ignores-case'             => [
                new HttpMethod('foo', 'Get'),
                'get',
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(HttpMethod $route, string $verb): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com'));
        $request = $request->withMethod($verb);

        $match = $route->match($request);
        $this->assertInstanceOf(HttpRouteMatch::class, $match);
    }

    public function testNoMatchWithoutVerb(): void
    {
        $route   = new HttpMethod('foo', 'get');
        $request = (new Request())->withMethod('POST');

        $this->assertNull($route->match($request));
    }

    public function testFactory(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            HttpMethod::class,
            [
                'verb' => 'Missing "verb" in options array',
                'name' => 'Missing "name" in options array',
            ],
            [
                'verb' => 'get',
                'name' => 'foo',
            ]
        );
    }
}
