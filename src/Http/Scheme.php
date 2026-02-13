<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Scheme route.
 */
final class Scheme implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    public function __construct(
        private readonly string $scheme,
        private readonly array $defaults = []
    ) {
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Scheme
    {
        $options = self::processRouteOptions(
            $options,
            ['scheme'],
            ['defaults' => []],
        );

        return new Scheme(
            $options['scheme'],
            $options['defaults']
        );
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        if ($request->getUri()->getScheme() !== $this->scheme) {
            return null;
        }

        return new RouteMatch($this->defaults);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string
    {
        if (isset($options['uri'])) {
            $options['uri']->setScheme($this->scheme);
        }

        // A scheme does not contribute to the path, thus nothing is returned.
        return '';
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return [];
    }
}
