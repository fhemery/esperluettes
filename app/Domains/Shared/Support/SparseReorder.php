<?php

namespace App\Domains\Shared\Support;

use App\Domains\Shared\Contracts\Sortable;
use InvalidArgumentException;

class SparseReorder
{
    /**
     * Compute new sort_order values for items given the new ordered ids.
     * Returns a map of id => newSortOrder for items that must change.
     *
     * Strategy:
     * - Validate that orderedIds is a permutation of input item ids.
     * - Attempt sparse assignment: only change items whose current order does not fit strictly between
     *   their new left and right neighbors' effective orders. Use midpoint when possible.
     * - If any assignment has no integer room (left >= right-1), trigger full rebalance using step.
     *
     * @param Sortable[] $items      Existing items (any order)
     * @param int[]      $orderedIds New full order (must contain all ids exactly once)
     * @param int        $step       Step used for full rebalance and edge inserts
     * @return array<int,int>        Map id => new sort_order
     */
    public static function computeChanges(array $items, array $orderedIds, int $step = 100): array
    {
        if ($step <= 0) {
            throw new InvalidArgumentException('Step must be > 0');
        }

        // Build maps and validate inputs
        [$idToItem, $originalOrders] = self::buildMaps($items);
        self::validatePermutation($idToItem, $orderedIds);

        // Fast path: if the new order equals the old order when sorting by sort_order
        if (self::isNoOpOrder($items, $orderedIds)) {
            return [];
        }

        $newOrders = []; // id => new order (assigned during sparse pass)
        $changes = [];
        $rebalanceNeeded = false;

        $n = count($orderedIds);
        for ($i = 0; $i < $n; $i++) {
            $id = $orderedIds[$i];

            [$leftOrder, $rightOrder] = self::neighborOrders($orderedIds, $i, $newOrders, $originalOrders);
            $targetOrNull = self::computeTarget($leftOrder, $rightOrder, $step);
            if ($targetOrNull === null) {
                $rebalanceNeeded = true;
                break;
            }

            // If current value already fits between neighbors, keep it
            $current = $originalOrders[$id];
            if (self::fitsBetween($current, $leftOrder, $rightOrder)) {
                // Keep as-is
                $newOrders[$id] = $current;
                continue;
            }

            $newOrders[$id] = $targetOrNull;
            if ($targetOrNull !== $current) {
                $changes[$id] = $targetOrNull;
            }
        }

        if ($rebalanceNeeded) {
            $changes = self::rebalance($orderedIds, $originalOrders, $step);
        }

        return $changes;
    }

    private static function effectiveOrder(int $id, array $newOrders, array $originalOrders): int
    {
        return $newOrders[$id] ?? $originalOrders[$id];
    }

    /**
     * @param Sortable[] $items
     * @return array{array<int,Sortable>, array<int,int>} [idToItem, originalOrders]
     */
    private static function buildMaps(array $items): array
    {
        $idToItem = [];
        $originalOrders = [];
        foreach ($items as $it) {
            if (!$it instanceof Sortable) {
                throw new InvalidArgumentException('All items must implement Sortable');
            }
            $id = $it->getId();
            $idToItem[$id] = $it;
            $originalOrders[$id] = $it->getSortOrder();
        }
        return [$idToItem, $originalOrders];
    }

    /**
     * @param array<int,Sortable> $idToItem
     * @param int[] $orderedIds
     */
    private static function validatePermutation(array $idToItem, array $orderedIds): void
    {
        $ids = array_keys($idToItem);
        sort($ids);
        $oids = $orderedIds;
        sort($oids);
        if ($ids !== $oids) {
            throw new InvalidArgumentException('orderedIds must contain exactly all item ids');
        }
    }

    /**
     * @param Sortable[] $items
     */
    private static function isNoOpOrder(array $items, array $orderedIds): bool
    {
        $byOrder = $items;
        usort($byOrder, function (Sortable $a, Sortable $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });
        $oldOrderIds = array_map(fn (Sortable $s) => $s->getId(), $byOrder);
        return $oldOrderIds === $orderedIds;
    }

    /**
     * Return [leftOrder, rightOrder] (either may be null).
     * @return array{0:int|null,1:int|null}
     */
    private static function neighborOrders(array $orderedIds, int $index, array $newOrders, array $originalOrders): array
    {
        $n = count($orderedIds);
        $leftOrder = ($index > 0)
            ? self::effectiveOrder($orderedIds[$index - 1], $newOrders, $originalOrders)
            : null;
        $rightOrder = ($index < $n - 1)
            ? self::effectiveOrder($orderedIds[$index + 1], $newOrders, $originalOrders)
            : null;
        return [$leftOrder, $rightOrder];
    }

    /**
     * Compute target order or return null if requires rebalance.
     */
    private static function computeTarget(?int $leftOrder, ?int $rightOrder, int $step): ?int
    {
        if ($leftOrder === null && $rightOrder === null) {
            return 0; // single item case
        }
        if ($leftOrder === null) {
            return $rightOrder - $step; // before first
        }
        if ($rightOrder === null) {
            return $leftOrder + $step; // after last
        }
        // middle
        if ($leftOrder < $rightOrder - 1) {
            $target = intdiv($leftOrder + $rightOrder, 2);
            if ($target <= $leftOrder) {
                $target = $leftOrder + 1;
            }
            return $target;
        }
        return null; // no room -> rebalance
    }

    private static function fitsBetween(int $current, ?int $leftOrder, ?int $rightOrder): bool
    {
        return ($leftOrder === null || $current > $leftOrder)
            && ($rightOrder === null || $current < $rightOrder);
    }

    /**
     * @return array<int,int> id => new order
     */
    private static function rebalance(array $orderedIds, array $originalOrders, int $step): array
    {
        $changes = [];
        $order = $step;
        foreach ($orderedIds as $id) {
            $target = $order;
            $order += $step;
            if ($originalOrders[$id] !== $target) {
                $changes[$id] = $target;
            }
        }
        return $changes;
    }
}
