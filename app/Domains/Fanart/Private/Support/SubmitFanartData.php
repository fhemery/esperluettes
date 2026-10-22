<?php

namespace App\Domains\Fanart\Private\Support;

use Illuminate\Http\UploadedFile;

class SubmitFanartData
{
    public function __construct(
        public readonly FanartKind $kind,
        public readonly UploadedFile $file,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?int $chapterId,
        public readonly bool $shareOnDiscord,
    ) {
    }
}
