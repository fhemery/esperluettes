<?php

namespace App\Domains\Story\Public\Contracts;

enum StoryQueryReadStatus: int {
    case All = 0;
    case UnreadOnly = 1;
    case ReadOnly = 2;
}