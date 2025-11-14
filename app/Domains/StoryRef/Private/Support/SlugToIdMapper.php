<?php

namespace App\Domains\StoryRef\Private\Support;

use Illuminate\Support\Collection;

class SlugToIdMapper
{
    /**
     * @template TDto
     * @param array<int,string>|null $slugs
     * @param Collection<int,TDto> $dtos
     * @param callable(TDto): array{0:string,1:int} $extractor
     * @return array<int,int>
     */
    public static function map(?array $slugs, Collection $dtos, callable $extractor): array
    {
        if ($slugs === null) {
            return [];
        }

        $normalized = [];
        foreach ($slugs as $slug) {
            if (! is_string($slug)) {
                continue;
            }
            $clean = trim(strtolower($slug));
            if ($clean !== '') {
                $normalized[] = $clean;
            }
        }

        if ($normalized === []) {
            return [];
        }

        $normalized = array_values(array_unique($normalized));

        $bySlug = [];
        foreach ($dtos as $dto) {
            [$slug, $id] = $extractor($dto);
            $slugKey = strtolower($slug);
            $bySlug[$slugKey] = (int) $id;
        }

        $ids = [];
        foreach ($normalized as $slug) {
            if (isset($bySlug[$slug])) {
                $ids[] = $bySlug[$slug];
            }
        }

        return array_values(array_unique($ids));
    }
}
