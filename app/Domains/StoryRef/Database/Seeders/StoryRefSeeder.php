<?php

namespace App\Domains\StoryRef\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\StoryRef\Services\GenreService;
use App\Domains\StoryRef\Services\AudienceService;
use App\Domains\StoryRef\Services\TypeService;
use App\Domains\StoryRef\Services\StatusService;
use App\Domains\StoryRef\Services\CopyrightService;
use App\Domains\StoryRef\Services\TriggerWarningService;
use App\Domains\StoryRef\Services\FeedbackService;
use App\Domains\StoryRef\Models\StoryRefGenre;
use App\Domains\StoryRef\Models\StoryRefAudience;
use App\Domains\StoryRef\Models\StoryRefType;
use App\Domains\StoryRef\Models\StoryRefStatus;
use App\Domains\StoryRef\Models\StoryRefCopyright;
use App\Domains\StoryRef\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Models\StoryRefFeedback;

class StoryRefSeeder extends Seeder
{
    public function run(): void
    {
        // Genres (only if empty)
        if (!StoryRefGenre::query()->exists()) {
            app(GenreService::class)->create([
                'name' => 'Fantasy',
                'description' => 'Imaginary worlds filled with dragons',
                // slug will auto-generate
                'is_active' => true,
            ]);
        }

        // Audiences (only if empty)
        if (!StoryRefAudience::query()->exists()) {
            app(AudienceService::class)->create([
                'name' => 'All audiences',
                'is_active' => true,
            ]);
        }

        // Types (only if empty)
        if (!StoryRefType::query()->exists()) {
            app(TypeService::class)->create([
                'name' => 'Novel',
                'is_active' => true,
            ]);
        }

        // Statuses (only if empty)
        if (!StoryRefStatus::query()->exists()) {
            app(StatusService::class)->create([
                'name' => 'First draft',
                'description' => null,
                'is_active' => true,
            ]);
        }

        // Copyrights (only if empty)
        if (!StoryRefCopyright::query()->exists()) {
            app(CopyrightService::class)->create([
                'name' => 'All rights reserved',
                'description' => null,
                'is_active' => true,
            ]);
        }

        // Trigger Warnings (only if empty)
        if (!StoryRefTriggerWarning::query()->exists()) {
            app(TriggerWarningService::class)->create([
                'name' => 'Physical Violence',
                'description' => 'People are getting hurt, be it with punches or weapons',
                'is_active' => true,
            ]);
        }

        // Feedbacks (only if empty)
        if (!StoryRefFeedback::query()->exists()) {
            app(FeedbackService::class)->create([
                'name' => 'Gentle please',
                'is_active' => true,
            ]);
        }
    }
}
