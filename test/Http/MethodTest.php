<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\Method as HttpMethod;
use Laminas\Router\Http\RouteMatch;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
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
                new HttpMethod('get'),
                'GET',
            ],
            'match-comma-separated-verbs'    => [
                new HttpMethod('get,post'),
                'GET',
            ],
            'match-comma-separated-verbs-ws' => [
                new HttpMethod('get ,   post , put'),
                'POST',
            ],
            'match-ignores-case'             => [
                new HttpMethod('Get'),
                'GET',
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(HttpMethod $route, string $verb): void
    {
        $request = new MockServerRequest(new MockUri('http://example.com'), $verb);

        $match = $route->match($request);
        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            HttpMethod::class,
            [
                'verb' => 'Missing "verb" option',
            ],
            [
                'verb' => 'get',
            ]
        );
    }
}
