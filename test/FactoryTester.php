<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Router\ConfigProvider;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteInterface;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

use function array_key_exists;

/**
 * Helper to test route factories.
 */
final class FactoryTester
{
    /**
     * Test a factory.
     *
     * @param array<string, string> $requiredOptions
     * @param class-string<RouteInterface> $classname
     */
    public function testFactory(string $classname, array $requiredOptions, array $options): void
    {
        // Test required options.
        foreach ($requiredOptions as $option => $exceptionMessage) {
            $testOptions = $options;

            unset($testOptions[$option]);

            try {
                $classname::factory($testOptions);
                TestCase::fail('An expected exception was not thrown');
            } catch (InvalidArgumentException $exception) {
                TestCase::assertStringContainsString($exceptionMessage, $exception->getMessage());
            }
        }

        if (! array_key_exists('route_plugins', $options)) {
            $options['route_plugins'] = new RoutePluginManager(new ServiceManager((new ConfigProvider())->__invoke()));
        }

        // Create the route, will throw an exception if something goes wrong.
        TestCase::assertInstanceOf($classname, $classname::factory($options));
    }
}
