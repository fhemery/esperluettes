<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Notification\Tests\Fixtures\StaffOnlyTestNotificationContent;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

describe('notifications:export-types-doc', function () {
    it('writes a markdown file listing registered types and channels', function () {
        $path = sys_get_temp_dir().'/notification-types-test-'.uniqid('', true).'.md';

        $exit = Artisan::call('notifications:export-types-doc', [
            '--output' => $path,
        ]);

        expect($exit)->toBe(0);
        expect(file_exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('notifications:export-types-doc');
        expect($content)->toContain('news.published');
        expect($content)->toContain(NewsPublishedNotification::class);
        expect($content)->toContain('`discord`');
        expect($content)->toContain('Delivery channels');

        unlink($path);
    });

    it('includes the visible-to-roles column for a registered staff-only fixture type', function () {
        $factory = app(NotificationFactory::class);
        $factory->registerGroup('test-export-staff', 998, 'test::export.staff.group');
        $factory->register(
            type: StaffOnlyTestNotificationContent::type(),
            class: StaffOnlyTestNotificationContent::class,
            groupId: 'test-export-staff',
            nameKey: 'test::export.staff.type',
            visibleToRoles: [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
        );

        $path = sys_get_temp_dir().'/notification-types-staff-test-'.uniqid('', true).'.md';

        $exit = Artisan::call('notifications:export-types-doc', [
            '--output' => $path,
        ]);

        expect($exit)->toBe(0);

        $content = file_get_contents($path);
        expect($content)->toContain('Visible to roles');
        expect($content)->toContain(StaffOnlyTestNotificationContent::type());
        expect($content)->toContain('moderator, admin, tech-admin');

        unlink($path);
    });
});
