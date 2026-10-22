<?php

namespace App\Domains\Fanart\Private\Support;

enum FanartKind: string
{
    case Image = 'image';
    case Audio = 'audio';
}
