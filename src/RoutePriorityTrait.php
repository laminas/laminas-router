<?php

declare(strict_types=1);

namespace Laminas\Router;

trait RoutePriorityTrait
{
    private ?int $priority = null;

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): int
    {
        return $this->priority ?? 0;
    }
}
