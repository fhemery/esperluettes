<?php

namespace App\Domains\Announcement\Controllers;

use App\Domains\Announcement\Models\Announcement;
use App\Domains\Announcement\Services\AnnouncementService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

class AnnouncementController extends BaseController
{
    public function index(AnnouncementService $service): View
    {
        $announcements = Announcement::query()
            ->published()
            ->orderForListing()
            ->paginate(12);

        $pinned = $service->getPinnedForCarousel();

        return view('announcements.index', [
            'announcements' => $announcements,
            'pinned' => $pinned,
        ]);
    }

    public function show(string $slug): View
    {
        $announcement = Announcement::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return view('announcements.show', [
            'announcement' => $announcement,
        ]);
    }
}
