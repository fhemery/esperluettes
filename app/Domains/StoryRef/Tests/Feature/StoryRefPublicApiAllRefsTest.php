<?php

use App\Domains\StoryRef\Public\Api\StoryRefPublicApi;
use App\Domains\StoryRef\Public\Contracts\StoryRefFilterDto;
use App\Domains\StoryRef\Public\Contracts\StoryRefsDto;
use App\Domains\StoryRef\Public\Contracts\TypeDto;
use App\Domains\StoryRef\Public\Contracts\GenreDto;
use App\Domains\StoryRef\Public\Contracts\AudienceDto;
use App\Domains\StoryRef\Public\Contracts\StatusDto;
use App\Domains\StoryRef\Public\Contracts\TriggerWarningDto;
use App\Domains\StoryRef\Public\Contracts\FeedbackDto;
use App\Domains\StoryRef\Public\Contracts\CopyrightDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('StoryRefPublicApi all refs', function () {
    it('returns all ref collections as DTOs', function () {
        // Seed one active item for each ref type
        makeRefType($this, 'Type A');
        makeRefGenre($this, 'Genre A');
        makeRefAudience($this, 'Audience A');
        makeRefStatus($this, 'Status A');
        makeRefTriggerWarning($this, 'TW A');
        makeRefFeedback($this, 'Feedback A');
        makeRefCopyright($this, 'Copyright A');

        /** @var StoryRefPublicApi $api */
        $api = app(StoryRefPublicApi::class);

        /** @var StoryRefsDto $refs */
        $refs = $api->getAllStoryReferentials();

        expect($refs)->toBeInstanceOf(StoryRefsDto::class);

        expect($refs->types)->toBeInstanceOf(Collection::class);
        expect($refs->types->first())->toBeInstanceOf(TypeDto::class);

        expect($refs->genres)->toBeInstanceOf(Collection::class);
        expect($refs->genres->first())->toBeInstanceOf(GenreDto::class);

        expect($refs->audiences)->toBeInstanceOf(Collection::class);
        expect($refs->audiences->first())->toBeInstanceOf(AudienceDto::class);

        expect($refs->statuses)->toBeInstanceOf(Collection::class);
        expect($refs->statuses->first())->toBeInstanceOf(StatusDto::class);

        expect($refs->triggerWarnings)->toBeInstanceOf(Collection::class);
        expect($refs->triggerWarnings->first())->toBeInstanceOf(TriggerWarningDto::class);

        expect($refs->feedbacks)->toBeInstanceOf(Collection::class);
        expect($refs->feedbacks->first())->toBeInstanceOf(FeedbackDto::class);

        expect($refs->copyrights)->toBeInstanceOf(Collection::class);
        expect($refs->copyrights->first())->toBeInstanceOf(CopyrightDto::class);
    });

    it('applies activeOnly filtering to all collections', function () {
        // Each ref type: one active and one inactive
        makeRefType($this, 'Type Active', ['is_active' => true]);
        makeRefType($this, 'Type Inactive', ['is_active' => false]);

        makeRefGenre($this, 'Genre Active', ['is_active' => true]);
        makeRefGenre($this, 'Genre Inactive', ['is_active' => false]);

        makeRefAudience($this, 'Audience Active', ['is_active' => true]);
        makeRefAudience($this, 'Audience Inactive', ['is_active' => false]);

        makeRefStatus($this, 'Status Active', ['is_active' => true]);
        makeRefStatus($this, 'Status Inactive', ['is_active' => false]);

        makeRefTriggerWarning($this, 'TW Active', ['is_active' => true]);
        makeRefTriggerWarning($this, 'TW Inactive', ['is_active' => false]);

        makeRefFeedback($this, 'Feedback Active', ['is_active' => true]);
        makeRefFeedback($this, 'Feedback Inactive', ['is_active' => false]);

        makeRefCopyright($this, 'Copyright Active', ['is_active' => true]);
        makeRefCopyright($this, 'Copyright Inactive', ['is_active' => false]);

        /** @var StoryRefPublicApi $api */
        $api = app(StoryRefPublicApi::class);

        // Default filter: activeOnly = true
        $activeOnlyRefs = $api->getAllStoryReferentials();

        expect($activeOnlyRefs->types)->toHaveCount(1);
        expect($activeOnlyRefs->genres)->toHaveCount(1);
        expect($activeOnlyRefs->audiences)->toHaveCount(1);
        expect($activeOnlyRefs->statuses)->toHaveCount(1);
        expect($activeOnlyRefs->triggerWarnings)->toHaveCount(1);
        expect($activeOnlyRefs->feedbacks)->toHaveCount(1);
        expect($activeOnlyRefs->copyrights)->toHaveCount(1);

        // Explicit filter: activeOnly = false
        $allRefs = $api->getAllStoryReferentials(new StoryRefFilterDto(activeOnly: false));

        expect($allRefs->types)->toHaveCount(2);
        expect($allRefs->genres)->toHaveCount(2);
        expect($allRefs->audiences)->toHaveCount(2);
        expect($allRefs->statuses)->toHaveCount(2);
        expect($allRefs->triggerWarnings)->toHaveCount(2);
        expect($allRefs->feedbacks)->toHaveCount(2);
        expect($allRefs->copyrights)->toHaveCount(2);
    });
});
