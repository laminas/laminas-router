<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteMatch as BaseRouteMatch;

use function array_merge;

/**
 * Part route match.
 */
final class RouteMatch extends BaseRouteMatch
{
    /**
     * Create a part RouteMatch with given parameters and length.
     */
    public function __construct(
        protected array $params,
        protected int $length = 0
    ) {
        parent::__construct($params);
    }

    /**
     * setMatchedRouteName(): defined by BaseRouteMatch.
     *
     * @see    BaseRouteMatch::setMatchedRouteName()
     */
    public function setMatchedRouteName(string $name): RouteMatch
    {
        if ($this->matchedRouteName === null) {
            $this->matchedRouteName = $name;
        } else {
            $this->matchedRouteName = $name . '/' . $this->matchedRouteName;
        }

        return $this;
    }

    /**
     * Merge parameters from another match.
     */
    public function merge(RouteMatch $match): RouteMatch
    {
        $this->params  = array_merge($this->params, $match->getParams());
        $this->length += $match->getLength();

        $this->matchedRouteName = $match->getMatchedRouteName();

        return $this;
    }

    /**
     * Get the matched path length.
     */
    public function getLength(): int
    {
        return $this->length;
    }
}
