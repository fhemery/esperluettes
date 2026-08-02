<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('Quote contest access', function () {

    it('404s the contest page for a non-confirmed user', function () {
        // Decision #1 and assumption A5: the gate is the activity's own
        // `role_restrictions`, not per-action code. This proves the config.
        $contest = createContestInSubmissions($this);

        $this->actingAs(alice($this, roles: [Roles::USER]))
            ->get($contest->url)
            ->assertNotFound();
    });

    it('hides the contest from the activity listing of a non-confirmed user', function () {
        createContestInSubmissions($this, ['name' => 'Concours des plus belles phrases']);

        $this->actingAs(alice($this, roles: [Roles::USER]));

        expect(Blade::render('<x-calendar::activity-list-component />'))
            ->not->toContain('Concours des plus belles phrases');
    });

    it('lists the contest for a confirmed user', function () {
        createContestInSubmissions($this, ['name' => 'Concours des plus belles phrases']);

        $this->actingAs(alice($this, roles: [Roles::USER_CONFIRMED]));

        expect(Blade::render('<x-calendar::activity-list-component />'))
            ->toContain('Concours des plus belles phrases');
    });

    it('redirects a guest to the login page', function () {
        $contest = createContestInSubmissions($this);
        Auth::logout();

        $this->get($contest->url)->assertRedirect('/login');
    });

    it('shows the description and the categories to a confirmed user', function () {
        $contest = createContestInSubmissions($this, [
            'description' => '<p>Le règlement du concours.</p>',
        ]);
        makeCategory($contest->id, 'La plus drôle', 1);
        makeCategory($contest->id, 'La plus émouvante', 2);

        $this->actingAs(bob($this))
            ->get($contest->url)
            ->assertOk()
            ->assertSee('Le règlement du concours.', false)
            ->assertSee('La plus drôle', false)
            ->assertSee('La plus émouvante', false);
    });

    it('offers Mes citations as the only tab', function () {
        // Votes and Résultats arrive in later phases: the tabs array must hold
        // exactly one entry, and the others must be absent from the DOM rather
        // than rendered and hidden.
        $contest = createContestInSubmissions($this);
        makeCategory($contest->id, 'La plus drôle');

        $html = $this->actingAs(bob($this))->get($contest->url)->assertOk()->getContent();

        expect(substr_count($html, 'role="tab"'))->toBe(1)
            ->and($html)->toContain('quote-contest::quote-contest.tab_my_quotes')
            ->and($html)->not->toContain('quote-contest::quote-contest.tab_votes')
            ->and($html)->not->toContain('quote-contest::quote-contest.tab_results');
    });

    it('shows the contest to a moderator and to an admin', function () {
        $contest = createContestInSubmissions($this);

        $this->actingAs(moderator($this))->get($contest->url)->assertOk();
        $this->actingAs(admin($this))->get($contest->url)->assertOk();
    });
});
