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

class StoryRefSeeder extends Seeder
{
    public function run(): void
    {
        // Genres
        app(GenreService::class)->create([
            'name' => 'Fantasy',
            'description' => 'Imaginary worlds filled with dragons',
            // slug will auto-generate
            'is_active' => true,
        ]);

        // Audiences
        app(AudienceService::class)->create([
            'name' => 'All audiences',
            'is_active' => true,
        ]);

        // Types
        app(TypeService::class)->create([
            'name' => 'Novel',
            'is_active' => true,
        ]);

        // Statuses
        app(StatusService::class)->create([
            'name' => 'First draft',
            'description' => null,
            'is_active' => true,
        ]);

        // Copyrights
        app(CopyrightService::class)->create([
            'name' => 'All rights reserved',
            'description' => null,
            'is_active' => true,
        ]);

        // Trigger Warnings
        app(TriggerWarningService::class)->create([
            'name' => 'Physical Violence',
            'description' => 'People are getting hurt, be it with punches or weapons',
            'is_active' => true,
        ]);

        // Feedbacks
        app(FeedbackService::class)->create([
            'name' => 'Gentle please',
            'is_active' => true,
        ]);
    }
}
