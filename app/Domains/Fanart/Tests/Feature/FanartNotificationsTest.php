<?php

use App\Domains\Fanart\Private\Services\FanartNotifier;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const FANART_NOTIFICATION_TYPES = [
    'fanart.submitted', 'fanart.accepted', 'fanart.declined', 'fanart.deleted',
    'fanart.deleted_by_moderation', 'fanart.audio_replaced', 'fanart.removed_with_content',
    'fanart.role_requested', 'fanart.role_given', 'fanart.role_declined',
];

/** @return array{0: object, 1: object, 2: object, 3: object, 4: \App\Domains\Fanart\Private\Models\Fanart} */
function fanartScene(TestCase $t): array
{
    $author = alice($t);
    $artist = bob($t);
    $story = publicStory('Les Ombres', $author->id);
    $chapter = createPublishedChapter($t, $story, $author, ['title' => 'Premier souffle']);
    $fanart = createFanart([
        'story_id' => $story->id,
        'chapter_id' => $chapter->id,
        'user_id' => $artist->id,
        'title' => 'Portrait',
    ]);

    return [$author, $artist, $story, $chapter, $fanart];
}

function renderLatest(string $type): string
{
    app()->setLocale('fr'); // the testing env uses a raw-key locale
    $notification = getLatestNotificationByKey($type);
    expect($notification)->not->toBeNull();

    return app(NotificationPublicApi::class)->getNotificationsByIds([$notification->id])[$notification->id]->htmlDisplay;
}

it('registers the fanart group and ten types in the notification settings', function () {
    $factory = app(NotificationFactory::class);

    expect(collect($factory->getGroups())->pluck('id')->all())->toContain('fanart');

    $types = collect($factory->getTypesForGroup('fanart'))->pluck('type')->sort()->values()->all();
    $expected = collect(FANART_NOTIFICATION_TYPES)->sort()->values()->all();
    expect($types)->toBe($expected);
});

it('renders every type with its denormalised payload', function (string $type, Closure $send, array $expects) {
    [$author, $artist, $story, $chapter, $fanart] = fanartScene($this);
    $notifier = app(FanartNotifier::class);

    $send($notifier, $fanart, $author, $artist, $story);

    $html = renderLatest($type);
    foreach ($expects as $needle) {
        expect($html)->toContain($needle);
    }
})->with([
    'submitted' => ['fanart.submitted', fn ($n, $f, $a, $r, $s) => $n->fanartSubmitted($f, $r->id), ['Les Ombres', 'Portrait', 'une image']],
    'accepted' => ['fanart.accepted', fn ($n, $f, $a, $r, $s) => $n->fanartAccepted($f, $a->id, 'Bravo'), ['Portrait', 'Bravo']],
    'declined' => ['fanart.declined', fn ($n, $f, $a, $r, $s) => $n->fanartDeclined($f, $a->id, null), ['Portrait', 'refusé']],
    'deleted' => ['fanart.deleted', fn ($n, $f, $a, $r, $s) => $n->fanartDeleted($f, $a->id), ['Portrait', 'supprimé']],
    'audio_replaced' => ['fanart.audio_replaced', fn ($n, $f, $a, $r, $s) => $n->fanartAudioReplaced($f, $a->id), ['Portrait', 'Premier souffle']],
    'removed_with_content' => ['fanart.removed_with_content', fn ($n, $f, $a, $r, $s) => $n->fanartRemovedWithContent($f, 'Les Ombres', $s->slug, 'Premier souffle'), ['Portrait', 'Les Ombres']],
    'role_requested' => ['fanart.role_requested', fn ($n, $f, $a, $r, $s) => $n->fanartRoleRequested($s->id, $r->id), ['Les Ombres', 'fanartiste']],
    'role_given' => ['fanart.role_given', fn ($n, $f, $a, $r, $s) => $n->fanartRoleGiven($s->id, $r->id, $a->id), ['Les Ombres', 'fanartiste']],
    'role_declined' => ['fanart.role_declined', fn ($n, $f, $a, $r, $s) => $n->fanartRoleDeclined($s->id, $r->id, $a->id), ['Les Ombres', 'refusée']],
]);

it('renders deleted_by_moderation with its denormalised payload', function () {
    [, , , , $fanart] = fanartScene($this);

    app(FanartNotifier::class)->fanartDeletedByModeration($fanart, moderator($this)->id);

    expect(renderLatest('fanart.deleted_by_moderation'))->toContain('Portrait')->toContain('modération');
});

it('escapes the author comment', function () {
    [$author, , , , $fanart] = fanartScene($this);

    app(FanartNotifier::class)->fanartAccepted($fanart, $author->id, '<script>x</script>');

    expect(renderLatest('fanart.accepted'))->not->toContain('<script>')->toContain('&lt;script&gt;');
});

it('renders a notification whose story was deleted afterwards', function () {
    [$author, $artist, $story, , $fanart] = fanartScene($this);

    app(FanartNotifier::class)->fanartAccepted($fanart, $author->id, null);
    $story->delete();

    expect(renderLatest('fanart.accepted'))->toContain('Les Ombres')->toContain('Portrait');
});

it('never notifies the actor', function () {
    [$author, $artist, $story, , $fanart] = fanartScene($this);
    $coAuthor = carol($this);
    addCollaborator($story->id, $coAuthor->id);

    app(FanartNotifier::class)->fanartDeletedByModeration($fanart, $author->id);

    $targets = getNotificationTargetUserIds(getLatestNotificationByKey('fanart.deleted_by_moderation')->id);
    expect($targets)->toContain($coAuthor->id)->toContain($artist->id)->not->toContain($author->id);
});

it('sends nothing and throws nothing when the only recipient is the actor', function () {
    [$author, $artist, $story, , $fanart] = fanartScene($this);

    $notifier = app(FanartNotifier::class);
    $notifier->fanartSubmitted($fanart, $author->id);
    $notifier->fanartRoleRequested($story->id, $author->id);
    $notifier->fanartDeleted($fanart, $artist->id);

    expect(countNotificationsByKey('fanart.submitted'))->toBe(0)
        ->and(countNotificationsByKey('fanart.role_requested'))->toBe(0)
        ->and(countNotificationsByKey('fanart.deleted'))->toBe(0);
});
