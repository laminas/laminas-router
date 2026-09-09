<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatchInterface;
use Psr\Http\Message\RequestInterface;

use function array_key_exists;

/**
 * Dummy route.
 */
final readonly class DummyRouteWithParam implements RouteInterface
{
    public function __construct(
        private string $name,
        private int|null $priority = null
    ) {
    }

    /** @inheritDoc */
    public function match(RequestInterface $request): RouteMatchInterface
    {
        return new HttpRouteMatch(['foo' => 'bar'], $this->name);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        return new AssembledUrl(array_key_exists('foo', $params) ? (string) $params['foo'] : '');
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
