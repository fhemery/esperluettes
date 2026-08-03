<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SubmissionRefusedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The one moderation write of the contest: delete any entry, at any point in
 * the contest's life (spec §4.6.3).
 *
 * The role check lives here rather than in route middleware because the same
 * three roles gate the *Résultats* tab, which the view component builds — the
 * two must not drift, so they read one constant (architecture §3.3, point 4).
 */
class QuoteContestModerationController
{
    /**
     * Moderation of user content, which in this codebase always means these
     * three roles together (Moderation, Story, FAQ and Auth all read the same
     * list). Calendar's `[admin, tech-admin]` gates *configuration*, which is a
     * different question.
     */
    public const ROLES = [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN];

    public function __construct(
        private readonly QuoteContestSubmissionService $submissions,
        private readonly AuthPublicApi $auth,
    ) {}

    public function destroy(int $activityId, int $entryId): RedirectResponse
    {
        if (! $this->auth->hasAnyRole(self::ROLES)) {
            abort(403);
        }

        try {
            $this->submissions->deleteEntryAsModerator($entryId, (int) Auth::id());
        } catch (SubmissionRefusedException) {
            abort(403);
        }

        return back()
            ->withFragment('results')
            ->with('success', __('quote-contest::quote-contest.flash.entry_deleted'));
    }
}
