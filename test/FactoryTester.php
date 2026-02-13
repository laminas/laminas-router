<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use ArrayIterator;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteInterface;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Helper to test route factories.
 */
final class FactoryTester
{
    /**
     * Test case to call assertions to.
     */
    protected TestCase $testCase;

    /**
     * Create a new factory tester.
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Test a factory.
     */
    public function testFactory(string $classname, array $requiredOptions, array $options): void
    {
        $factory = sprintf('%s::factory', $classname);

        // Test required options.
        foreach ($requiredOptions as $option => $exceptionMessage) {
            $testOptions = $options;

            unset($testOptions[$option]);

            try {
                $factory($testOptions);
                $this->testCase->fail('An expected exception was not thrown');
            } catch (InvalidArgumentException $e) {
                $this->testCase->assertStringContainsString($exceptionMessage, $e->getMessage());
            }
        }

        // Create the route, will throw an exception if something goes wrong.
        $route = $factory($options);
        $this->testCase->assertInstanceOf(RouteInterface::class, $route);

        // Try the same with an iterator.
        $route = $factory(new ArrayIterator($options));
        $this->testCase->assertInstanceOf(RouteInterface::class, $route);
    }
}
