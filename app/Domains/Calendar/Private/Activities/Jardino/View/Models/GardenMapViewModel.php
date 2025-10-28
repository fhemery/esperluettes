<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Models;

final class GardenMapViewModel
{
    /** @param array<int, GardenCellViewModel> $occupiedCells */
    public function __construct(
        public readonly int $width = GardenMapConstants::DEFAULT_WIDTH,
        public readonly int $height = GardenMapConstants::DEFAULT_HEIGHT,
        public readonly int $cellWidth = GardenMapConstants::DEFAULT_CELL_WIDTH,
        public readonly int $cellHeight = GardenMapConstants::DEFAULT_CELL_HEIGHT,
        public readonly array $occupiedCells = [],
    ) {}

    public function getCell(int $x, int $y): ?GardenCellViewModel
    {
        foreach ($this->occupiedCells as $cell) {
            if ($cell->x === $x && $cell->y === $y) {
                return $cell;
            }
        }
        return null;
    }

    public function isCellOccupied(int $x, int $y): bool
    {
        return $this->getCell($x, $y) !== null;
    }

    public function getTotalCells(): int
    {
        return $this->width * $this->height;
    }

    public function getEmptyCells(): int
    {
        return $this->getTotalCells() - count($this->occupiedCells);
    }
}
