<?php

namespace App\Domains\Fanart\Private\Support;

use Illuminate\Http\UploadedFile;

class UpdateArtworkData
{
    public function __construct(
        public readonly ?UploadedFile $file,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?int $chapterId,
    ) {
    }
}
