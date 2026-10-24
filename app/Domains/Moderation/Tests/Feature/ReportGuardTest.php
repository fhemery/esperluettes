<?php

use App\Domains\Moderation\Private\Models\ModerationReport;
use App\Domains\Moderation\Public\Contracts\ReportGuardInterface;
use App\Domains\Moderation\Public\Contracts\SnapshotFormatterInterface;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

class GuardedTestFormatter implements SnapshotFormatterInterface, ReportGuardInterface
{
    public static int $captured = 0;

    /** @var array<int, array{int,int}> */
    public static array $guardCalls = [];

    public function canReport(int $entityId, int $reporterId): bool
    {
        self::$guardCalls[] = [$entityId, $reporterId];

        return $entityId !== 13;
    }

    public function capture(int $entityId): array
    {
        self::$captured++;

        return ['id' => $entityId];
    }

    public function render(array $snapshot): string
    {
        return '';
    }

    public function getReportedUserId(int $entityId): int
    {
        return 1;
    }

    public function getContentUrl(int $entityId): string
    {
        return '/';
    }
}

class UnguardedTestFormatter implements SnapshotFormatterInterface
{
    public function capture(int $entityId): array
    {
        return ['id' => $entityId];
    }

    public function render(array $snapshot): string
    {
        return '';
    }

    public function getReportedUserId(int $entityId): int
    {
        return 1;
    }

    public function getContentUrl(int $entityId): string
    {
        return '/';
    }
}

beforeEach(function () {
    GuardedTestFormatter::$captured = 0;
    GuardedTestFormatter::$guardCalls = [];

    $registry = app(ModerationRegistry::class);
    $registry->register('guardtest', 'Guard test', GuardedTestFormatter::class);
    $registry->register('noguardtest', 'No guard test', UnguardedTestFormatter::class);
});

function postGuardReport(string $topic, int $entityId, int $reasonId)
{
    return test()->postJson('/moderation/report', [
        'topic_key' => $topic,
        'entity_id' => $entityId,
        'reason_id' => $reasonId,
    ]);
}

it('refuses a report the topic guard rejects with 403 and stores nothing', function () {
    $reason = createReason('guardtest', 'Other');

    $this->actingAs(alice($this));
    postGuardReport('guardtest', 13, $reason->id)
        ->assertForbidden()
        ->assertJson(['success' => false]);

    expect(ModerationReport::count())->toBe(0);
});

it('does not capture a snapshot when the guard refuses', function () {
    $reason = createReason('guardtest', 'Other');

    $this->actingAs(alice($this));
    postGuardReport('guardtest', 13, $reason->id)->assertForbidden();

    expect(GuardedTestFormatter::$captured)->toBe(0);
});

it('passes the authenticated reporter id to the guard', function () {
    $reason = createReason('guardtest', 'Other');
    $user = alice($this);

    $this->actingAs($user);
    postGuardReport('guardtest', 7, $reason->id)->assertOk();

    expect(GuardedTestFormatter::$guardCalls)->toBe([[7, $user->id]]);
});

it('accepts a report the topic guard allows', function () {
    $reason = createReason('guardtest', 'Other');

    $this->actingAs(alice($this));
    postGuardReport('guardtest', 7, $reason->id)
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(ModerationReport::where('topic_key', 'guardtest')->where('entity_id', 7)->count())->toBe(1);
});

it('accepts reports on a topic whose formatter has no guard', function () {
    $reason = createReason('noguardtest', 'Other');

    $this->actingAs(alice($this));
    postGuardReport('noguardtest', 13, $reason->id)->assertOk();

    expect(ModerationReport::where('topic_key', 'noguardtest')->count())->toBe(1);
});
