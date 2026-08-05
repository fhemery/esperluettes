# News comments — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

News owns the integration; Comment stays the generic engine it already is for
Story/chapters. No new domain, no change inside Comment. This is the same
shape Story already uses for chapter comments — a `CommentPolicy`
implementation plus a `CommentPosted` listener, both living in News.

### 1.1 Changes in other domains

- **Comment**: none. The polymorphic `commentable_type`/`commentable_id`
  storage, the `CommentPolicyRegistry` extension point, and the `'comment'`
  Moderation topic are entity-type-agnostic already — registering `'news'` as
  a new entity type requires no change on Comment's side (decision #1).
- **Moderation**: none. The existing `'comment'` topic and reason list apply
  to any `commentable_type`; no News-specific registration needed.
- **Notification**: none structurally. News registers one new notification
  type and one new group through the existing `NotificationFactory` API —
  same mechanism Story, Follow, ReadList, Quote already use.

## 2. Data model

### 2.1 Tables

None. Comments live in Comment's existing `comments` table, keyed by
`commentable_type = 'news'` / `commentable_id = <news.id>`.

### 2.2 Model

None new. `News` (`app/Domains/News/Private/Models/News.php`) is read, not
extended.

### 2.3 Lifecycle rules

- **Article deleted**: `NewsService::delete()` calls
  `CommentMaintenancePublicApi::deleteFor('news', $news->id)` before
  `$news->delete()` — same direct-call sequencing `ChapterService::delete()`
  uses for chapters (not an event listener; the deletion must be synchronous
  with the parent row going away).
- **Article unpublished**: no code path — the show route already 404s for
  non-admins on a draft, so the thread is unreachable without being touched.
- **Commenting user deactivated/deleted**: no News-specific code. Comment's
  existing `Auth::UserDeactivated` / `Auth::UserDeleted` listeners
  (soft-delete / nullify `author_id`) already apply to every
  `commentable_type`.

## 3. PHP architecture

### 3.1 Public API

No new News public methods. News becomes a consumer of:
- `Comment\Public\Api\CommentPolicyRegistry::register('news', NewsCommentPolicy)`
- `Comment\Public\Api\CommentMaintenancePublicApi::deleteFor()`
- `Comment\Public\Api\CommentPublicApi::getCommentInternal()` (thread fan-out
  lookup, same call `NotifyOnChapterComment` makes)
- `Comment\Public\Events\CommentPosted` (subscribed)

### 3.2 Services

No new service class beyond the policy and listener below —
`NewsCommentPolicy` and `NotifyOnNewsComment` hold all the logic; nothing
belongs in `NewsService` except the one `deleteFor()` call in §2.3.

### 3.3 Policy / authorization

`App\Domains\News\Private\Services\NewsCommentPolicy implements CommentPolicy`:

| Method | Behavior |
|---|---|
| `canCreateRoot(entityId, userId)` | `true` iff the article exists and `status === 'published'` (decision #9). No author exclusion, no per-user cap (decisions #7, functional §3). |
| `canReply(parentComment, userId)` | `true` — replies inherit the same published-only gate implicitly (a draft has no visible root comments to reply to). |
| `canEditOwn` | `true` — no time window (functional §4.3). |
| `validateCreate` / `validateEdit` | no-op. |
| `getRootCommentMinLength()` | `20` (functional §4.1). |
| `getRootCommentMaxLength()` / `getReplyCommentMinLength()` / `getReplyCommentMaxLength()` | `null` (functional §4.1–4.2). |
| `getUrl(entityId, commentId)` | `route('news.show', ['slug' => $news->slug]) . '?comment=' . $commentId` — same deep-link shape `ChapterCommentPolicy::getUrl()` uses, for the Moderation snapshot link. |

`compliant` gating is not re-implemented here — it is already enforced by the
`auth` + `compliant` middleware on Comment's own `POST /comments` route,
identically for every `commentable_type`.

Registered in `NewsServiceProvider::boot()`:

```php
app(CommentPolicyRegistry::class)->register('news', app(NewsCommentPolicy::class));
```

### 3.4 Events and listeners

No new News events. News subscribes to Comment's existing event:

`App\Domains\News\Private\Listeners\NotifyOnNewsComment` handles
`CommentPosted`:
- Ignores the event when `$event->comment->entityType !== 'news'`.
- Root comments (`!isReply`): returns immediately — no notification is sent
  (functional §4.1.3, unlike Story's root-comment fan-out to story authors).
- Replies: resolves the parent comment's thread via
  `CommentPublicApi::getCommentInternal($parentId, true, 0)`, builds the
  recipient set as `{root author} ∪ {prior repliers} \ {current replier}`
  (identical logic to `NotifyOnChapterComment`), and sends
  `NewsReplyCommentNotification` through `NotificationPublicApi`.

Subscribed in `NewsServiceProvider::boot()`:

```php
$eventBus->subscribe(CommentPosted::class, [app(NotifyOnNewsComment::class), 'handle']);
```

`App\Domains\News\Public\Notifications\NewsReplyCommentNotification implements
NotificationContent` carries `commentId`, `authorName`, `authorSlug`,
`newsTitle`, `newsSlug` — the News-side equivalent of
`ChapterReplyCommentNotification`, with no story/chapter fields.

Registered with a **new** notification group, distinct from both News's
existing `'news'` group (article-published broadcasts) and Story's `'comments'`
group (decision #6):

```php
$notificationFactory->registerGroup('news-comments', 45, 'news::notification.settings.group_comments');
$notificationFactory->register(
    type: NewsReplyCommentNotification::type(),
    class: NewsReplyCommentNotification::class,
    groupId: 'news-comments',
    nameKey: 'news::notification.settings.type_reply_comment',
);
```

(`registerGroup` calls live centrally in `NotificationServiceProvider` for
four of five existing groups, but Story's `'publication'` group is already
registered locally in `StoryServiceProvider` — both patterns coexist today.
`news-comments` follows the local pattern: it is News-specific and has no
reason to be discoverable from the Notification domain.)

### 3.5 Routes, controllers, form requests

None new. Posting/editing/reporting/replying all go through Comment's
existing routes (`POST /comments`, `PUT /comments/{id}`, moderation routes,
`GET /comments/fragments`) — untouched, entity-type-agnostic already. No
`PATCH` is introduced by this feature (Comment's existing `update` route
already uses `PATCH`, which is pre-existing and out of scope here).

## 4. Frontend architecture

`app/Domains/News/Private/Resources/views/pages/show.blade.php` gains one
block, gated on published status (decision #9):

```blade
@if($news->status === 'published')
    <x-comment::comment-list-component entity-type="news" entity-id="{{ $news->id }}" page="0" perPage="5" />
@endif
```

Lazy mode (`page="0"`), matching chapters (decision #8) — comments load via
the existing Intersection-Observer fragment endpoint, not on initial render.
No new Blade components, no new JS/Alpine — the entire UI (editor toolbar,
report button, moderation actions, deep-linking) is Comment's existing
`comment-list-component` / `comment-item` partials, reused as-is.

## 5. Deptrac

Two new edges, both mirroring the exact edge Story already has for the same
reason (`StoryPublic` / `StoryPrivate` → `CommentPublic`):

| Edge | Reason |
|---|---|
| `NewsPublic` → `CommentPublic` | `NewsServiceProvider` (Public layer) calls `CommentPolicyRegistry::register()`. |
| `NewsPrivate` → `CommentPublic` | `NewsCommentPolicy` implements `CommentPolicy`; `NotifyOnNewsComment` consumes `CommentPosted` and calls `CommentPublicApi`; `NewsService::delete()` calls `CommentMaintenancePublicApi`. |

No other new edges — Notification, Moderation, Events edges News already
has cover everything else.

## 6. Testing strategy

Integration tests (default), one feature test file per behavior, mirroring
Story's existing Comment-integration test shapes:

- `NewsCommentPolicy` — root/reply length limits, author-can-post, no cap,
  published-only gate (draft rejects `canCreateRoot`).
- `NotifyOnNewsComment` — reply fan-out recipients and exclusions; root
  comment sends nothing.
- Cascade — deleting a `News` row removes its comments
  (`CommentMaintenancePublicApi::deleteFor` called).
- `GET /news/{slug}` renders the comment component only when published, not
  on draft preview.
- Notification group/type registration — `NewsReplyCommentNotification`
  appears under the `news-comments` group and is user-toggleable in Settings.

Nothing here needs a unit test in isolation — all the new logic (`Policy`,
`Listener`) is only meaningful wired through the real Comment/Notification
public APIs, so integration tests are the correct and only level. Visual QA
(VERIFY) covers the actual UI: posting, replying, editing, reporting, and the
lazy-load scroll behavior in a real browser.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Comment thread load mode on the article page | Lazy (`page="0"`, IntersectionObserver fragment load) vs Eager (`page="1"`, rendered in initial HTML) | Lazy | Matches the only pattern exercised elsewhere in the app (chapters); no extra query on every article view for readers who never scroll to the thread. |
| 2 | Draft-preview (admin-only unpublished article) comment thread | Hide the section entirely vs show and allow posting | Hidden, via `status === 'published'` gate in both the Blade view and `NewsCommentPolicy::canCreateRoot()` | Avoids comment threads seeding on content that may still change before publish; matches the spec's framing that comments only exist in the article's public, reachable state. |

## 8. File layout

```
app/Domains/News/
├── Private/
│   ├── Listeners/
│   │   └── NotifyOnNewsComment.php          (new)
│   └── Services/
│       └── NewsCommentPolicy.php            (new)
└── Public/
    └── Notifications/
        └── NewsReplyCommentNotification.php (new)
```

Edited, not created: `NewsServiceProvider.php` (policy registration, listener
subscription, group + type registration), `NewsService.php` (cascade delete
call), `show.blade.php` (comment component), `deptrac.yaml` (two edges),
News's `lang/fr/notification.php` (new group/type keys).

## 9. Risks acknowledged

- **`NewsCommentPolicy::canCreateRoot` needs the article's `status`**, so it
  looks up `News` directly (same-domain Private model access — legal, not a
  cross-domain call). If `canCreateRoot` is ever called for a
  `commentable_id` that doesn't exist, it must return `false`, not throw —
  same defensive shape `ChapterCommentPolicy` doesn't currently need to worry
  about but this one does, since drafts are a valid non-postable state.
- **`news-comments` as a fourth distinct comment-notification concept**
  (alongside Story's `'comments'` group and News's own `'news'` group) adds
  one more row to the Settings notification page. Acceptable per decision #6;
  revisit only if News grows more comment-adjacent notification types and the
  group split starts looking arbitrary to users.
