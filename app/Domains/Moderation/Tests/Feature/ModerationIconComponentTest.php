<?php

use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ModerationIconComponent', function () {
    it('does not display anything for guests', function () {
        Auth::logout();

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for admins when there are no pending reports', function () {
        $admin = admin($this);
        $this->actingAs($admin);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for tech-admins when there are no pending reports', function () {
        $user = techAdmin($this);
        $this->actingAs($user);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for moderators when there are no pending reports', function () {
        $user = moderator($this);
        $this->actingAs($user);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('does not display the icon for regular users even if there are pending reports', function () {
        $user = alice($this);
        $this->actingAs($user);

        // Seed a pending report
        $reason = ModerationReason::create([
            'topic_key' => 'comment',
            'label' => 'Inappropriate',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ModerationReport::create([
            'topic_key' => 'comment',
            'entity_id' => 1,
            'reported_by_user_id' => $user->id,
            'reason_id' => $reason->id,
            // status defaults to 'pending'
        ]);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('shows the correct pending count badge for admins', function () {
        $admin = admin($this);

        $reason = ModerationReason::create([
            'topic_key' => 'comment',
            'label' => 'Inappropriate',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create 3 pending reports and 1 dismissed
        foreach ([1,2,3] as $i) {
            ModerationReport::create([
                'topic_key' => 'comment',
                'entity_id' => $i,
                'reported_by_user_id' => $admin->id,
                'reason_id' => $reason->id,
            ]);
        }
        ModerationReport::create([
            'topic_key' => 'comment',
            'entity_id' => 99,
            'reported_by_user_id' => $admin->id,
            'reason_id' => $reason->id,
            'status' => 'dismissed',
        ]);

        $this->actingAs($admin);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toContain('href="/admin/moderation/moderation-reports"');
        expect($rendered)->toContain('pending-badge');
        expect($rendered)->toMatch('/bg-accent[^>]*>\s*3\s*</');
    });

    it('does not show icon when pending count is zero (only dismissed reports)', function () {
        $admin = admin($this);

        $reason = ModerationReason::create([
            'topic_key' => 'comment',
            'label' => 'Inappropriate',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // One non-pending report
        ModerationReport::create([
            'topic_key' => 'comment',
            'entity_id' => 123,
            'reported_by_user_id' => $admin->id,
            'reason_id' => $reason->id,
            'status' => 'dismissed',
        ]);

        $this->actingAs($admin);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toBe('');
    });

    it('displays the icon for admins when there are pending reports', function () {
        $admin = admin($this);

        $reason = ModerationReason::create([
            'topic_key' => 'comment',
            'label' => 'Inappropriate',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ModerationReport::create([
            'topic_key' => 'comment',
            'entity_id' => 1,
            'reported_by_user_id' => $admin->id,
            'reason_id' => $reason->id,
            // status defaults to 'pending'
        ]);

        $this->actingAs($admin);

        $rendered = Blade::render('<x-moderation::moderation-icon-component />');

        expect($rendered)->toContain('href="/admin/moderation/moderation-reports"');
        expect($rendered)->toContain('pending-badge');
    });
});
