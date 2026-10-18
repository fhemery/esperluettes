<?php

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Moderation\Database\Seeders\ModerationSeeder;
use App\Domains\Moderation\Private\Models\ModerationReason;
use App\Domains\Moderation\Private\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('private');
    Storage::fake('public');
});

function reportFanart(TestCase $t, $reporter, Fanart $fanart): \Illuminate\Testing\TestResponse
{
    $reason = createReason('fanart', 'Contenu inapproprié');

    return $t->actingAs($reporter)->postJson('/moderation/report', [
        'topic_key' => 'fanart',
        'entity_id' => $fanart->id,
        'reason_id' => $reason->id,
    ]);
}

function reportScene(TestCase $t): array
{
    $author = alice($t);
    $artist = bob($t, ['name' => 'Artiste']);
    $story = publicStory('Histoire', $author->id);
    $chapter = createPublishedChapter($t, $story, $author, ['title' => 'Premier souffle']);

    return [$author, $artist, $story, $chapter];
}

it('lets a confirmed reader report a published fanart with a fanart reason', function () {
    [, $artist, $story] = reportScene($this);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $artist->id]);
    $reader = carol($this);

    reportFanart($this, $reader, $fanart)->assertOk();

    $report = ModerationReport::first();
    expect($report->topic_key)->toBe('fanart')
        ->and($report->entity_id)->toBe($fanart->id)
        ->and($report->reported_user_id)->toBe($artist->id)
        ->and($report->content_url)->toContain('/stories/' . $story->slug . '?tab=fanarts&fanart=' . $fanart->id)
        ->and(Fanart::find($fanart->id))->not->toBeNull();
});

it('stores a copy of the image in moderation/fanart with title, artist, story, chapter', function () {
    [, $artist, $story, $chapter] = reportScene($this);
    $fanart = createPublishedImage([
        'story_id' => $story->id, 'chapter_id' => $chapter->id, 'user_id' => $artist->id, 'title' => 'Mon dessin',
    ]);

    reportFanart($this, carol($this), $fanart)->assertOk();

    $snapshot = ModerationReport::first()->content_snapshot;
    expect($snapshot['title'])->toBe('Mon dessin')
        ->and($snapshot['artist_id'])->toBe($artist->id)
        ->and($snapshot['artist_name'])->not->toBe('')
        ->and($snapshot['story_title'])->toBe('Histoire')
        ->and($snapshot['chapter_title'])->toBe('Premier souffle')
        ->and($snapshot['_media'])->toHaveCount(1)
        ->and($snapshot['_media'][0])->toStartWith('moderation/fanart/');
    Storage::disk('private')->assertExists($snapshot['_media'][0]);
});

it('renders the copied image for a moderator even after the fanart is deleted', function () {
    [, $artist, $story] = reportScene($this);
    $fanart = createPublishedImage(['story_id' => $story->id, 'user_id' => $artist->id, 'title' => 'Mon dessin']);
    reportFanart($this, carol($this), $fanart)->assertOk();
    $report = ModerationReport::first();

    $fanart->forceDelete();
    Storage::disk('private')->delete($fanart->file_path);

    $this->actingAs(moderator($this))
        ->get(route('moderation.admin.moderation-reports.show', $report))
        ->assertOk()
        ->assertSee('Mon dessin')
        ->assertSee(route('moderation.admin.moderation-reports.media', [$report, 0]), false)
        ->assertSee('<img', false);
});

it('removes a pending fanart reported by an author and sends the decline notification without comment', function () {
    [$author, $artist, $story] = reportScene($this);
    $fanart = createPublishedImage([
        'story_id' => $story->id, 'user_id' => $artist->id,
        'status' => FanartStatus::Pending, 'published_at' => null,
    ]);

    reportFanart($this, $author, $fanart)->assertOk();

    expect(Fanart::withTrashed()->find($fanart->id))->toBeNull()
        ->and(ModerationReport::count())->toBe(1)
        ->and(ModerationReport::first()->content_snapshot['_media'])->toHaveCount(1)
        ->and(countNotificationsByKey('fanart.declined'))->toBe(1)
        ->and(getNotificationTargetUserIds(getLatestNotificationByKey('fanart.declined')->id))->toBe([$artist->id]);
    $content = getLatestNotificationByKey('fanart.declined')->content_data ?? [];
    expect($content['comment'] ?? null)->toBeNull();
});

it('keeps a pending fanart reported by its submitter or by staff', function () {
    [, $artist, $story] = reportScene($this);
    $fanart = createFanart([
        'story_id' => $story->id, 'user_id' => $artist->id,
        'status' => FanartStatus::Pending, 'published_at' => null,
    ]);

    reportFanart($this, $artist, $fanart)->assertOk();
    reportFanart($this, moderator($this), $fanart)->assertOk();

    expect(ModerationReport::count())->toBe(2)
        ->and(Fanart::find($fanart->id))->not->toBeNull()
        ->and(countNotificationsByKey('fanart.declined'))->toBe(0);
});

it('keeps a published fanart reported by an author', function () {
    [$author, $artist, $story] = reportScene($this);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);

    reportFanart($this, $author, $fanart)->assertOk();

    expect(Fanart::find($fanart->id))->not->toBeNull()
        ->and(countNotificationsByKey('fanart.declined'))->toBe(0);
});

it('does not delete the fanart when its report is approved', function () {
    [, $artist, $story] = reportScene($this);
    $fanart = createFanart(['story_id' => $story->id, 'user_id' => $artist->id]);
    reportFanart($this, carol($this), $fanart)->assertOk();

    $this->actingAs(moderator($this))
        ->post(route('moderation.admin.moderation-reports.approve', ModerationReport::first()));

    expect(Fanart::find($fanart->id))->not->toBeNull();
});

it('lists fanart reasons in admin reasons management', function () {
    $this->seed(ModerationSeeder::class);

    expect(ModerationReason::where('topic_key', 'fanart')->where('label', 'Other')->count())->toBe(1);

    $this->actingAs(moderator($this))
        ->get(route('moderation.admin.moderation-reasons.index', ['topic_key' => 'fanart']))
        ->assertOk()
        ->assertSee(__('fanart::moderation.topic_fanart'));
});
