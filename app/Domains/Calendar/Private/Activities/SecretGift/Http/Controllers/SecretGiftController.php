<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers;

use App\Domains\Calendar\Private\Activities\SecretGift\Http\Requests\SaveGiftRequest;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftService;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class SecretGiftController
{
    public function __construct(private SecretGiftService $service)
    {
    }

    public function saveGift(SaveGiftRequest $request, Activity $activity)
    {
        $userId = Auth::id();

        // Verify activity is active
        if ($activity->state !== ActivityState::ACTIVE) {
            return back()->with('error', __('secret-gift::secret-gift.not_active'));
        }

        $assignment = $this->service->getAssignmentAsGiver($activity->id, $userId);

        if (!$assignment) {
            abort(403);
        }

        // Handle text
        if ($request->has('gift_text')) {
            $text = $request->input('gift_text');
            $purified = $text ? Purifier::clean($text, 'strict') : null;
            $this->service->saveGiftText($assignment, $purified);
        }

        // Handle image removal
        if ($request->boolean('gift_image_remove')) {
            $this->service->removeGiftImage($assignment);
        }

        // Handle new image upload
        if ($request->hasFile('gift_image')) {
            $this->service->saveGiftImage($assignment, $request->file('gift_image'));
        }

        return back()->with('success', __('secret-gift::secret-gift.gift_saved'));
    }

    public function serveImage(Activity $activity, SecretGiftAssignment $assignment)
    {
        $userId = Auth::id();

        if (!$this->service->canViewImage($assignment, $userId, $activity)) {
            abort(403);
        }

        if (!$assignment->gift_image_path) {
            abort(404);
        }

        $path = $assignment->gift_image_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $content = Storage::disk('local')->get($path);
        $mimeType = Storage::disk('local')->mimeType($path);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
