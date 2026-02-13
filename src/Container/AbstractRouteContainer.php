<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Laminas\Router\RouteInterface;

use function array_keys;
use function array_map;
use function count;
use function usort;

/**
 * Abstract route container implementation.
 *
 * Higher priority values iterate first.
 * Same priority: LIFO (last inserted first).
 *
 * @template TValue of RouteInterface
 * @implements RouteContainerInterface<string, TValue>
 */
abstract class AbstractRouteContainer implements RouteContainerInterface
{
    /** @var array<string, array{value: TValue, priority: int, serial: int}> */
    protected array $items = [];

    /** @var list<string> Sorted keys for iteration */
    protected array $sortedKeys = [];

    /** @var int Increments for LIFO ordering within same priority */
    protected int $serial = 0;

    protected int $position = 0;

    protected bool $sorted = false;

    /**
     * @param TValue $value
     */
    abstract public function insert(string $key, RouteInterface $value, ?int $priority = null): void;

    /**
     * @return TValue|null
     */
    abstract public function get(string $key): ?RouteInterface;

    /**
     * @return TValue
     */
    abstract public function current(): RouteInterface;

    /**
     * Store an item in the container.
     *
     * @param TValue $value
     */
    protected function setRoute(string $key, RouteInterface $value, ?int $priority): void
    {
        $this->items[$key] = [
            'value'    => $value,
            'priority' => $priority ?? 0,
            'serial'   => $this->serial++,
        ];
        $this->sorted      = false;
    }

    /**
     * Retrieve an item from the container.
     *
     * @return TValue|null
     */
    protected function getRoute(string $key): ?RouteInterface
    {
        return $this->items[$key]['value'] ?? null;
    }

    /**
     * Get the current item during iteration.
     *
     * @return TValue
     */
    protected function getCurrentRoute(): RouteInterface
    {
        $this->sort();
        return $this->items[$this->sortedKeys[$this->position]]['value'];
    }

    public function remove(string $key): void
    {
        unset($this->items[$key]);
        $this->sorted = false;
    }

    public function clear(): void
    {
        $this->items      = [];
        $this->sortedKeys = [];
        $this->sorted     = true;
        $this->position   = 0;
    }

    /** @psalm-suppress PossiblyUnusedMethod Required by Countable interface */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Sort items by priority (higher first) and serial (higher first for LIFO).
     */
    protected function sort(): void
    {
        if ($this->sorted) {
            return;
        }

        // Cast keys to string explicitly - PHP converts numeric string keys to int
        $this->sortedKeys = array_map('strval', array_keys($this->items));
        usort($this->sortedKeys, function (string $a, string $b): int {
            $itemA = $this->items[$a];
            $itemB = $this->items[$b];
            // Higher priority first, then higher serial (LIFO)
            return $itemB['priority'] <=> $itemA['priority']
                ?: $itemB['serial'] <=> $itemA['serial'];
        });
        $this->sorted   = true;
        $this->position = 0;
    }

    public function key(): ?string
    {
        $this->sort();
        return $this->sortedKeys[$this->position] ?? null;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->sort();
        $this->position = 0;
    }

    public function valid(): bool
    {
        $this->sort();
        return isset($this->sortedKeys[$this->position]);
    }
}
