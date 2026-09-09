<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Regex;
use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Regex>
 */
final readonly class RegexBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Regex
    {
        return Regex::factory($options);
    }
}
