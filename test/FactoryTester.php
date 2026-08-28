<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use ArrayIterator;
use Laminas\Router\ConfigProvider;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteBuilderRegistry;
use Laminas\Router\RouteBuilderRegistryFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Helper to test route factories.
 */
final class FactoryTester
{
    private readonly RouteBuilderRegistry $routerBuilderRegistry;

    /**
     * Create a new factory tester.
     */
    public function __construct(
        /**
         * Test case to call assertions to.
         */
        protected TestCase $testCase
    ) {
        $this->routerBuilderRegistry = (new RouteBuilderRegistryFactory())->__invoke(
            new ServiceManager((new ConfigProvider())->__invoke()['dependencies']),
        );
    }

    /**
     * Test a factory.
     *
     * @param array<string, mixed> $requiredOptions
     */
    public function testFactory(string $classname, array $requiredOptions, array $options): void
    {
        $factory = sprintf('%s::factory', $classname);

        // Test that the factory does not allow a scalar option.
        try {
            $factory(0);
            $this->testCase->fail('An expected exception was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->testCase->assertStringContainsString(
                'factory expects an array or Traversable set of options',
                $e->getMessage()
            );
        }

        // Test required options.
        foreach ($requiredOptions as $option => $exceptionMessage) {
            $testOptions = $options;

            unset($testOptions[$option]);

            try {
                $this->routerBuilderRegistry->build($classname, $testOptions);
                $factory($testOptions);
                $this->testCase->fail('An expected exception was not thrown');
            } catch (InvalidArgumentException $e) {
                $this->testCase->assertStringContainsString($exceptionMessage, $e->getMessage());
            }
        }

        // Create the route, will throw an exception if something goes wrong.
        $factory($options);
        $this->routerBuilderRegistry->build($classname, $options);

        // Try the same with an iterator.
        $factory(new ArrayIterator($options));
    }
}
