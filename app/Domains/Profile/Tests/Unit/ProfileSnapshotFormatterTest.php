<?php

use App\Domains\Profile\Public\Moderation\ProfileSnapshotFormatter;
use App\Domains\Profile\Private\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ProfileSnapshotFormatter', function () {
    it('capture returns expected snapshot structure and values', function () {
        $user = alice($this);
        // Ensure profile has some data
        /** @var Profile $profile */
        $profile = Profile::query()->findOrFail($user->id);
        $profile->update([
            'display_name' => 'Alice Cooper',
            'description' => 'Hello <b>world</b>',
            'facebook_url' => 'https://fb.example/alice',
            'x_url' => 'https://x.example/alice',
            'instagram_url' => 'https://ig.example/alice',
            'youtube_url' => 'https://yt.example/alice',
        ]);

        $formatter = new ProfileSnapshotFormatter();
        $snapshot = $formatter->capture($user->id);

        expect($snapshot)
            ->toHaveKeys(['display_name', 'bio', 'social_networks'])
            ->and($snapshot['display_name'])->toBe('Alice Cooper')
            ->and($snapshot['bio'])->toBe('Hello <b>world</b>')
            ->and($snapshot['social_networks'])
                ->toMatchArray([
                    'facebook' => 'https://fb.example/alice',
                    'x' => 'https://x.example/alice',
                    'instagram' => 'https://ig.example/alice',
                    'youtube' => 'https://yt.example/alice',
                ]);
    });

    it('render outputs expected HTML using snapshot', function () {
        $formatter = new ProfileSnapshotFormatter();
        $snapshot = [
            'display_name' => 'Alice <script>alert(1)</script>',
            'bio' => 'Bio with <b>bold</b>',
            'social_networks' => [
                'facebook' => 'https://fb.example/alice',
                'x' => null,
                'instagram' => null,
                'youtube' => null,
            ],
        ];

        $html = $formatter->render($snapshot);

        expect($html)
            ->toContain(__('profile::moderation.display_name'))
            ->toContain(__('profile::moderation.bio'))
            ->toContain(__('profile::moderation.social_networks'))
            // escaped content (no raw <script> tag)
            ->not->toContain('<script>')
            ->toContain(e('Alice <script>alert(1)</script>'))
            ->toContain(e('Bio with <b>bold</b>'))
            ->toContain(e(json_encode($snapshot['social_networks'])));
    });

    it('getReportedUserId returns the same id', function () {
        $formatter = new ProfileSnapshotFormatter();
        expect($formatter->getReportedUserId(42))->toBe(42);
    });

    it('getContentUrl returns the profile show route using slug', function () {
        $user = alice($this);
        /** @var Profile $profile */
        $profile = Profile::query()->findOrFail($user->id);
        $formatter = new ProfileSnapshotFormatter();

        $url = $formatter->getContentUrl($user->id);

        // Route expects slug param; ensure it matches generated URL
        expect($url)->toBe(route('profile.show', $profile->slug));
    });
});
