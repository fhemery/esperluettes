# Notification Domain

## Purpose
- **Centralize user-facing notifications** across the platform (cross-domain).
- **Provide a stable contract** (`NotificationContent`) for domain-specific payloads that are persisted, reconstructed, and rendered for display.
- **Offer a public API** to create targeted and broadcast notifications with validation and backfilling support.

## Key Concepts
- **`NotificationContent` (contract)**
  - `public static function type(): string` Unique, stable key (e.g. `story.chapter.comment`).
  - `public function toData(): array` JSON-serializable data only (scalars/arrays/IDs). No Eloquent models.
  - `public static function fromData(array $data): static` Rebuilds the content from stored data.
  - `public function display(): string` Returns localized HTML to render in the UI.
  - Implementations should be immutable (readonly properties) to ensure deterministic serialization.

- **`NotificationFactory` (singleton)**
  - Maintains a map of `type` => `class-string<NotificationContent>`.
  - `register(type, class)` registers a content type.
  - `resolve(type)` returns the class name or null.
  - `make(type, data)` instantiates a `NotificationContent` via `::fromData()`.

- **`NotificationPublicApi`**
  - `createNotification(int[] $userIds, NotificationContent $content, ?int $sourceUserId = null, ?\DateTime $createdAt = null)`
    - Validates target users exist; deduplicates IDs.
    - Optional `sourceUserId` must exist if provided.
    - Optional `createdAt` allows backfilling timestamps.
  - `createBroadcastNotification(NotificationContent $content, ?int $sourceUserId = null)`
    - Sends to all eligible users with roles `user` and `user-confirmed`.
  - `deleteNotificationsByType(string $contentKey): int` and `countNotificationsByType(string $contentKey): int` helpers.

## Registration of Notification Types
Register your `NotificationContent` class in your domain ServiceProvider `boot()` using the factory. This makes the type resolvable across the app (e.g. for rendering from stored rows).

Example (in-domain ReadList):
```php
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;

public function boot(): void
{
    $factory = app(NotificationFactory::class);
    $factory->register(
        type: ReadListAddedNotification::type(),
        class: ReadListAddedNotification::class
    );
}
```

Example (in Story domain):
```php
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;

public function boot(): void
{
    app(NotificationFactory::class)->register(
        ChapterCommentNotification::type(),
        ChapterCommentNotification::class
    );
}
```

## Implementing a New NotificationContent
1) **Create the class** in your domain (Public/Notifications) and implement the contract
```php
final class SomethingHappenedNotification implements NotificationContent
{
    public function __construct(
        public readonly int $entityId,
        public readonly string $actorName,
    ) {}

    public static function type(): string { return 'mydomain.something.happened'; }

    public function toData(): array { return ['entity_id' => $this->entityId, 'actor_name' => $this->actorName]; }

    public static function fromData(array $data): static
    {
        return new static(
            entityId: (int)($data['entity_id'] ?? 0),
            actorName: (string)($data['actor_name'] ?? ''),
        );
    }

    public function display(): string
    {
        return __(
            'mydomain::notification.something_happened',
            ['actor_name' => $this->actorName]
        );
    }
}
```

2) **Register the type** in your domain ServiceProvider via `NotificationFactory::register()` (see section above).

3) **Produce notifications**
- In-domain service: inject `NotificationPublicApi` and call `createNotification()`.
- Cross-domain reaction: handle another domain’s event in a Listener and call the API.

ReadList (in-domain) example producer:
```php
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;

$api->createNotification(
    userIds: [$authorId],
    content: new ReadListAddedNotification($readerName, $readerSlug, $storyTitle, $storySlug),
    sourceUserId: $readerId,
);
```

Story (cross-domain) example from `NotifyOnChapterComment`:
```php
$content = new ChapterCommentNotification(
    commentId: (int) $c->commentId,
    authorName: $authorName,
    authorSlug: $authorSlug,
    chapterTitle: (string) ($chapter->title ?? ''),
    storySlug: (string) $story->slug,
    chapterSlug: (string) $chapter->slug,
    isReply: $c->isReply,
);
$notifications->createNotification($recipients, $content, (int) $c->authorId, $eventDate);
```

## Example Implementations
- **In-domain (ReadList):** `ReadListAddedNotification`
  - Type: `readlist.story.added`
  - `display()` uses `readlist::notification.story_added` with links to profile and story.
- **Cross-domain (Story):** `ChapterCommentNotification`
  - Type: `story.chapter.comment`
  - `display()` picks a translation key based on `isReply` and links to chapter/comments and author profile.

## i18n and Rendering
- `display()` should only compose data and translation keys; keep logic minimal.
- Use namespaced translations from your domain (e.g. `readlist::...`, `story::...`).
- Build URLs using named routes; keep payload route-safe slugs/IDs in `toData()`.

## Validations & Constraints
- `NotificationPublicApi` validates recipients and (optionally) `sourceUserId`.
- `userIds` must be non-empty and existing users.
- Keep `toData()` JSON-safe; avoid models or unserializable objects.
- Favor immutable value objects (readonly props) for reproducibility.
- Ensure your type string is stable; changing it breaks reconstruction and analytics.

## Cleanup & Operations
- Cleanup command: `CleanupOldNotificationsCommand` (registered by the domain provider).
- Helpers exist to count/delete by type via the public API.

## How to Backfill
- Listeners/commands can pass a custom `\DateTime $createdAt` into `createNotification()` for historical imports.

## Where Things Live
- Contract: `app/Domains/Notification/Public/Contracts/NotificationContent.php`
- Factory: `app/Domains/Notification/Public/Services/NotificationFactory.php`
- Public API: `app/Domains/Notification/Public/Api/NotificationPublicApi.php`
- Register your types: your domain `Public/Providers/*ServiceProvider.php`
- Examples: `ReadListAddedNotification`, `ChapterCommentNotification`, listener `NotifyOnChapterComment`.
