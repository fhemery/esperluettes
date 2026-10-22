<?php

namespace App\Domains\Fanart\Private\Support;

enum FanartStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
}
