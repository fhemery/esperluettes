<?php

namespace App\Domains\Fanart\Database\Seeders;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Support\FanartMode;
use App\Domains\Fanart\Public\Providers\FanartServiceProvider;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Database\Seeders\E2eStorySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Fanart fixtures for the E2E environment (see .env.e2e), mirrored in
 * `e2e/support/fixtures.ts`. All of them sit on story 1, published; the artists
 * are `confirmed` (the reader every spec drives) and `tech_admin`.
 */
class E2eFanartSeeder extends Seeder
{
    private const IMAGE = 'e2e/fixtures/gift-image.png';
    private const SOUND = 'e2e/fixtures/gift-sound-short.mp3';

    private MediaPublicApi $media;

    /** @var array<string, int> */
    private array $users = [];

    public function run(): void
    {
        if (Fanart::withTrashed()->exists()) {
            return;
        }

        $this->media = app(MediaPublicApi::class);
        $ids = DB::table('users')->pluck('id', 'email');
        foreach (['confirmed', 'tech-admin'] as $name) {
            $this->users[$name] = (int) $ids[$name . '@e2e.test'];
        }

        // Open submissions to every reader: the default mode is « designated ».
        // `author` writes stories 1 and 2, `confirmed` is the co-author of 2.
        $settings = app(SettingsPublicApi::class);
        foreach (['author', 'confirmed'] as $name) {
            $userId = (int) $ids[$name . '@e2e.test'];
            foreach ([FanartServiceProvider::KEY_IMAGES_MODE, FanartServiceProvider::KEY_AUDIO_MODE] as $key) {
                $settings->setValue($userId, FanartServiceProvider::TAB_STORIES, $key, FanartMode::Yes->value);
            }
        }

        $story = E2eStorySeeder::STORY_ID;
        $published = E2eStorySeeder::CHAPTER_ID;
        $simple = E2eStorySeeder::SIMPLE_CHAPTER_ID;

        // Whole work and chapter 3 (a group readers have not read: spoiler-collapsed).
        $this->image($story, null, 'confirmed', 'Fanart oeuvre E2E');
        $this->image($story, $simple, 'tech-admin', 'Fanart chapitre trois E2E');

        // One sound on chapters 1 and 3 (story-page player queue).
        $this->audio($story, $published, 'confirmed', 'Son chapitre un E2E');
        $this->audio($story, $simple, 'tech-admin', 'Son chapitre trois E2E');
    }

    private function image(int $storyId, ?int $chapterId, string $artist, string $title): void
    {
        $path = $this->media->storePrivate('fanart/' . $storyId, $this->fixture(self::IMAGE, 'image/png'), [400, 800]);
        $this->row($storyId, $chapterId, $artist, $title, FanartKind::Image, $path);
    }

    private function audio(int $storyId, ?int $chapterId, string $artist, string $title): void
    {
        $path = $this->media->storePrivateFile('fanart/' . $storyId, $this->fixture(self::SOUND, 'audio/mpeg'));
        $this->row($storyId, $chapterId, $artist, $title, FanartKind::Audio, $path);
    }

    private function row(int $storyId, ?int $chapterId, string $artist, string $title, FanartKind $kind, string $path): void
    {
        Fanart::create([
            'story_id' => $storyId,
            'chapter_id' => $chapterId,
            'user_id' => $this->users[$artist],
            'kind' => $kind,
            'status' => FanartStatus::Published,
            'is_artwork' => false,
            'title' => $title,
            'description' => '<p>Description E2E</p>',
            'file_path' => $path,
            'share_on_discord' => false,
            'published_at' => now(),
        ]);
    }

    private function fixture(string $relativePath, string $mime): UploadedFile
    {
        $path = base_path($relativePath);

        return new UploadedFile($path, basename($path), $mime, null, true);
    }
}
