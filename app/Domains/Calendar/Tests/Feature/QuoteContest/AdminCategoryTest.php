<?php

use App\Domains\Calendar\Private\Activities\QuoteContest\Models\QuoteContestCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// `makeCategory()` and `makeEntryIn()` live in this folder's helpers.php.

describe('Quote contest category administration', function () {

    it('lets an admin add, edit and reorder categories', function () {
        $contest = createQuoteContest($this);
        $this->actingAs(admin($this));

        $this->post(route('calendar.admin.quote-contest.categories.store', $contest->id), [
            'title' => 'La plus drôle',
            'description' => 'Les citations qui font rire.',
        ])->assertSessionHasNoErrors();

        $this->post(route('calendar.admin.quote-contest.categories.store', $contest->id), [
            'title' => 'La plus émouvante',
        ])->assertSessionHasNoErrors();

        $categories = QuoteContestCategory::query()->where('activity_id', $contest->id)
            ->orderBy('position')->get();

        expect($categories->pluck('title')->all())->toBe(['La plus drôle', 'La plus émouvante'])
            ->and($categories->pluck('position')->all())->toBe([1, 2]);

        // Edit the first one, title and description.
        $this->put(route('calendar.admin.quote-contest.categories.update', [$contest->id, $categories[0]->id]), [
            'title' => 'La plus cocasse',
            'description' => 'Les citations qui font vraiment rire.',
            'position' => 1,
        ])->assertSessionHasNoErrors();

        expect($categories[0]->fresh()->title)->toBe('La plus cocasse')
            ->and($categories[0]->fresh()->description)->toBe('Les citations qui font vraiment rire.');

        // Reorder: the second becomes the first.
        $this->put(route('calendar.admin.quote-contest.categories.update', [$contest->id, $categories[1]->id]), [
            'title' => 'La plus émouvante',
            'position' => 0,
        ])->assertSessionHasNoErrors();

        $reordered = QuoteContestCategory::query()->where('activity_id', $contest->id)
            ->orderBy('position')->pluck('title')->all();

        expect($reordered)->toBe(['La plus émouvante', 'La plus cocasse']);
    });

    it('refuses a category without a title, in French', function () {
        $contest = createQuoteContest($this);

        $this->actingAs(admin($this))
            ->post(route('calendar.admin.quote-contest.categories.store', $contest->id), ['title' => ''])
            ->assertSessionHasErrors([
                'title' => 'quote-contest::quote-contest.validation.category_title_required',
            ]);

        expect(QuoteContestCategory::query()->count())->toBe(0);
    });

    it('deletes an empty category', function () {
        $contest = createQuoteContest($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        $this->actingAs(admin($this))
            ->delete(route('calendar.admin.quote-contest.categories.destroy', [$contest->id, $category->id]))
            ->assertSessionHas('success');

        expect(QuoteContestCategory::query()->count())->toBe(0);
    });

    it('refuses to delete a category holding an entry, with a message', function () {
        $contest = createQuoteContest($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category);

        $this->actingAs(admin($this))
            ->delete(route('calendar.admin.quote-contest.categories.destroy', [$contest->id, $category->id]))
            ->assertSessionHas('error', 'quote-contest::quote-contest.flash.category_not_empty');

        expect(QuoteContestCategory::query()->count())->toBe(1);
    });

    it('refuses to delete a category holding only a withdrawn entry', function () {
        $contest = createQuoteContest($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category, ['withdrawn_at' => now()->subDay()]);

        $this->actingAs(admin($this))
            ->delete(route('calendar.admin.quote-contest.categories.destroy', [$contest->id, $category->id]))
            ->assertSessionHas('error', 'quote-contest::quote-contest.flash.category_not_empty');

        expect(QuoteContestCategory::query()->count())->toBe(1);
    });

    it('never touches a category belonging to another contest', function () {
        $contest = createQuoteContest($this);
        $other = createQuoteContest($this, ['name' => 'Autre concours']);
        $category = makeCategory($other->id, 'La plus drôle');

        $this->actingAs(admin($this))
            ->delete(route('calendar.admin.quote-contest.categories.destroy', [$contest->id, $category->id]))
            ->assertNotFound();

        expect(QuoteContestCategory::query()->count())->toBe(1);
    });

    it('denies every category route to a confirmed user and to a moderator', function () {
        $contest = createQuoteContest($this);
        $category = makeCategory($contest->id, 'La plus drôle');

        foreach ([alice($this), moderator($this)] as $intruder) {
            $this->actingAs($intruder);

            $this->post(route('calendar.admin.quote-contest.categories.store', $contest->id), [
                'title' => 'Ajout interdit',
            ])->assertRedirect(route('dashboard'));

            $this->put(route('calendar.admin.quote-contest.categories.update', [$contest->id, $category->id]), [
                'title' => 'Édition interdite',
            ])->assertRedirect(route('dashboard'));

            $this->delete(route('calendar.admin.quote-contest.categories.destroy', [$contest->id, $category->id]))
                ->assertRedirect(route('dashboard'));
        }

        expect(QuoteContestCategory::query()->count())->toBe(1)
            ->and($category->fresh()->title)->toBe('La plus drôle');
    });

    it('lists the categories with their entry count on the activity edit form', function () {
        $contest = createQuoteContest($this);
        $category = makeCategory($contest->id, 'La plus drôle');
        makeEntryIn($category);

        $this->actingAs(admin($this))
            ->get(route('calendar.admin.activities.edit', \App\Domains\Calendar\Private\Models\Activity::findOrFail($contest->id)))
            ->assertOk()
            ->assertSee('La plus drôle', false)
            ->assertSee('quote-contest::quote-contest.config.categories_title', false)
            ->assertSee(route('calendar.admin.quote-contest.categories.update', [$contest->id, $category->id]), false);
    });

    it('words every category message in French', function () {
        $fr = fn (string $key) => trans('quote-contest::quote-contest.' . $key, [], 'fr');

        expect($fr('flash.category_not_empty'))
            ->toBe('Impossible de supprimer cette catégorie : elle contient déjà des citations, retirées ou non.')
            ->and($fr('validation.category_title_required'))
            ->toBe('Le titre de la catégorie est obligatoire.')
            ->and($fr('config.categories_title'))->toBe('Catégories');
    });
});
