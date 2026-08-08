<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Calendar\Private\Activities\SecretGift\Http\Requests\SavePreferencesRequest;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftService;
use App\Domains\Calendar\Private\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Mews\Purifier\Facades\Purifier;

/**
 * A reader's three write actions on their own enrolment: join, edit their
 * preferences, leave.
 *
 * Every action re-derives the registration window itself rather than trusting
 * that a page was rendered offering the action — a forged POST never went past
 * a rendered page. Same posture as `QuoteContestEntryController`.
 */
class SecretGiftParticipantController
{
    public function __construct(
        private readonly SecretGiftService $service,
        private readonly SecretGiftConfigService $config,
        private readonly AuthPublicApi $auth,
    ) {}

    public function store(SavePreferencesRequest $request, Activity $activity): RedirectResponse
    {
        $this->assertMayEnrol($activity);

        $this->service->join($activity, (int) Auth::id(), $this->purifiedPreferences($request));

        return back()->with('success', __('secret-gift::secret-gift.flash.joined'));
    }

    public function update(SavePreferencesRequest $request, Activity $activity): RedirectResponse
    {
        $this->assertMayEnrol($activity);

        $participant = $this->service->getParticipant($activity->id, (int) Auth::id());
        if ($participant === null) {
            abort(403);
        }

        $this->service->updatePreferences($participant, $this->purifiedPreferences($request));

        return back()->with('success', __('secret-gift::secret-gift.flash.preferences_saved'));
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->assertMayEnrol($activity);

        $this->service->leave($activity, (int) Auth::id());

        return back()->with('success', __('secret-gift::secret-gift.flash.left'));
    }

    /**
     * The two gates every enrolment write shares.
     *
     * `role_restrictions` has to be re-checked here: the `web, auth, verified`
     * route middleware does not look at it — only
     * `ActivityService::findVisibleBySlugOrFail()` does, and these routes never
     * call it. Without this check a reader excluded from the activity page could
     * still enrol by posting the activity id.
     */
    private function assertMayEnrol(Activity $activity): void
    {
        if (! $this->auth->hasAnyRole((array) ($activity->role_restrictions ?? []))) {
            abort(404);   // same posture as findVisibleBySlugOrFail()
        }

        if (! $this->config->isRegistrationOpen($activity)) {
            abort(403);   // the page never offers the action; reaching it means a forged request
        }
    }

    /**
     * Preferences are authored in the shared rich-text editor and rendered back
     * as HTML, so they go through the same `strict` Purifier profile as
     * `gift_text`. Nothing left after sanitising is stored as `null`.
     */
    private function purifiedPreferences(SavePreferencesRequest $request): ?string
    {
        $text = (string) ($request->validated()['preferences'] ?? '');

        if (trim($text) === '') {
            return null;
        }

        $purified = Purifier::clean($text, 'strict');

        return trim(strip_tags($purified)) === '' ? null : $purified;
    }
}
