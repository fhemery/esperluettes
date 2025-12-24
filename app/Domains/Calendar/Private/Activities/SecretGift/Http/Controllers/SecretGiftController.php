<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers;

use App\Domains\Calendar\Private\Activities\SecretGift\Http\Requests\SaveGiftRequest;
use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
use App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftService;
use App\Domains\Calendar\Private\Models\Activity;
use App\Domains\Calendar\Public\Contracts\ActivityState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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

        // Handle sound removal
        if ($request->boolean('gift_sound_remove')) {
            $this->service->removeGiftSound($assignment);
        }

        // Handle new sound upload
        if ($request->hasFile('gift_sound')) {
            $this->service->saveGiftSound($assignment, $request->file('gift_sound'));
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
        $fullPath = Storage::disk('local')->path($path);
        $mimeType = File::mimeType($fullPath);
        
        // Check if this is a download request
        $isDownload = request()->routeIs('secret-gift.download-image');
        
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ];
        
        if ($isDownload) {
            $filename = 'gift-image-' . $assignment->giver_user_id . '-' . $assignment->id . '.' . pathinfo($path, PATHINFO_EXTENSION);
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        }

        return response($content, 200, $headers);
    }

    public function streamSound(Activity $activity, SecretGiftAssignment $assignment)
    {
        $userId = Auth::id();
        
        // Refresh activity to get latest state
        $activity->refresh();

        if (!$this->service->canViewSound($assignment, $userId, $activity)) {
            abort(403);
        }

        if (!$assignment->gift_sound_path) {
            abort(404);
        }

        $path = $assignment->gift_sound_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('local')->path($path);
        $fileSize = filesize($fullPath);
        $mimeType = File::mimeType($fullPath);
        
        // Handle Range requests for proper audio playback
        $range = request()->header('Range');
        
        if (!$range) {
            // No range request - stream with proper headers
            $stream = Storage::disk('local')->readStream($path);
            
            return response()->stream(function() use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }
        
        // Handle Range request for seeking
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int)$matches[1];
            $end = $matches[2] ? (int)$matches[2] : $fileSize - 1;
            $length = $end - $start + 1;
            
            if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
                return response('Invalid Range', 416);
            }
            
            $stream = fopen($fullPath, 'rb');
            fseek($stream, $start);
            
            return response()->stream(function() use ($stream, $length) {
                echo fread($stream, $length);
                fclose($stream);
            }, 206, [
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }
        
        // Fallback to full response
        $stream = Storage::disk('local')->readStream($path);
        
        return response()->stream(function() use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function downloadSound(Activity $activity, SecretGiftAssignment $assignment)
    {
        $userId = Auth::id();
        
        // Refresh activity to get latest state
        $activity->refresh();

        if (!$this->service->canViewSound($assignment, $userId, $activity)) {
            abort(403);
        }

        if (!$assignment->gift_sound_path) {
            abort(404);
        }

        $path = $assignment->gift_sound_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('local')->path($path);
        $filename = 'gift-audio-' . $assignment->giver_user_id . '-' . $assignment->id . '.' . pathinfo($path, PATHINFO_EXTENSION);
        
        return response()->download($fullPath, $filename, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
