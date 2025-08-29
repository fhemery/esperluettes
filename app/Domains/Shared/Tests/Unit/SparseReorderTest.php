<?php

use App\Domains\Shared\Contracts\Sortable;
use App\Domains\Shared\Support\SparseReorder;

use Tests\TestCase;

uses(TestCase::class);

class InMemorySortable implements Sortable
{
    public function __construct(
        private int $id,
        private int $order,
    ) {}

    public function getId(): int { return $this->id; }
    public function getSortOrder(): int { return $this->order; }
    public function setSortOrder(int $order): void { $this->order = $order; }
}

function makeItems(array $ordersById): array {
    $items = [];
    foreach ($ordersById as $id => $order) {
        $items[] = new InMemorySortable($id, $order);
    }
    return $items;
}

it('returns no changes when order is already correct', function () {
    $items = makeItems([1 => 100, 2 => 200, 3 => 300]);
    $ordered = [1,2,3];

    $changes = SparseReorder::computeChanges($items, $ordered, step: 100);

    expect($changes)->toBe([]);
});

it('moves an item between neighbors using midpoint', function () {
    $items = makeItems([1 => 100, 2 => 200, 3 => 300]);
    // Move 3 before 2 => [1,3,2]
    $ordered = [1,3,2];

    $changes = SparseReorder::computeChanges($items, $ordered, step: 100);

    // 3 should become between 100 and 200 => 150 (intdiv(100+200,2))
    expect($changes)->toHaveKey(3)
        ->and($changes[3])->toBe(150);
    // 2 fits after 150 already; unchanged
    expect($changes)->not()->toHaveKey(2);
    // 1 unchanged
    expect($changes)->not()->toHaveKey(1);
});

it('inserts at the start using right - step', function () {
    $items = makeItems([1 => 100, 2 => 200, 3 => 300]);
    $ordered = [3,1,2];

    $changes = SparseReorder::computeChanges($items, $ordered, step: 100);

    // right (id 1) has 100 => 100 - 100 = 0
    expect($changes)->toHaveKey(3)
        ->and($changes[3])->toBe(0);
});

it('inserts at the end using left + step', function () {
    $items = makeItems([1 => 100, 2 => 200, 3 => 300]);
    $ordered = [1,2,3]; // already at end -> no change
    expect(SparseReorder::computeChanges($items, $ordered, 100))->toBe([]);

    $items = makeItems([1 => 100, 2 => 200, 3 => -100]);
    $ordered = [1,2,3]; // move 3 to end; left is 200 => 300
    $changes = SparseReorder::computeChanges($items, $ordered, 100);
    expect($changes)->toHaveKey(3)->and($changes[3])->toBe(300);
});

it('triggers rebalance when there is no integer room between neighbors', function () {
    // Consecutive values leave no gap between 100 and 102 for an integer strictly between
    $items = makeItems([1 => 100, 2 => 101, 3 => 102]);
    $ordered = [1,3,2];

    $changes = SparseReorder::computeChanges($items, $ordered, step: 100);

    // Rebalance to 100,200,300 based on new order [1,3,2]; only changed ids returned
    expect($changes)->toEqual([
        3 => 200,
        2 => 300,
    ]);
});

it('handles large and negative orders gracefully', function () {
    $items = makeItems([1 => -1000, 2 => 100000]);
    $ordered = [1,2]; // already correct
    expect(SparseReorder::computeChanges($items, $ordered, 100))->toBe([]);

    // Swap them: [2,1] => place 2 before 1 using right-step: -1100
    $ordered = [2,1];
    $changes = SparseReorder::computeChanges($items, $ordered, 100);
    expect($changes)->toHaveKey(2)->and($changes[2])->toBe(-1100);
});

it('validates that orderedIds is a permutation of the item ids', function () {
    $items = makeItems([1 => 100, 2 => 200]);
    // Missing id 2
    $ordered = [1];
    SparseReorder::computeChanges($items, $ordered, 100);
})->throws(InvalidArgumentException::class);
