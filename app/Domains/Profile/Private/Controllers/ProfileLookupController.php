<?php

declare(strict_types=1);

namespace App\Domains\Profile\Private\Controllers;

use App\Domains\Profile\Private\Services\ProfileService;
use App\Domains\Profile\Private\Services\ProfileAvatarUrlService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

class ProfileLookupController extends Controller
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly ProfileAvatarUrlService $avatars,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 25);
        if (mb_strlen($q) < 2) {
            return response()->json(['profiles' => [], 'total' => 0]);
        }
        $cap = max(1, min(25, $limit));
        $page = $this->profiles->listProfiles(search: $q, page: 1, perPage: $cap);

        $items = [];
        foreach ($page->items() as $p) {
            $items[] = [
                'id' => (int) $p->user_id,
                'display_name' => (string) ($p->display_name ?? ''),
                'avatar_url' => $this->avatars->publicUrl($p->profile_picture_path, (int) $p->user_id),
            ];
        }

        return response()->json([
            'profiles' => $items,
            'total' => (int) $page->total(),
        ]);
    }

    public function byIds(Request $request): JsonResponse
    {
        $idsParam = (string) $request->query('ids', '');
        $ids = array_values(array_unique(array_filter(array_map(fn($v) => (int) $v, explode(',', $idsParam)), fn($v) => $v > 0)));
        if (empty($ids)) {
            return response()->json(['profiles' => []]);
        }
        $profiles = $this->profiles->getProfilesByUserIds($ids);
        $out = [];
        foreach ($ids as $id) {
            $p = $profiles[$id] ?? null;
            if ($p) {
                $out[] = [
                    'id' => (int) $p->user_id,
                    'display_name' => (string) ($p->display_name ?? ''),
                    'avatar_url' => $this->avatars->publicUrl($p->profile_picture_path, (int) $p->user_id),
                ];
            }
        }
        return response()->json(['profiles' => $out]);
    }
}
