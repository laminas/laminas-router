<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Translator\TranslatorInterface;
use LaminasTest\Router\BuilderTester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;
use UnexpectedValueException;

use function implode;
use function sprintf;
use function strlen;
use function strpos;

final class SegmentTest extends TestCase
{
    /**
     * @psalm-return array<string, array{
     *     0: Segment,
     *     1: string,
     *     2: null|int,
     *     3: null|array<non-empty-string, string>
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                                             => [
                new Segment('foo', '/:foo'),
                '/bar',
                null,
                ['foo' => 'bar'],
            ],
            'no-match-without-leading-slash'                           => [
                new Segment('foo', ':foo'),
                '/bar/',
                null,
                null,
            ],
            'no-match-with-trailing-slash'                             => [
                new Segment('foo', '/:foo'),
                '/bar/',
                null,
                null,
            ],
            'offset-skips-beginning'                                   => [
                new Segment('foo', ':foo'),
                '/bar',
                1,
                ['foo' => 'bar'],
            ],
            'offset-enables-partial-matching'                          => [
                new Segment('foo', '/:foo'),
                '/bar/baz',
                0,
                ['foo' => 'bar'],
            ],
            'match-overrides-default'                                  => [
                new Segment('foo', '/:foo', [], ['foo' => 'baz']),
                '/bar',
                null,
                ['foo' => 'bar'],
            ],
            'constraints-prevent-match'                                => [
                new Segment('foo', '/:foo', ['foo' => '\d+']),
                '/bar',
                null,
                null,
            ],
            'constraints-allow-match'                                  => [
                new Segment('foo', '/:foo', ['foo' => '\d+']),
                '/123',
                null,
                ['foo' => '123'],
            ],
            'constraints-override-non-standard-delimiter'              => [
                new Segment('foo', '/:foo{-}/bar', ['foo' => '[^/]+']),
                '/foo-bar/bar',
                null,
                ['foo' => 'foo-bar'],
            ],
            'constraints-with-parantheses-dont-break-parameter-map'    => [
                new Segment('foo', '/:foo/:bar', ['foo' => '(bar)']),
                '/bar/baz',
                null,
                ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'simple-match-with-optional-parameter'                     => [
                new Segment('foo', '/[:foo]', [], ['foo' => 'bar']),
                '/',
                null,
                ['foo' => 'bar'],
            ],
            'optional-parameter-is-ignored'                            => [
                new Segment('foo', '/:foo[/:bar]'),
                '/bar',
                null,
                ['foo' => 'bar'],
            ],
            'optional-parameter-is-provided-with-default'              => [
                new Segment('foo', '/:foo[/:bar]', [], ['bar' => 'baz']),
                '/bar',
                null,
                ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'optional-parameter-is-consumed'                           => [
                new Segment('foo', '/:foo[/:bar]'),
                '/bar/baz',
                null,
                ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'optional-group-is-discared-with-missing-parameter'        => [
                new Segment('foo', '/:foo[/:bar/:baz]', [], ['bar' => 'baz']),
                '/bar',
                null,
                ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'optional-group-within-optional-group-is-ignored'          => [
                new Segment('foo', '/:foo[/:bar[/:baz]]', [], ['bar' => 'baz', 'baz' => 'bat']),
                '/bar',
                null,
                ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'bat'],
            ],
            'non-standard-delimiter-before-parameter'                  => [
                new Segment('foo', '/foo-:bar'),
                '/foo-baz',
                null,
                ['bar' => 'baz'],
            ],
            'non-standard-delimiter-between-parameters'                => [
                new Segment('foo', '/:foo{-}-:bar'),
                '/bar-baz',
                null,
                ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'non-standard-delimiter-before-optional-parameter'         => [
                new Segment('foo', '/:foo{-/}[-:bar]/:baz'),
                '/bar-baz/bat',
                null,
                ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'bat'],
            ],
            'non-standard-delimiter-before-ignored-optional-parameter' => [
                new Segment('foo', '/:foo{-/}[-:bar]/:baz'),
                '/bar/bat',
                null,
                ['foo' => 'bar', 'baz' => 'bat'],
            ],
            'parameter-with-dash-in-name'                              => [
                new Segment('foo', '/:foo-bar'),
                '/baz',
                null,
                ['foo-bar' => 'baz'],
            ],
            'url-encoded-parameters-are-decoded'                       => [
                new Segment('foo', '/:foo'),
                '/foo%20bar',
                null,
                ['foo' => 'foo bar'],
            ],
            'urlencode-flaws-corrected'                                => [
                new Segment('foo', '/:foo'),
                "/!$&'()*,-.:;=@_~+",
                null,
                ['foo' => "!$&'()*,-.:;=@_~+"],
            ],
            'empty-matches-are-replaced-with-defaults'                 => [
                new Segment('foo', '/foo[/:bar]/baz-:baz', [], ['bar' => 'bar']),
                '/foo/baz-baz',
                null,
                ['bar' => 'bar', 'baz' => 'baz'],
            ],
        ];
    }

    public function testL10nRoute(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly(8))
            ->method('translate')
            ->willReturnCallback(function (
                string $message,
                string $textDomain = 'default',
                ?string $locale = null
            ): string {
                if ($message === 'fw' && $textDomain === 'default' && $locale === null) {
                    return 'framework';
                }

                if ($message === 'fw' && $textDomain === 'default' && $locale === 'de-DE') {
                    return 'baukasten';
                }

                if ($message === 'fw' && $textDomain === 'alternative' && $locale === null) {
                    return 'fw-alternative';
                }

                if ($message === 'fw' && $textDomain === 'default' && $locale === 'fr-FR') {
                    return 'fw';
                }

                if ($message === 'fw' && $textDomain === 'default') {
                    return 'framework';
                }

                throw new UnexpectedValueException(sprintf(
                    'Translation not found message "%s" textDomain "%s" local "%s"',
                    $message,
                    $textDomain,
                    $locale ?? 'null'
                ));
            });

        $this->matchingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/framework',
            null,
            [],
            ['translator' => $translator]
        );
        $this->matchingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/baukasten',
            null,
            [],
            ['translator' => $translator, 'locale' => 'de-DE']
        );
        $this->matchingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/fw',
            null,
            [],
            ['translator' => $translator, 'locale' => 'fr-FR']
        );
        $this->matchingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/fw-alternative',
            null,
            [],
            ['translator' => $translator, 'text_domain' => 'alternative']
        );

        $this->assemblingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/framework',
            null,
            [],
            ['translator' => $translator]
        );
        $this->assemblingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/baukasten',
            null,
            [],
            ['translator' => $translator, 'locale' => 'de-DE']
        );
        $this->assemblingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/fw',
            null,
            [],
            ['translator' => $translator, 'locale' => 'fr-FR']
        );
        $this->assemblingWithL10n(
            new Segment('foo', '/{fw}', [], []),
            '/fw-alternative',
            null,
            [],
            ['translator' => $translator, 'text_domain' => 'alternative']
        );
    }

    /** @psalm-return array<string, array{0: string, 1: class-string<Throwable>, 2: string}> */
    public static function parseExceptionsProvider(): array
    {
        return [
            'unbalanced-brackets'                       => [
                '[',
                RuntimeException::class,
                'Found unbalanced brackets',
            ],
            'closing-bracket-without-opening-bracket'   => [
                ']',
                RuntimeException::class,
                'Found closing bracket without matching opening bracket',
            ],
            'empty-parameter-name'                      => [
                ':',
                RuntimeException::class,
                'Found empty parameter name',
            ],
            'translated-literal-without-closing-backet' => [
                '{test',
                RuntimeException::class,
                'Translated literal missing closing bracket',
            ],
        ];
    }

    /**
     * @param array<non-empty-string, string|null|int>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testMatching(
        Segment $route,
        string $path,
        ?int $offset,
        ?array $params = null,
        array $options = []
    ): void {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com' . $path));
        $match   = $route->match($request, $offset, $options);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($request->getUri()->getPath()), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @param array<non-empty-string, string|null|int>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testAssembling(
        Segment $route,
        string $path,
        ?int $offset,
        ?array $params = null,
        array $options = []
    ): void {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            $this->expectNotToPerformAssertions();
            return;
        }

        $result = $route->assemble($params, $options);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result->toString(), $offset));
        } else {
            $this->assertEquals($path, $result->toString());
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    private function matchingWithL10n(
        Segment $route,
        string $path,
        int|null $offset,
        ?array $params = null,
        array $options = []
    ): void {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com' . $path));
        $match   = $route->match($request, $offset, $options);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    private function assemblingWithL10n(
        Segment $route,
        string $path,
        int|null $offset,
        ?array $params = null,
        array $options = []
    ): void {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params, $options);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result->toString(), $offset));
        } else {
            $this->assertEquals($path, $result->toString());
        }
    }

    /**
     * @param class-string<Throwable> $exceptionName
     */
    #[DataProvider('parseExceptionsProvider')]
    public function testParseExceptions(string $route, string $exceptionName, string $exceptionMessage): void
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        new Segment('foo', $route);
    }

    public function testAssemblingWithMissingParameterInRoot(): void
    {
        $route = new Segment('foo', '/:foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter "foo"');
        $route->assemble();
    }

    public function testTranslatedAssemblingThrowsExceptionWithoutTranslator(): void
    {
        $route = new Segment('foo', '/{foo}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No translator provided');
        $route->assemble();
    }

    public function testTranslatedMatchingThrowsExceptionWithoutTranslator(): void
    {
        $route = new Segment('foo', '/{foo}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No translator provided');
        $route->match(new Request());
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = new Segment('foo', '/foo');
        $request = new Request();

        $this->assertNull($route->match($request));
    }

    public function testAssemblingWithExistingChild(): void
    {
        $route = new Segment('foo', '/[:foo]', [], ['foo' => 'bar']);
        $path  = $route->assemble([], ['has_child' => true]);

        $this->assertEquals('/bar', $path->toString());
    }

    public function testFactory(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Segment::class,
            [
                'route' => 'Missing "route" in options array',
                'name'  => 'Missing "name" in options array',
            ],
            [
                'route'       => '/:foo[/:bar{-}]',
                'constraints' => ['foo' => 'bar'],
                'name'        => 'foo',
            ]
        );
    }

    public function testRawDecode(): void
    {
        // verify all characters which don't absolutely require encoding pass through match unchanged
        // this includes every character other than #, %, / and ?
        $raw     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',.~!@$^&*()_+{}|:"<>';
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/' . $raw));
        $route   = new Segment('foo', '/:foo');
        $match   = $route->match($request);

        self::assertNotNull($match);
        $this->assertSame($raw, $match->getParam('foo'));
    }

    public function testEncodedDecode(): void
    {
        // @codingStandardsIgnoreStart
        // every character
        $in  = '%61%62%63%64%65%66%67%68%69%6a%6b%6c%6d%6e%6f%70%71%72%73%74%75%76%77%78%79%7a%41%42%43%44%45%46%47%48%49%4a%4b%4c%4d%4e%4f%50%51%52%53%54%55%56%57%58%59%5a%30%31%32%33%34%35%36%37%38%39%60%2d%3d%5b%5d%5c%3b%27%2c%2e%2f%7e%21%40%23%24%25%5e%26%2a%28%29%5f%2b%7b%7d%7c%3a%22%3c%3e%3f';
        $out = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',./~!@#$%^&*()_+{}|:"<>?';
        // @codingStandardsIgnoreEnd

        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/' . $in));
        $route   = new Segment('foo', '/:foo');
        $match   = $route->match($request);

        self::assertNotNull($match);
        $this->assertSame($out, $match->getParam('foo'));
    }

    public function testEncodeCache(): void
    {
        $params1 = ['p1' => 6.123, 'p2' => 7];
        $uri1    = 'example.com/' . implode('/', $params1);
        $params2 = ['p1' => 6, 'p2' => 'test'];
        $uri2    = 'example.com/' . implode('/', $params2);

        $route   = new Segment('foo', 'example.com/:p1/:p2');
        $request = new Request();

        $request = $request->withUri(new Uri($uri1));
        $route->match($request);
        $this->assertSame($uri1, $route->assemble($params1)->toString());

        $request = $request->withUri(new Uri($uri2));
        $route->match($request);
        $this->assertSame($uri2, $route->assemble($params2)->toString());
    }

    public function testConstructWithEmptyRoute(): void
    {
        $route = new Segment('foo', '');

        $this->assertSame('', $route->assemble([])->toString());
    }

    public function testMatchCapturesThreeParametersInOrder(): void
    {
        $route   = new Segment('foo', '/:one/:two/:three');
        $request = (new Request())->withUri(new Uri('http://example.com/a/b/c'));
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

    public function testMatchLiteralWithRegexMetacharacters(): void
    {
        $route   = new Segment('foo', '/foo.bar/:id');
        $request = (new Request())->withUri(new Uri('http://example.com/foo.bar/1'));

        $this->assertSame('1', $route->match($request)?->getParam('id'));

        $request = (new Request())->withUri(new Uri('http://example.com/fooXbar/1'));
        $this->assertNull($route->match($request));
    }

    public function testAssembleOmitsNestedOptionalWhenDefaults(): void
    {
        $route = new Segment('foo', '[:bar[/:baz]]', [], ['bar' => 'b', 'baz' => 'c']);

        $this->assertSame('', $route->assemble([])->toString());
        $this->assertSame('x', $route->assemble(['bar' => 'x'])->toString());
    }

    public function testAssembleNestedOptionalCollectsAssembledParamsFromBothLevels(): void
    {
        $route = new Segment('foo', '[:bar[/:baz]]', [], ['bar' => 'b', 'baz' => 'c']);

        $this->assertSame(
            ['bar', 'baz'],
            $route->assemble(['bar' => 'x', 'baz' => 'y'])->assembledParams,
        );
    }

    public function testMatchNestedOptionalWithTranslatedLiteralsAtBothLevels(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(
            static fn (string $message): string => match ($message) {
                'outer' => 'OUT',
                'inner' => 'IN',
                default => $message,
            },
        );

        $route   = new Segment('foo', '[/{outer}[/{inner}/:bar]]');
        $request = (new Request())->withUri(new Uri('http://example.com/OUT/IN/x'));

        $match = $route->match($request, null, ['translator' => $translator]);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('x', $match->getParam('bar'));
    }
}
