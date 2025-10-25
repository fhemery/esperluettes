<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\View\Models;

final class GardenMapConstants
{
    public const DEFAULT_WIDTH = 70;
    public const DEFAULT_HEIGHT = 53;
    public const DEFAULT_CELL_WIDTH = 16;
    public const DEFAULT_CELL_HEIGHT = 16;

    public const MIN_WIDTH = 10;
    public const MIN_HEIGHT = 10;
    public const MAX_WIDTH = 100;
    public const MAX_HEIGHT = 100;

    public const MIN_CELL_WIDTH = 8;
    public const MIN_CELL_HEIGHT = 8;
    public const MAX_CELL_WIDTH = 32;
    public const MAX_CELL_HEIGHT = 32;

    public const NB_FLOWERS=28;
    public const FLOWER_PATH_PREFIX='images/activities/jardino/';
}
