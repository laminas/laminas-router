<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;

/**
 * Placeholder route.
 */
final class Placeholder implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    public function __construct(private readonly array $defaults)
    {
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Placeholder
    {
        $options = self::processRouteOptions(
            $options,
            [],
            ['defaults' => []],
        );

        if (! is_array($options['defaults'])) {
            throw new Exception\InvalidArgumentException('options[defaults] expected to be an array if set');
        }

        return new Placeholder($options['defaults']);
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        return new RouteMatch($this->defaults);
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
