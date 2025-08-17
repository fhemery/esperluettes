<?php

namespace App\Domains\Story\Http\Controllers;

use App\Domains\Story\Http\Requests\StoryRequest;
use App\Domains\Story\Models\Story;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoryController
{
    public function store(StoryRequest $request): RedirectResponse
    {
        $userId = Auth::id();

        return DB::transaction(function () use ($request, $userId) {
            // 1) Create story with temporary slug
            $title = (string) $request->input('title');
            $description = (string) $request->input('description');
            $visibility = (string) $request->input('visibility');
            $slugBase = Story::generateSlugBase($title);
            $story = new Story([
                'created_by_user_id' => $userId,
                'title' => $title,
                'slug' => $slugBase, // temporary
                'description' => $description,
                'visibility' => $visibility,
            ]);
            $story->save();

            // 2) Update slug with id suffix
            $story->slug = $slugBase . '-' . $story->id;
            $story->save();

            // 3) Seed collaborator row for creator
            DB::table('story_collaborators')->insert([
                'story_id' => $story->id,
                'user_id' => $userId,
                'role' => 'author',
                'invited_by_user_id' => $userId,
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            return redirect()->to('/stories/' . $story->slug)
                ->with('status', __('Story created successfully.'));
        });
    }

    public function show(string $slug): View
    {
        // Extract id from trailing -{id}
        $id = null;
        if (preg_match('/-(\d+)$/', $slug, $m)) {
            $id = (int) $m[1];
        }

        $story = $id ? Story::query()->findOrFail($id) : Story::query()->where('slug', $slug)->firstOrFail();

        $isAuthor = false;
        if (Auth::check()) {
            $isAuthor = DB::table('story_collaborators')
                ->where('story_id', $story->id)
                ->where('user_id', Auth::id())
                ->where('role', 'author')
                ->exists();
        }

        return view('story::show', [
            'story' => $story,
            'isAuthor' => $isAuthor,
        ]);
    }
}
