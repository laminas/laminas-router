<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function array_map;
use function explode;
use function in_array;
use function strtoupper;

/**
 * Method route.
 */
final class Method implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /** @var list<string> */
    private readonly array $verbs;

    /**
     * Create a new method route.
     */
    public function __construct(
        string $verb,
        private readonly array $defaults = []
    ) {
        $this->verbs = array_map('trim', explode(',', strtoupper($verb)));
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Method
    {
        $options = self::processRouteOptions(
            $options,
            ['verb'],
            ['defaults' => []],
        );

        return new Method(
            $options['verb'],
            $options['defaults']
        );
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        if (in_array(strtoupper($request->getMethod()), $this->verbs, true)) {
            return new RouteMatch($this->defaults);
        }

        return null;
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string
    {
        return '';
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return [];
    }
}
