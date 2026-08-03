<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestEntry;
use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestVote;
use App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\EntryRemovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function moderationDelete(TestCase $t, object $contest, int $entryId)
{
    return $t->delete(route('quote-contest.moderation.entries.destroy', [$contest->id, $entryId]));
}

function makeRawVote(int $categoryId, int $entryId, int $userId): QuoteContestVote
{
    return QuoteContestVote::create([
        'category_id' => $categoryId,
        'entry_id' => $entryId,
        'user_id' => $userId,
    ]);
}

describe('Moderation deletion — who may', function () {

    it('lets a moderator delete an entry in every phase of the contest', function () {
        // Spec §4.6.3: deletion is allowed at any point in the contest's life,
        // not only while a given phase is open.
        $submitter = carol($this);
        $moderator = moderator($this);

        $contests = [
            createContestBeforeStart($this),
            createContestInSubmissions($this, ['name' => 'Concours en soumissions']),
            createContestInInterlude($this, ['name' => 'Concours en entre-deux']),
            createContestInVoting($this, ['name' => 'Concours en votes']),
            createContestEnded($this, ['name' => 'Concours terminé']),
        ];

        foreach ($contests as $contest) {
            $category = makeCategory($contest->id, 'La plus drôle');
            $entry = makeEntryIn($category, ['user_id' => $submitter->id]);

            $this->actingAs($moderator);
            moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

            expect(QuoteContestEntry::query()->find($entry->id))->toBeNull();
        }
    });

    it('lets an admin delete an entry', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs(admin($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('lets a tech admin delete an entry', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs(techAdmin($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });

    it('refuses a confirmed user with a 403 and changes nothing', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        $this->actingAs(bob($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(1);
    });

    it('refuses the submitter deleting their own entry through the moderation route', function () {
        $submitter = carol($this);
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $submitter->id]);

        $this->actingAs($submitter);
        moderationDelete($this, $contest, (int) $entry->id)->assertForbidden();

        expect(QuoteContestEntry::query()->count())->toBe(1);
    });

    it('refuses a guest', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => carol($this)->id]);

        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect(route('login'));

        expect(QuoteContestEntry::query()->count())->toBe(1);
    });
});

describe('Moderation deletion — what it does', function () {

    it('drops every vote cast on the deleted entry', function () {
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $deleted = makeEntryIn($category, ['user_id' => carol($this)->id]);
        $kept = makeEntryIn($category, ['user_id' => daniel($this)->id]);

        makeRawVote((int) $category->id, (int) $deleted->id, bob($this)->id);
        makeRawVote((int) $category->id, (int) $kept->id, alice($this)->id);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $deleted->id)->assertRedirect();

        // The FK cascades: no orphan ballot survives its entry.
        expect(QuoteContestVote::query()->where('entry_id', $deleted->id)->count())->toBe(0)
            ->and(QuoteContestVote::query()->where('entry_id', $kept->id)->count())->toBe(1);
    });

    it('frees the category slot so the submitter may enter another quote', function () {
        $author = alice($this);
        $submitter = bob($this);
        $story = publicStory('Mon histoire', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre premier']);
        $first = createQuote($submitter->id, $chapter->id, $story->id, ['highlighted_text' => 'Premier passage']);
        $second = createQuote($submitter->id, $chapter->id, $story->id, ['highlighted_text' => 'Second passage']);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $submit = fn (int $quoteId) => $this->post(route('quote-contest.entries.store', $contest->id), [
            'category_id' => $category->id,
            'quote_id' => $quoteId,
        ]);

        $this->actingAs($submitter);
        $submit($first->id)->assertRedirect();
        $entry = QuoteContestEntry::query()->sole();

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        $this->actingAs($submitter);
        $submit($second->id)->assertRedirect();

        $replacement = QuoteContestEntry::query()->sole();

        expect((string) $replacement->highlighted_text)->toBe('Second passage')
            ->and((int) $replacement->user_id)->toBe($submitter->id);
    });

    it('refuses an unknown entry', function () {
        $contest = createContestInVoting($this);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, 999999)->assertForbidden();
    });

    it('deletes an entry already withdrawn for privacy', function () {
        // A withdrawn row is still evidence a moderator may clear away.
        $contest = createContestInVoting($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, [
            'user_id' => carol($this)->id,
            'withdrawn_at' => now()->subHour(),
        ]);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(0);
    });
});

describe('Moderation deletion — the submitter is told', function () {

    it('notifies the submitter that their entry was removed', function () {
        $submitter = carol($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La catégorie arbitrée');
        $entry = makeEntryIn($category, ['user_id' => $submitter->id]);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        $notification = getLatestNotificationByKey(EntryRemovedNotification::type());

        expect($notification)->not->toBeNull()
            ->and(getNotificationTargetUserIds((int) $notification->id))->toBe([$submitter->id]);
    });

    it('notifies nobody but the submitter', function () {
        $submitter = carol($this);
        $bystander = daniel($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La catégorie arbitrée');
        $mine = makeEntryIn($category, ['user_id' => $submitter->id]);
        makeEntryIn($category, ['user_id' => $bystander->id]);

        $moderator = moderator($this);
        $this->actingAs($moderator);
        moderationDelete($this, $contest, (int) $mine->id)->assertRedirect();

        $notification = getLatestNotificationByKey(EntryRemovedNotification::type());
        $targets = getNotificationTargetUserIds((int) $notification->id);

        expect($targets)->not->toContain($bystander->id)
            ->and($targets)->not->toContain($moderator->id)
            ->and(countNotificationsByKey(EntryRemovedNotification::type()))->toBe(1);
    });

    it('names the category and links to the contest, and carries nothing else', function () {
        $submitter = carol($this);
        $contest = createContestInSubmissions($this, ['name' => 'Concours arbitré']);
        $category = makeCategory($contest->id, 'La catégorie arbitrée');
        $entry = makeEntryIn($category, [
            'user_id' => $submitter->id,
            'highlighted_text' => 'Le passage litigieux',
        ]);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        $notification = getLatestNotificationByKey(EntryRemovedNotification::type());
        $data = $notification->content_data;
        $data = is_array($data) ? $data : json_decode((string) $data, true);

        expect(array_keys($data))->toEqualCanonicalizing(['category_title', 'activity_slug', 'activity_name'])
            ->and($data['category_title'])->toBe('La catégorie arbitrée')
            ->and($data['activity_name'])->toBe('Concours arbitré')
            // The passage itself is context the notification has no business
            // repeating outside the contest page.
            ->and(json_encode($data))->not->toContain('Le passage litigieux');
    });

    it('never puts the reader private note in the notification', function () {
        // Assumption A1: the note never enters the contest at all, so it cannot
        // reach the notification either.
        $author = alice($this);
        $submitter = bob($this);
        $story = publicStory('Mon histoire', $author->id);
        $chapter = createPublishedChapter($this, $story, $author, ['title' => 'Chapitre premier']);
        $quote = createQuote($submitter->id, $chapter->id, $story->id, [
            'highlighted_text' => 'Un passage à soumettre',
            'note' => 'Ma note strictement privée',
        ]);

        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs($submitter)
            ->post(route('quote-contest.entries.store', $contest->id), [
                'category_id' => $category->id,
                'quote_id' => $quote->id,
            ])->assertRedirect();

        $entry = QuoteContestEntry::query()->sole();

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        $notification = getLatestNotificationByKey(EntryRemovedNotification::type());
        $data = $notification->content_data;
        $payload = json_encode(is_array($data) ? $data : json_decode((string) $data, true));

        expect($payload)->not->toContain('Ma note strictement privée')
            // Nothing of the passage's private context either (§3.4).
            ->and($payload)->not->toContain('Un passage à soumettre');
    });

    it('notifies nobody when the submitter no longer exists', function () {
        // Decision #7: the entry of a deleted user stays, and stays deletable.
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => 999999]);

        $this->actingAs(moderator($this));
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        expect(QuoteContestEntry::query()->count())->toBe(0)
            ->and(countNotificationsByKey(EntryRemovedNotification::type()))->toBe(0);
    });

    it('does not notify a moderator who deletes their own entry', function () {
        $moderator = moderator($this);
        $contest = createContestInSubmissions($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        $entry = makeEntryIn($category, ['user_id' => $moderator->id]);

        $this->actingAs($moderator);
        moderationDelete($this, $contest, (int) $entry->id)->assertRedirect();

        expect(countNotificationsByKey(EntryRemovedNotification::type()))->toBe(0);
    });
});

describe('Moderation notification — French wording', function () {

    it('words the removal notice in French', function () {
        $rendered = trans('quote-contest::quote-contest.notification.entry_removed.display', [
            'category' => 'La plus drôle',
            'activity_name' => 'Concours de citations',
            'activity_url' => '/calendrier/concours-de-citations',
        ], 'fr');

        expect($rendered)
            ->toContain('La plus drôle')
            ->toContain('Concours de citations')
            ->toContain('/calendrier/concours-de-citations')
            ->toContain('modération')
            // The group is Calendar-wide, so its label is the domain's, not the
            // sub-module's (open item O3).
            ->and(trans('calendar::notification.groups.calendar', [], 'fr'))->toBe('Calendrier');
    });
});
