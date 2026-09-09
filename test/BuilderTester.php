<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteInterface;
use PHPUnit\Framework\TestCase;

/**
 * Helper to test route builders.
 */
final readonly class BuilderTester
{
    private RouteBuilderContainerInterface $routeBuilderContainer;

    public function __construct()
    {
        $this->routeBuilderContainer = RouteBuilderContainerTestHelper::create();
    }

    /**
     * @param array<string, string> $requiredOptions
     * @param class-string<RouteInterface> $classname
     * @param array<string, mixed> $options
     */
    public function testBuilder(string $classname, array $requiredOptions, array $options): void
    {
        foreach ($requiredOptions as $option => $exceptionMessage) {
            $testOptions = $options;

            unset($testOptions[$option]);

            try {
                $this->routeBuilderContainer->build(['type' => $classname, ...$testOptions]);
                TestCase::fail('An expected exception was not thrown');
            } catch (InvalidArgumentException $exception) {
                TestCase::assertStringContainsString($exceptionMessage, $exception->getMessage());
            }
        }

        TestCase::assertInstanceOf(
            $classname,
            $this->routeBuilderContainer->build(['type' => $classname, ...$options])
        );
    }
}
