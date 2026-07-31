<?php

namespace App\Domains\StoryRef\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\StoryRef\Private\Services\GenreRefService;
use App\Domains\StoryRef\Private\Services\AudienceRefService;
use App\Domains\StoryRef\Private\Services\TypeRefService;
use App\Domains\StoryRef\Private\Services\StatusRefService;
use App\Domains\StoryRef\Private\Services\CopyrightRefService;
use App\Domains\StoryRef\Private\Services\TriggerWarningRefService;
use App\Domains\StoryRef\Private\Services\FeedbackRefService;
use App\Domains\StoryRef\Private\Models\StoryRefGenre;
use App\Domains\StoryRef\Private\Models\StoryRefAudience;
use App\Domains\StoryRef\Private\Models\StoryRefType;
use App\Domains\StoryRef\Private\Models\StoryRefStatus;
use App\Domains\StoryRef\Private\Models\StoryRefCopyright;
use App\Domains\StoryRef\Private\Models\StoryRefTriggerWarning;
use App\Domains\StoryRef\Private\Models\StoryRefFeedback;

class StoryRefSeeder extends Seeder
{
    public function run(): void
    {
        // Genres (only if empty)
        if (!StoryRefGenre::query()->exists()) {
            app(GenreRefService::class)->create([
                'name' => 'Fantasy',
                'description' => 'Imaginary worlds filled with dragons',
                // slug will auto-generate
                'is_active' => true,
            ]);
        }

        // Audiences (only if empty)
        if (!StoryRefAudience::query()->exists()) {
            app(AudienceRefService::class)->create([
                'name' => 'All audiences',
                'is_active' => true,
            ]);
        }

        // Types (only if empty)
        if (!StoryRefType::query()->exists()) {
            app(TypeRefService::class)->create([
                'name' => 'Novel',
                'is_active' => true,
            ]);
        }

        // Statuses (only if empty)
        if (!StoryRefStatus::query()->exists()) {
            app(StatusRefService::class)->create([
                'name' => 'First draft',
                'description' => null,
                'is_active' => true,
            ]);
        }

        // Copyrights (only if empty)
        if (!StoryRefCopyright::query()->exists()) {
            app(CopyrightRefService::class)->create([
                'name' => 'All rights reserved',
                'description' => null,
                'is_active' => true,
            ]);
        }

        // Trigger Warnings (only if empty)
        if (!StoryRefTriggerWarning::query()->exists()) {
            app(TriggerWarningRefService::class)->create([
                'name' => 'Physical Violence',
                'description' => 'People are getting hurt, be it with punches or weapons',
                'is_active' => true,
            ]);
        }

        // Feedbacks (only if empty)
        if (!StoryRefFeedback::query()->exists()) {
            app(FeedbackRefService::class)->create([
                'name' => 'Gentle please',
                'is_active' => true,
            ]);
        }
    }
}
