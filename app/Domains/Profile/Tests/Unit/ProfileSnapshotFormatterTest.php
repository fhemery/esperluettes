<?php

use App\Domains\Profile\Private\Support\Moderation\ProfileSnapshotFormatter;
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
            'display_name'     => 'Alice Cooper',
            'description'      => 'Hello <b>world</b>',
            'facebook_handle'  => 'alice',
            'x_handle'         => 'alice',
            'instagram_handle' => 'alice',
            'youtube_handle'   => 'alice',
            'tiktok_handle'    => 'alice',
            'bluesky_handle'   => 'alice.bsky.social',
            'mastodon_handle'  => 'alice@mastodon.social',
        ]);

        $formatter = new ProfileSnapshotFormatter();
        $snapshot = $formatter->capture($user->id);

        expect($snapshot)
            ->toHaveKeys(['display_name', 'bio', 'social_networks'])
            ->and($snapshot['display_name'])->toBe('Alice Cooper')
            ->and($snapshot['bio'])->toBe('Hello <b>world</b>')
            ->and($snapshot['social_networks'])
                ->toMatchArray([
                    'facebook'  => 'alice',
                    'x'         => 'alice',
                    'instagram' => 'alice',
                    'youtube'   => 'alice',
                    'tiktok'    => 'alice',
                    'bluesky'   => 'alice.bsky.social',
                    'mastodon'  => 'alice@mastodon.social',
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
