<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Http\Request;
use Laminas\Router\Http\Method as HttpMethod;
use Laminas\Router\Http\RouteMatch;
use Laminas\Stdlib\Request as BaseRequest;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
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
                'get',
            ],
            'match-comma-separated-verbs'    => [
                new HttpMethod('get,post'),
                'get',
            ],
            'match-comma-separated-verbs-ws' => [
                new HttpMethod('get ,   post , put'),
                'post',
            ],
            'match-ignores-case'             => [
                new HttpMethod('Get'),
                'get',
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param string $verb
     */
    public function testMatching(HttpMethod $route, $verb)
    {
        $request = new Request();
        $request->setUri('http://example.com');
        $request->setMethod($verb);

        $match = $route->match($request);
        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testNoMatchWithoutVerb()
    {
        $route   = new HttpMethod('get');
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            HttpMethod::class,
            [
                'verb' => 'Missing "verb" in options array',
            ],
            [
                'verb' => 'get',
            ]
        );
    }
}
