<?php

namespace App\Domains\Fanart\Database\Seeders;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Models\FanartRoleRequest;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\FanartStatus;
use App\Domains\Media\Public\Api\MediaPublicApi;
use App\Domains\Story\Database\Seeders\E2eStorySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Fanart fixtures for the E2E environment (see .env.e2e), mirrored in
 * `e2e/support/fixtures.ts`.
 *
 * Story 1 is the author's public story; the artists are `confirmed` (the
 * reader every spec drives) and `tech_admin`. Story 3 is the author's private
 * story, story 5 stays empty. Titles are the contract with the specs.
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
        foreach (['confirmed', 'tech-admin', 'author', 'moderator'] as $name) {
            $this->users[$name] = (int) $ids[$name . '@e2e.test'];
        }

        $story = E2eStorySeeder::STORY_ID;
        $published = E2eStorySeeder::CHAPTER_ID;
        $draft = E2eStorySeeder::DRAFT_CHAPTER_ID;
        $simple = E2eStorySeeder::SIMPLE_CHAPTER_ID;

        // Published, by `confirmed`: whole work, chapter 1, draft chapter 2 (hidden from readers).
        $this->image($story, null, 'confirmed', 'Fanart oeuvre E2E', FanartStatus::Published);
        $this->image($story, $published, 'confirmed', 'Fanart chapitre un E2E', FanartStatus::Published);
        $this->image($story, $draft, 'confirmed', 'Fanart brouillon E2E', FanartStatus::Published);
        $this->image($story, $simple, 'tech-admin', 'Fanart chapitre trois E2E', FanartStatus::Published);

        // One sound on chapters 1 and 3 (a chapter has at most one published sound).
        $this->audio($story, $published, 'confirmed', 'Son chapitre un E2E', FanartStatus::Published);
        $this->audio($story, $simple, 'tech-admin', 'Son chapitre trois E2E', FanartStatus::Published);

        // Author artwork, published at once.
        $this->image($story, $simple, 'author', 'Artwork auteur E2E', FanartStatus::Published, artwork: true);

        // Pending: `confirmed`'s own (profile badge, withdraw), and three for the author to decide on.
        $this->image($story, null, 'confirmed', 'Fanart en attente confirme E2E', FanartStatus::Pending);
        $this->image($story, null, 'tech-admin', 'Fanart a accepter E2E', FanartStatus::Pending);
        $this->image($story, null, 'tech-admin', 'Fanart a decliner E2E', FanartStatus::Pending);
        $this->image($story, null, 'tech-admin', 'Fanart a signaler E2E', FanartStatus::Pending);
        $this->audio($story, $published, 'tech-admin', 'Son remplacant E2E', FanartStatus::Pending);

        // The author's private story keeps its fanarts visible to them.
        $this->image(E2eStorySeeder::PRIVATE_STORY_ID, null, 'confirmed', 'Fanart histoire privee E2E', FanartStatus::Published);

        // Two pending role requests on story 1: one for the author to accept, one to decline.
        foreach (['tech-admin', 'moderator'] as $requester) {
            FanartRoleRequest::create([
                'story_id' => $story,
                'user_id' => $this->users[$requester],
                'status' => FanartRoleRequest::STATUS_PENDING,
            ]);
        }
    }

    private function image(int $storyId, ?int $chapterId, string $artist, string $title, FanartStatus $status, bool $artwork = false): void
    {
        $path = $this->media->storePrivate('fanart/' . $storyId, $this->fixture(self::IMAGE, 'image/png'), [400, 800]);
        $this->row($storyId, $chapterId, $artist, $title, $status, FanartKind::Image, $path, $artwork);
    }

    private function audio(int $storyId, ?int $chapterId, string $artist, string $title, FanartStatus $status): void
    {
        $path = $this->media->storePrivateFile('fanart/' . $storyId, $this->fixture(self::SOUND, 'audio/mpeg'));
        $this->row($storyId, $chapterId, $artist, $title, $status, FanartKind::Audio, $path, false);
    }

    private function row(int $storyId, ?int $chapterId, string $artist, string $title, FanartStatus $status, FanartKind $kind, string $path, bool $artwork): void
    {
        Fanart::create([
            'story_id' => $storyId,
            'chapter_id' => $chapterId,
            'user_id' => $this->users[$artist],
            'kind' => $kind,
            'status' => $status,
            'is_artwork' => $artwork,
            'title' => $title,
            'description' => '<p>Description E2E</p>',
            'file_path' => $path,
            'share_on_discord' => false,
            'published_at' => $status === FanartStatus::Published ? now() : null,
        ]);
    }

    private function fixture(string $relativePath, string $mime): UploadedFile
    {
        $path = base_path($relativePath);

        return new UploadedFile($path, basename($path), $mime, null, true);
    }
}
