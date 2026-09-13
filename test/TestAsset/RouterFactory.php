<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

final readonly class RouterFactory
{
    public function __invoke(): Router
    {
        return new Router();
    }
}
