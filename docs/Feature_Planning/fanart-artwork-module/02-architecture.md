# New fanart / artwork module — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md) (rows 28–31 are this step's)

## 1. Domain placement

A new **`Fanart`** domain (`app/Domains/Fanart`) owns everything: fanarts and
artworks, the Fanartiste role and its requests, the mode d'accueil settings,
the moderation topic, the notifications, the profile tab and the Discord feed.

Why a new domain rather than Story: the user asked for isolation (spec §9
notes), and the feature has its own tables, lifecycle, moderation topic and
UI surface. Story only needs a handful of read/credit methods and some
component tags in its views — the same relationship Quote has with Story,
which is the template followed throughout.

The Fanartiste role lives **in Fanart** (§7 #1), not in `story_collaborators`:
a fanartiste is not a collaborator for any other purpose — in particular they
do **not** gain read access to private stories.

### 1.1 Changes in other domains

**Story** — direct calls only, no new extension point.
- `StoryPublicApi` gains (exact names are BUILD's):
  - `canRead(?int $userId, int $storyId): bool` — the `StoryPolicy::view`
    rule (staff bypass, public / community-confirmed / private-collaborator),
    including "story soft-deleted ⇒ false". Fanart uses it for every
    visibility check, so author deactivation (Story soft-deletes the story)
    hides the story's fanarts with no listener in Fanart.
  - `getReadChapterIds(int $userId, int $storyId): int[]` and
    `getReadChapterIdsByStoryIds(int $userId, int[] $storyIds): array<int,int[]>`
    — exposes `ReadingProgressService`, one query each, for spoiler collapsing
    (tab: one story; profile tab: many).
  - `getAvailableChapterCredits(int $userId): int` and
    `spendChapterCredit(int $userId): bool` — wraps `ChapterCreditService`;
    `spend` is atomic (conditional update) and returns `false` when no credit
    is left, so the check and the spend cannot race.
  - Chapter data Fanart needs (ids, titles, slugs, sort order, published
    status) is already reachable through `listStories(...)` /
    `StoryChapterDto`; if the shape does not fit, BUILD adds a narrow
    `getChaptersForStory(int $storyId): StoryChapterDto[]` rather than
    widening a DTO.
- Views: Story's templates include `<x-fanart::…>` component tags (Blade tags
  are not PHP dependencies — no deptrac edge, Quote precedent):
  - story page: Chapitres / Fanarts tab shell, the « Proposer un fanart » /
    « Ajouter un artwork » / « Demander le rôle de fanartiste » button area,
    the speaker icon per chapter row and the story-page player;
  - chapter page: audio widget under the title, image block at the bottom;
  - collaborators page: a Fanartistes section (grant / revoke, pending
    requests).
  Each component decides itself whether to render (confirmed + staff only),
  so Story's views do not learn Fanart's rules. The tab shell is the one
  piece Story owns structurally: when the component reports "no Fanarts tab
  for this viewer", the page renders exactly as today (no tab bar).
- Settings: no change to Story's guarded `registerStoryPreferenceSettings()`.
  Fanart adds its own **section** to the `stories` tab from its own
  `booted` callback, guarded on its own key (§3.4).

**Media** — direct calls plus three capabilities (§7 #2, #3):
- a private scope root `fanart` (`fanart/{storyId}`) and a private scope root
  `moderation` (`moderation/{topicKey}`), added to the existing hard-coded
  scope lists (no scope registry — two consumers do not justify one);
- **private image variants**: `storePrivate($scope, $file, [400, 800])` already
  writes them; Media gains `streamVariant(string $path, int $width, string
  $format = 'webp'): Response` (no authorization — the caller checks, as for
  `stream()`), makes `hasVariants()` private-aware, and teaches the GC's
  private sweep to treat `{name}-{w}w.{jpg,webp}` as variants of the claimed
  original instead of unclaimed originals;
- `copyPrivate(string $path, string $targetScope): string` — copies a
  private (or public) original into a private scope and returns the new path.
  Variants are not copied: moderators view the original.

**Moderation** — a generic "file snapshot" capability (§7 #3):
- `SnapshotFormatterInterface::capture()` may return a reserved
  `_media` key (`string[]` of media paths). `ModerationService::createReport`
  copies each into `moderation/{topicKey}` via `MediaPublicApi::copyPrivate`
  and stores the **copied** paths in the snapshot before persisting.
- Moderation registers a `MediaUsageProvider` claiming every `_media` path of
  every existing report (retention = as long as the report exists — open
  question 1 closed with its default).
- A moderator-only route streams a snapshot's media file;
  `render()` of a formatter receives the snapshot with an extra
  `_media_urls` (same order as `_media`) so it can print `<img>` / `<audio>`.
- No change to `ReportApproved` (§7 #4).

**Profile, Settings, Notification, Editor** — consumed through their existing
public APIs only (profile tab registry, settings registry, notification
factory, rich-text component + `maxstripped` + sanitizer). No change.

**Discord** — no code change. Fanart's bot endpoints reuse the `discord.api`
middleware alias (§3.5); `docs/Discord_Api_Usage.md` gains a section.

## 2. Data model

### 2.1 Tables

**`fanarts`**

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `story_id` | bigint | no FK (Story table) — index |
| `chapter_id` | bigint, nullable | null = « Toute l'œuvre » (images only) — index |
| `user_id` | bigint | submitter (fanartist or author) — index |
| `kind` | string(16) | `image` \| `audio` |
| `status` | string(16) | `pending` \| `published` |
| `is_artwork` | bool | submitted by an author of the story at the time |
| `title` | string(100) | plain text (A5) |
| `description` | text, nullable | sanitized HTML, ≤ 1000 visible chars |
| `file_path` | string | Media private path |
| `share_on_discord` | bool, default false | ticked at submission; images only |
| `discord_sent_at` | timestamp, nullable | set by the bot's mark-sent |
| `published_at` | timestamp, nullable | accept time / artwork upload time |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | SoftDeletes — **only** for user deactivation |

Indexes: `(story_id, status, kind)` (tab, queue), `(chapter_id, kind, status)`
(chapter page, audio uniqueness), `(user_id, status)` (profile tab, cascades),
`(share_on_discord, status, discord_sent_at)` (bot feed).

"One published audio per chapter" is enforced in the service (row lock on
the chapter's published audio inside the accept/upload transaction), not by a
DB constraint — MySQL has no partial unique index and pending audios on the
same chapter are legitimate.

**`fanart_fanartists`** — `story_id`, `user_id`, `granted_by_user_id`,
`created_at`; unique `(story_id, user_id)`; index `user_id`.

**`fanart_role_requests`** — `id`, `story_id`, `user_id`, `status`
(`pending` \| `declined`), `decided_by_user_id` nullable, `decided_at`
nullable, timestamps; index `(story_id, status)`, `(user_id, story_id)`.
Accepted requests are deleted (the grant row is the record). The 30-day
cooldown reads the latest `declined` row for `(user_id, story_id)`.
One pending row per pair is enforced in the service.

No foreign keys: every reference points at Story or Auth tables.

### 2.2 Models

`Fanart` (`SoftDeletes`), `Fanartist`, `FanartRoleRequest` — PHP attribute
syntax (`#[Table]`, `#[Fillable]`), `protected $casts` for the bools,
timestamps and the two string-backed enums `FanartKind`, `FanartStatus`.
No cross-domain relations; `Fanartist` has a composite key
(`protected $primaryKey = null`, `#[WithoutIncrementing]`).

### 2.3 Lifecycle rules

| Trigger (spec §5) | Handling |
|---|---|
| Mode → `non`, role revoked, story → private | Nothing stored changes; only the submit authorization reads them. |
| Chapter unpublished / republished | Nothing stored; reader queries keep only fanarts whose chapter is published (null chapter always kept). Authors see all. |
| Chapter deleted (`Chapter.Deleted`) | Hard-delete its fanarts (pending + published), notify each distinct fanartist (not the actor/authors). Files left to GC. |
| Story deleted (`Story.Deleted`, also fired when a sole author's account is deleted) | Hard-delete all its fanarts, fanartist rows and role requests; notify each distinct non-author fanartist. |
| User deleted (`Auth.UserDeleted`) | Hard-delete (bypassing SoftDeletes) all fanarts with that `user_id` — covers both their fanarts and, on a surviving co-authored story, their own artworks; delete their fanartist rows and role requests. No notification. |
| User deactivated / reactivated | Soft-delete / restore their fanarts (Quote pattern). Author deactivation needs nothing: the story becomes unreadable through `canRead`. |
| Audio replaced (accept or artwork upload) | Previous published audio hard-deleted in the same transaction; its submitter notified unless they are the actor. |
| Author declines / deletes, fanartist withdraws, moderator deletes | Hard delete; notifications per §3.4. |
| Author reports a pending fanart (A1) | On `Moderation.ReportSubmitted` with topic `fanart`, if the fanart is pending **and** the reporter is an author of its story: hard-delete it and send the decline notification without comment. The snapshot copy was already taken synchronously before the event. |

Files are never deleted by Fanart: it claims `file_path` of every row
(including soft-deleted ones, so reactivation finds the file) through a
`MediaUsageProvider`; `media:gc` collects the rest.

## 3. PHP architecture

### 3.1 Public API

**No `FanartPublicApi` in this epic** — no domain calls Fanart: Story reaches
it only through Blade components, Discord through Fanart's own routes. Add
one when a caller appears.

Contracts registered by Fanart into other domains:
- `FanartSnapshotFormatter implements SnapshotFormatterInterface` (topic
  `fanart`): captures title, description, kind, artist id/name, story and
  chapter titles, status, and `_media => [file_path]`; `getContentUrl()`
  opens the story's Fanarts tab on that fanart's viewer
  (`/stories/{slug}?tab=fanarts&fanart={id}`).
- `FanartMediaUsageProvider implements MediaUsageProvider`.
- `FanartTabVisibility implements ProfileTabVisibility`.
- Notification content classes (`Public/Notifications`), group `fanart`:

| Type | Recipients | Payload essentials |
|------|------------|--------------------|
| `fanart.submitted` | all authors | fanartist, story, chapter?, kind |
| `fanart.accepted` | fanartist | story, title, author comment |
| `fanart.declined` | fanartist | story, title, author comment (nullable — A1) |
| `fanart.deleted` | fanartist | story, title (author deletion) |
| `fanart.deleted_by_moderation` | authors + fanartist | story, title |
| `fanart.audio_replaced` | previous audio's submitter | story, chapter |
| `fanart.removed_with_content` | fanartists of a deleted story/chapter | story (+ chapter) title |
| `fanart.role_requested` | all authors | requester, story |
| `fanart.role_given` | grantee | story |
| `fanart.role_declined` | requester | story |

The actor is always excluded from recipients. Payloads denormalise names,
slugs and titles (Quote pattern) so a deleted target still renders.

Events emitted: **`Fanart.DeletedByModeration`** (`fanartId`, `storyId`,
`userId`, `moderatorId`) for the audit log, as Comment does. No other event —
nothing consumes them (rule 2); notifications are sent from the services.

### 3.2 Services

- `FanartService` — submit, accept, decline, delete (owner / author /
  moderator variants), artwork upload and edit, audio replacement, listing
  queries for the tab, chapter page, profile tab and Discord feed. The only
  writer of `fanarts`.
- `ModeService` — effective mode per media type for a story: reads each
  author's two settings via `SettingsPublicApi::getValue` and takes the most
  restrictive (`non` > `designated` > `yes`); `canSubmit(userId, storyId, kind)`
  combines it with confirmed role, story not private, `canRead`, and
  fanartiste membership.
- `FanartistService` — grant, revoke, requests (create, withdraw, accept,
  decline, cooldown).
- `FanartNotifier` — recipient resolution and `createNotification` calls,
  catching the empty-recipient `ValidationException` as Quote does.

Artwork credit: the controller-level flow is "validate → `spendChapterCredit`
→ store file → create row" in one transaction; `false` from spend aborts with
the 4.5.3 message. Edit costs nothing; delete refunds nothing.

Uploads: images via `MediaPublicApi::storePrivate('fanart/{storyId}', $file,
[400, 800])`; audio via `storePrivateFile('fanart/{storyId}', $file)`.
Descriptions: `maxstripped:1000` on input, sanitized with an existing inline
purifier profile matching the `inline` editor toolbar (BUILD picks the
existing capability-named profile; adds one only if none fits).

### 3.3 Policy / authorization

`FanartPolicy` is the single enforcement point, called from form requests
(`authorize()`) and controllers; listing queries apply the same rules as
query scopes so nothing invisible is ever loaded.

| Ability | Rule |
|---|---|
| see anything | `user-confirmed` or moderator/admin/tech-admin; and `canRead(story)` |
| view a published fanart | above + chapter published (or null) unless viewer is author/staff |
| view a pending fanart | author of the story, the submitter, or staff |
| submit (kind) | `ModeService::canSubmit` |
| upload / edit artwork | author of the story; story not private |
| accept / decline | author of the story; fanart pending |
| delete | submitter (any status); author of the story (published, or via decline); staff (moderation route) |
| manage fanartistes / requests | author of the story |
| request role | confirmed, not author, not fanartiste, effective mode `designated` for ≥ 1 kind, no pending request, cooldown elapsed |
| stream file / variant | same as "view" for that fanart |
| profile tab | owner, staff, or (viewer confirmed and owner's `hide-fanart-tab` is false); content further filtered per story with `canRead` |

Guests and non-confirmed users get 403/404 on every route and no component
renders.

### 3.4 Events and listeners

Listened (all via `EventBus::subscribe` in the provider):
`Auth.UserDeleted`, `Auth.UserDeactivated`, `Auth.UserReactivated`,
`Story.Deleted`, `Chapter.Deleted`, `Moderation.ReportSubmitted` — handling in
§2.3. `Chapter.Published/Unpublished` and `Story.VisibilityChanged` need no
listener (read-time filtering).

Registrations in `FanartServiceProvider` (`Public/Providers`):
- settings, inside `booted`, after Story's provider: section `fanart` in tab
  `stories` with `fanart-images-mode` (default `yes`) and `fanart-audio-mode`
  (default `designated`), enum parameter `yes|designated|no`; parameter
  `hide-fanart-tab` (bool, default `true` = private) in tab `profile`,
  section `privacy`. Each guarded on its own key.
- profile tab `fanarts` (order after Quote), `FanartTabVisibility`,
  `ProfileTabPrivacy('profile', 'hide-fanart-tab')`.
- moderation topic `fanart` + seeded reasons.
- notification group/types (§3.1), media usage provider.

### 3.5 Routes, controllers, form requests

Web routes, middleware `web, auth, verified`, role `user-confirmed` or staff
(no PATCH — PUT for updates):

| Purpose | Route shape |
|---|---|
| create page / submit | `GET /stories/{slug}/fanarts/create`, `POST /stories/{slug}/fanarts` |
| artwork edit | `GET /fanarts/{fanart}/edit`, `PUT /fanarts/{fanart}` |
| delete (owner/author) | `DELETE /fanarts/{fanart}` |
| review | `POST /fanarts/{fanart}/accept`, `POST /fanarts/{fanart}/decline` |
| files | `GET /fanarts/{fanart}/file` (original / audio, Range), `GET /fanarts/{fanart}/thumb/{width}` |
| fragments (lazy) | `GET /stories/{slug}/fanarts` (tab), `GET /stories/{slug}/fanarts/groups/{chapterId\|work}`, `GET /fanarts/{fanart}` (viewer / review viewer), profile-tab group fragments |
| fanartistes | `POST /stories/{slug}/fanartistes`, `DELETE /stories/{slug}/fanartistes/{userId}` |
| role requests | `POST /stories/{slug}/fanartiste-requests`, `DELETE …/{request}` (withdraw), `POST …/{request}/accept`, `POST …/{request}/decline` |
| moderation | `DELETE /moderation/fanarts/{fanart}` (moderator/admin) |

File responses carry `Cache-Control: private` (SecretGift precedent).

Bot API (`api/discord/fanarts`, middleware `api, discord.api`, throttled as
the notifications feed): `GET /pending?perPage=&page=` → published images with
`share_on_discord` on **public** stories, `discord_sent_at` null, oldest
first, each `{id, title, artistName, storyTitle, storyUrl, chapterTitle?,
imageUrl}`; `POST /mark-sent {ids[]}`. `imageUrl` is a **signed, expiring**
URL to the 800w variant (the bot has no user session). Deleted fanarts simply
drop out of `pending`.

Form requests: `StoreFanartRequest` (kind-dependent rules: image
`mimes:jpg,jpeg,png,webp|max:2048`, audio `mimes:mp3|max:15360`, title
`required|max:100`, description `nullable|maxstripped:1000`, chapter
nullable for image / required for audio and belonging to the story,
attestation `accepted`, `share_on_discord` boolean image-only),
`UpdateArtworkRequest`, `ReviewFanartRequest` (comment `required|max:500`).

## 4. Frontend architecture

Blade-first, Alpine for interaction, HTML fragments for lazy loading (no JSON
rendering on the client).

- **Story page tabs**: `<x-fanart::story-tabs>` renders the tab bar and wraps
  Story's existing chapter list as the Chapitres panel; the Fanarts panel
  fetches the tab fragment on first open. `?tab=fanarts&fanart={id}` opens it
  directly (report content URL).
- **Groups**: one collapsible per chapter in story order + « Toute l'œuvre »;
  the tab fragment carries headers and read/spoiler state only, each group's
  thumbnails load when first expanded. Unread groups start collapsed with the
  spoiler notice; authors start expanded. « Montrer tout » / « Cacher tout »
  per group.
- **Thumbnails** use the gated 400w/800w variant routes with `srcset`,
  `loading="lazy"`, `alt` = title. `<x-media::image>` is not reusable (public
  URLs only); Fanart has its own small thumbnail component.
- **Viewer**: one modal Alpine component; content is the viewer fragment
  (image fit to width/height, title, artist link, sanitized description,
  `<x-moderation::report-button topicKey="fanart">`, delete button, and for
  staff `<x-moderation::moderation-button>` with the « Supprimer » action).
  The review viewer is the same fragment with the comment field and Accepter
  / Décliner. Stacks image above text on mobile.
- **Story-page player**: an Alpine store `fanartPlayer` fed with the ordered
  list of chapters that have a published audio (`{chapterId, src,
  markReadUrl}`), rendered with the chapter list. Speaker icon → `play(id)`;
  `ended` → `POST` the existing `chapters.read.mark` route, then play the next
  entry; stop after the last. One native `<audio controls>` element.
- **Chapter page**: `<x-fanart::chapter-audio>` (native player + report
  flag) under the title, `<x-fanart::chapter-images>` (same group fragment,
  view-only) at the bottom.
- **Create / edit page**: full page; Image / Audio switch shows only the
  allowed kinds; `<x-media::image-field>` (`allowLibrary=false`,
  `needsPropertyConfirm=false` — the attestation is our own server-validated
  checkbox) and `<x-media::sound-field maxSize="15360" accept="audio/mpeg">`;
  `<x-editor::rich-text toolbar="inline" max="1000">`; chapter select; credit
  warning for authors.
- **Collaborators page / profile tab**: server-rendered components; profile
  tab sections Images / Sons grouped by story then chapter, same lazy groups
  and spoiler rules, pending badge for the owner.
- All strings in `app/Domains/Fanart/Private/Resources/lang/fr/*.php`.

## 5. Deptrac

New layers `FanartPublic`, `FanartPrivate`, `FanartTests`:

- `FanartPublic → Shared, FanartPrivate, AuthPublic, EventsPublic,
  NotificationPublic, SettingsPublic, StoryPublic, ProfilePublic,
  ModerationPublic, MediaPublic` — provider registrations into those
  registries, snapshot formatter, notification contents, tab visibility.
- `FanartPrivate → Shared, FanartPublic, AuthPublic, EventsPublic,
  NotificationPublic, SettingsPublic, StoryPublic, MediaPublic,
  ModerationPublic, EditorPublic` — services, listeners, controllers.
  Profile names via `Shared\Contracts\ProfilePublicApi` (Quote precedent).
- `FanartTests → Shared, TestHelpers, FanartPrivate, FanartPublic, AuthPublic,
  StoryPublic, NotificationPublic, ModerationPublic, MediaPublic,
  SettingsPublic`.
- **`ModerationPrivate → MediaPublic`** (new) — copying snapshot files,
  claiming them for GC, streaming them to moderators.

No edge into Fanart from any domain (Story uses Blade tags only); no edge to
Discord (string middleware alias).

## 6. Testing strategy

- **Integration (feature) tests — the default**, in `Fanart/Tests/Feature`:
  submit/accept/decline/delete per role (guest, `user`, confirmed,
  fanartiste, author, co-author, moderator), mode matrix incl.
  most-restrictive co-author, private-story refusal, artwork credit
  spend/refusal, audio replacement + notification, every lifecycle row of
  §2.3, role requests + cooldown, profile tab visibility/privacy, file and
  variant routes' authorization and Range (206), fragments' spoiler state,
  Discord feed filtering + mark-sent + signed URL, report snapshot contents.
- **Media** feature tests: private variants written, `streamVariant`,
  `copyPrivate`, GC keeps a claimed original's private variants and collects
  unclaimed ones.
- **Moderation** feature tests: `_media` copy at report creation, usage
  provider claims, moderator-only media stream, `_media_urls` passed to
  `render()`.
- **Story** feature tests for the new public API methods (incl. atomic
  credit spend).
- **Unit**: `ModeService` most-restrictive resolution only.
- **Vitest**: the `fanartPlayer` store (queue, skip chapters without audio,
  stop at end, mark-read call).
- **VERIFY (visual)**: tabs and lazy groups, viewer on mobile, player
  auto-advance, chapter-page widgets, collaborators section, create page
  switch, moderation menu, nothing shown to guests / non-confirmed.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where the Fanartiste role lives | A: Fanart table + section on Story's collaborators page · B: multi-row `story_collaborators` | **A** | Isolation; B reworks ~13 Story files and would give fanartistes private-story read access (collaborators can read private stories). |
| 2 | Thumbnails for confirmed-only images | A: private variants in Media · B: originals only · C: public disk, hashed URLs | **A** | Only option keeping both privacy and light pages (B: up to 2 MB per thumbnail; C: leaked URL = public). Reusable Media capability. |
| 3 | Keeping the reported file | A: generic `_media` snapshot capability in Moderation (copy via Media) · B: Fanart retains the original while reported | **A** | The copy belongs to the report; B needs Fanart to track report state it cannot see. Reusable for avatars/covers later. |
| 4 | Does approving a report delete the fanart | A: enrich `ReportApproved`, Fanart deletes · B: existing pattern — moderator « Supprimer » in a moderation menu on the fanart | **B** (user) | Same behaviour as every other topic; report links to the fanart; copy (#3) covers already-deleted content. Spec §4.11.5 amended. |
| 5 | Injecting into Story's pages | A: `<x-fanart::…>` tags in Story views · B: story-page tab / slot registry in Story | **A** (decided) | One consumer; Quote precedent; no deptrac edge. B if a second domain needs a story-page tab. |
| 6 | Discord relay transport | A: Fanart's own bot endpoints behind `discord.api` · B: generic feed registry in Discord · C: notification channel | **A** (decided) | Minimum code, mirrors the existing pending/mark-sent pattern; C is user-targeted and doesn't fit. B when a second feed appears. |
| 7 | Hiding on chapter unpublish / author deactivation / visibility change | A: read-time filtering via Story API · B: listeners flipping a flag | **A** (decided) | No duplicated state; Story already soft-deletes stories on author deactivation. |
| 8 | Media scopes | A: add `fanart` and `moderation` to the hard-coded lists · B: scope registry | **A** (decided) | Two consumers; no speculative extension point. |
| 9 | Moderation-copy retention (spec open Q1) | A: as long as the report exists · B: purge after N days | **A** (default) | Matches spec default; reports are rarely deleted, volume is small. |
| 10 | Own domain events | A: only `Fanart.DeletedByModeration` · B: full set (submitted/accepted/…) | **A** (decided) | Nothing consumes the others; Comment precedent for the moderation one. |

## 8. File layout

```
app/Domains/Fanart/
  Database/Migrations/        create_fanarts, create_fanart_fanartists, create_fanart_role_requests
  Database/Seeders/           FanartModerationReasonSeeder
  Private/
    Controllers/              FanartController, FanartReviewController, FanartFileController,
                              FanartFragmentController, FanartistController, RoleRequestController,
                              FanartModerationController, DiscordFeedController
    Listeners/                (one per §3.4 event)
    Models/                   Fanart, Fanartist, FanartRoleRequest
    Policies/                 FanartPolicy
    Requests/                 StoreFanartRequest, UpdateArtworkRequest, ReviewFanartRequest
    Services/                 FanartService, ModeService, FanartistService, FanartNotifier
    Support/                  FanartKind, FanartStatus, FanartMediaUsageProvider
    View/Components/          story-tabs, chapter-audio, chapter-images, collaborators-section, …
    Resources/{js,lang/fr,views/{components,pages,fragments}}
    web.routes.php, api.routes.php
  Public/
    Events/                   FanartDeletedByModeration
    Moderation/               FanartSnapshotFormatter
    Notifications/            (one class per type in §3.1)
    Providers/                FanartServiceProvider
    Visibility/               FanartTabVisibility
  Tests/{Feature,Unit}
  README.md, AGENTS.md, CLAUDE.md
```

## 9. Risks acknowledged

- **Upload size in production**: local `php.ini` allows 100 MB; the prod
  front-end (WAF/nginx `client_max_body_size`, PHP `upload_max_filesize`)
  must allow ≥ 16 MB. Unverified — check before release.
- **Range streaming** needs the private disk to be local
  (`BinaryFileResponse`). Revisit if storage moves to S3.
- **GC private-variant recognition** is filename-convention based; a bug
  there deletes live thumbnails. Covered by Media tests; dry-run `media:gc`
  after first deploy.
- **Settings ordering**: Fanart's provider must boot after Story's (tab
  `stories` must exist). Enforced by provider order + `booted`; a failing
  boot is loud (registry throws).
- **Most-restrictive mode** costs one settings read per author per check —
  trivial for 1–3 authors; revisit if stories get many co-authors.
- **Signed image URLs for the bot** expire; if the bot posts late the link
  breaks. Revisit (e.g. bot downloads and re-uploads) if Discord embeds go
  blank.
- **Author identity at upload** decides `is_artwork`; an author later removed
  from the story keeps their published artwork as an ordinary fanart. Accepted.
