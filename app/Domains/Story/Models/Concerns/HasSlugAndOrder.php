<?php

namespace App\Domains\Story\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlugAndOrder
{
    protected static function bootHasSlugAndOrder(): void
    {
        static::creating(function ($model) {
            // Slug: auto-generate if empty
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = static::generateUniqueSlug((string) $model->name);
            }

            // Order: compute next if model declares HAS_ORDER = true
            if (defined(static::class.'::HAS_ORDER') && constant(static::class.'::HAS_ORDER') === true) {
                if ($model->order === null) {
                    $max = (int) static::query()->max('order');
                    $model->order = $max + 1;
                }
            }
        });

        static::updating(function ($model) {
            // If name changed and slug not provided/changed, keep slug unless empty
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = static::generateUniqueSlug((string) $model->name, $model->getKey());
            }
        });
    }

    protected static function generateUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $i = 0;
        do {
            $candidate = $i === 0 ? $slug : $slug.'-'.$i;
            $query = static::query()->where('slug', $candidate);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $exists = $query->exists();
            $i++;
        } while ($exists);

        return $candidate;
    }
}
