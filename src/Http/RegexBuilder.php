<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Regex>
 */
final readonly class RegexBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Regex::factory($options);
    }
}
