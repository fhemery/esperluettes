# Chapter Annotations — Architecture

Companion to [`Chapter_Annotations.md`](./Chapter_Annotations.md). The functional spec is normative; this doc translates it into a concrete implementation plan.

**v1 scope is deliberately narrower than the functional spec.** The MVP loop is "highlight → write comment → save → review via pop-up modal", with no in-chapter visual indicators, no post-publish editing, no replies, no gutter, no filter UI. See §8 for the v1-vs-vNext split. The data model and PHP architecture below are sized for the full spec — only the JS / UI surface and a few API methods are trimmed for v1.

Conventions used below:
- File paths under `app/Domains/<Domain>/Public|Private/...` follow the project's domain layout (see `docs/Domain_Structure.md`).
- "Comment domain" means `app/Domains/Comment`. "Story domain" means `app/Domains/Story`.
- Code snippets are illustrative, not final.

## 1. Goal & domain placement

All annotation behaviour — DB tables, controllers, services, routes, Blade components, JS — lives **inside the Comment domain**. The Story domain emits one wrapper around the chapter content and registers an updated `ChapterCommentPolicy`. Nothing else in Story changes.

Rationale: annotations are sub-feedback under a root comment. They reuse comment-domain machinery (per-entity policy registry, moderation cascade, body sanitization, soft-delete semantics) and stay reusable for any future annotable entity (news body, static page, …) without re-wiring Story.

### 1.1 Story domain changes (the only ones)

| Change | File | Description |
|--------|------|-------------|
| Wrap chapter content in `<x-comment::annotable>` | `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` (or wherever chapter body renders) | Replaces the existing wrapping element around `{!! $chapter->content !!}` with `<x-comment::annotable entity-type="chapter" :entity-id="$chapter->id" :gutter="true">…</x-comment::annotable>`. Story does not know what the wrapper does internally. |
| Extend `ChapterCommentPolicy` | `app/Domains/Story/Private/Services/ChapterCommentPolicy.php` | Implement the new annotation-related methods on `CommentPolicy` (§3.2). |

### 1.2 Everything else lives in Comment

- `comments` table additions: none. Annotations are separate tables (§2).
- New table: `comment_annotations` (single table, roots + replies — §2).
- New Blade components: `<x-comment::annotable>`, `<x-comment::annotation-filter>`, plus internal partials for the popover, the pop-up modal, and the floating save banner.
- New routes under the existing `Comment` route file.
- New JS bundle: `app/Domains/Comment/Resources/js/annotations/` registered as a Vite entry point.
- New PHP service `AnnotationService` and public API `AnnotationPublicApi`.
- New moderation topic `chapter-annotation` registered with `ModerationRegistry` + dedicated snapshot formatter and seeded reasons (§3.5).

## 2. Data model

### 2.1 `comment_annotations` (single table for roots + replies)

Owned by Comment domain. Roots and replies share one table — a row with `parent_annotation_id IS NULL` is a root annotation; a row with `parent_annotation_id` set is a reply.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `comment_id` | unsignedBigInteger | FK to `comments.id`. **Denormalised onto reply rows too** so a single `WHERE comment_id IN (…)` returns the entire tree in one query. Always points to the root comment of the thread. |
| `parent_annotation_id` | unsignedBigInteger, nullable | `NULL` for root annotations; FK to `comment_annotations.id` for replies. Replies are one level deep — the application rejects creating a reply whose parent already has `parent_annotation_id`. |
| `author_id` | unsignedBigInteger, nullable | The annotation/reply author. No cross-domain FK to `users`; nullified on user deletion (same rule as comments). |
| `body` | text | HTML, sanitized via `CommentBodySanitizer` with an annotation-scoped HTMLPurifier profile (bold / italic / custom-emoji only, see §3.3). |
| `highlighted_text` | text, nullable | Roots only — the plain-text selection captured at creation. `NULL` on reply rows. |
| `prefix` | string(255), nullable | Roots only. Up to 5 words preceding the selection. May be empty. `NULL` on reply rows. |
| `suffix` | string(255), nullable | Roots only. Up to 5 words following the selection. May be empty. `NULL` on reply rows. |
| `is_processed` | boolean, default `false` | Roots only (semantically). On reply rows, always `false` and ignored. |
| `processed_at` | timestamp, nullable | Roots only. Set when `is_processed` flips to true; cleared on unprocess. |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete. |

Indexes:
- `(comment_id, parent_annotation_id, deleted_at)` — primary access path: fetch every row in one query and split client-side / Eloquent-side by `parent_annotation_id`.
- `(parent_annotation_id)` — for the few reply-only operations (rare; cheap to add).

Tradeoffs:
- **Sparse columns**: `highlighted_text`, `prefix`, `suffix`, `processed_at` are `NULL` on every reply row. We accept that cost (~zero on disk for NULLs; trivial cognitive overhead) for the single-query / single-model win.
- A DB-level CHECK constraint could enforce "roots have a highlight, replies don't" — defer; the application layer enforces it via the request validation.

Notes:
- We do **not** store anchor offsets — re-anchoring is purely client-side and stateless (§5 of the spec).
- We do **not** store the entity type or entity ID on the annotation row. The annotation is reachable via its `comment_id`, which carries `commentable_type` + `commentable_id`.

### 2.2 Eloquent model

- `App\Domains\Comment\Private\Models\CommentAnnotation`

Uses `SoftDeletes`. Declares table / fillable via Laravel 13 PHP attribute syntax (`#[Table]`, `#[Fillable]`) per CLAUDE.md.

Relationships:
- `comment()` belongsTo `Comment`
- `parent()` belongsTo `CommentAnnotation` (self-referential, via `parent_annotation_id`)
- `replies()` hasMany `CommentAnnotation` (self-referential, ordered by `created_at`)

Scopes:
- `scopeRoots()` → `whereNull('parent_annotation_id')`
- `scopeReplies()` → `whereNotNull('parent_annotation_id')`

### 2.3 Cascade & visibility rules

- **Soft delete `comments` row** (existing moderation `delete-comment` action): a new listener inside Comment domain on `CommentDeletedByModeration` soft-deletes all `comment_annotations` rows (roots and replies) for that comment.
- **Empty-content the root comment** (existing moderation): we keep annotations as-is. The root comment shell still exists; annotations are still attached to it.
- **User deletion / deactivation**: existing Auth events `UserDeleted` / `UserDeactivated` / `UserReactivated` are extended to handle `comment_annotations` rows the same way they handle comments (nullify `author_id` / soft-delete / restore).
- **Moderator deletes a root annotation**: set `deleted_at` on that row **and** on every row whose `parent_annotation_id` equals it. Single transaction.
- **Annotation author deletes their own root annotation** (post-publish): same cascade as moderator delete — replies hanging off it are soft-deleted too. Replies aren't visible without their root, so leaving them as orphans would confuse the moderator audit later.
- **Annotation author edits their own annotation body**: no cascade.

### 2.4 Migrations

One migration under `app/Domains/Comment/Database/Migrations/`:
- `YYYY_MM_DD_HHMMSS_create_comment_annotations_table.php`

Working `down()` required. No backfill — green-field feature.

## 3. PHP architecture

### 3.1 New public API: `AnnotationPublicApi`

Sibling to `CommentPublicApi`, in `app/Domains/Comment/Public/Api/AnnotationPublicApi.php`.

Required for v1:

| Method | Description |
|--------|-------------|
| `getForEntity(string $entityType, int $entityId, int $viewerId): AnnotationListDto` | All annotations visible to the viewer on a given entity (chapter), with their replies. Visibility rules per spec §3. Used by the eager load on chapter page open. |
| `createForRootComment(int $commentId, int $authorId, array $annotations): array` | Atomically creates N annotations under a root comment. Returns inserted IDs. Called from the extended `POST /comments` flow when the payload includes `annotations[]`. Throws `AnnotationValidationException` on any failure → caller rolls back the whole comment-create transaction. |
| `applyChanges(int $commentId, int $authorId, AnnotationChangeSetDto $changes): AnnotationListDto` | Post-publish batch save. `$changes` describes added/edited/deleted annotation rows. Atomic within a single DB transaction. Returns the new full list of the author's annotations on the entity. |
| `addReply(int $annotationId, int $authorId, string $body): AnnotationDto` | Single AJAX call (author / commenter). Creates a row with `parent_annotation_id` set; returns it as an `AnnotationDto`. |
| `setProcessed(int $annotationId, int $byUserId, bool $value): void` | Single AJAX call (chapter author / co-author). Policy-checked. |
| `moderatorDelete(int $annotationId, int $byUserId): void` | Soft-deletes the annotation + cascades replies. |
| `moderatorEmptyContent(int $annotationId, int $byUserId): void` | Body becomes the standard "[contenu retiré]" placeholder. |

DTOs (under `app/Domains/Comment/Public/Api/Contracts/`):

- `AnnotationDto` — `{ id, commentId, parentAnnotationId, authorId, authorProfile, body, highlightedText, prefix, suffix, isProcessed, processedAt, createdAt, replies: AnnotationDto[], canEdit, canDelete, canReply, canMarkAsProcessed }`. The same DTO shape is used for root annotations and replies: a reply has `parentAnnotationId` set, `highlightedText`/`prefix`/`suffix` null, and an empty `replies` array.
- `AnnotationListDto` — `{ entityType, entityId, items: AnnotationDto[], canAnnotate, canMarkAsProcessed, viewerRole: 'commenter'|'author'|'moderator'|'none' }`. `items` holds only the root annotations; their replies are nested under `replies`. `viewerRole` lets the client render the right action set without re-deriving permissions client-side.
- `AnnotationToCreateDto` — `{ body, highlightedText, prefix, suffix }`
- `AnnotationChangeSetDto` — `{ added: AnnotationToCreateDto[], edited: { id, body }[], deleted: int[] }`

### 3.2 Updated `CommentPolicy` contract

Add these methods to `app/Domains/Comment/Public/Api/Contracts/CommentPolicy.php` and provide default `false` / `null` implementations on `DefaultCommentPolicy`:

```php
public function isAnnotable(): bool;
public function canAnnotate(int $entityId, int $userId): bool;
public function getAnnotationBodyMinLength(): ?int;   // default 1
public function getAnnotationBodyMaxLength(): ?int;   // default 1000
public function getAnnotationHighlightedTextMaxLength(): ?int; // default 500
public function canMarkAsProcessed(int $entityId, int $userId): bool;
```

`ChapterCommentPolicy` overrides:
- `isAnnotable()` → `true`.
- `canAnnotate()` → delegates to existing logic: not a chapter author / co-author **and** signed-in. (Note: the rule "user already posted a root comment" does NOT block annotation creation post-publish — they're editing their own thread.)
- `canMarkAsProcessed()` → `true` if the user is an author / co-author of the chapter's story.
- `getAnnotation*Length()` → return the spec's constants (1 / 1000 / 500).

`CommentPolicyRegistry` gets matching pass-through methods (mirrors the existing pattern).

### 3.3 Body sanitizer profile

Add a second HTMLPurifier profile to `CommentBodySanitizer`: `annotation` (bold/italic/custom-emoji only). The existing `strict` profile stays for root comments and replies.

The sanitizer accepts a `profile` argument; the `AnnotationService` always passes `'annotation'`.

### 3.4 Services

- `AnnotationService` — orchestrates create / update / delete / reply / process / moderate. Wraps multi-row mutations in DB transactions. Calls `CommentBodySanitizer`. Enforces policy.
- `AnnotationAccessService` — viewer-scoped visibility: given `(viewerId, entityType, entityId)`, returns the rows the viewer is allowed to see, with per-row permission flags filled in. Used by `getForEntity`.

Visibility rule (re-stated for implementation):
- Viewer is the annotation author → their own annotations (incl. their replies and any replies from authors on those annotations).
- Viewer is a chapter author / co-author / moderator → all annotations on the chapter, with all replies.
- Anyone else → empty list, `viewerRole = 'none'`.
- "Orphan reply" on a deleted annotation → not returned (the annotation soft-delete hides everything beneath it for normal viewers; moderators can still see deleted rows if we ever expose a moderation-only flag, deferred).

### 3.5 Moderation topic

A new topic **`chapter-annotation`** is registered with `ModerationRegistry` in `CommentServiceProvider::boot()`:

```php
app(ModerationRegistry::class)->register(
    key: 'chapter-annotation',
    displayName: __('comment::annotation_moderation.topic_name'),
    formatterClass: AnnotationSnapshotFormatter::class,
);
```

- `AnnotationSnapshotFormatter` (under `app/Domains/Comment/Private/Mappers/`) implements `SnapshotFormatterInterface` and renders a Blade view (`comment::moderation.annotation-snapshot`) that shows: chapter title + link, commenter display name + avatar, the annotation's highlighted-text blockquote, the annotation body, and the parent root-comment body.
- A new lang file `app/Domains/Comment/Private/Resources/lang/{fr,en}/annotation_moderation.php` provides the topic display name and snapshot labels.
- The `ModerationSeeder` is extended (or a Comment-domain seeder is added) to insert the topic's default reasons. Proposed initial set, FR-leaning: **"Hors-sujet"**, **"Spoiler non balisé"**, **"Propos offensants"**, **"Spam"**, **"Autre"**. Admins manage the full list via the existing reasons admin panel.
- No new HTTP route is added for reporting. The "Report" action in the per-commenter pop-up calls the existing `GET /moderation/report-form/chapter-annotation/{annotationId}` → `POST /moderation/report` flow.
- When a moderator approves a report on a `chapter-annotation`, the admin action is the existing one (per the Moderation admin panel) — for v1, the admin uses the same per-commenter pop-up's **Delete annotation** to take action.

### 3.6 Listeners

New listeners under `app/Domains/Comment/Private/Listeners/`. Each one operates on the single `comment_annotations` table (rows for both roots and replies):

| Listener | Event | Action |
|----------|-------|--------|
| `CascadeAnnotationsOnCommentDeleted` | `CommentDeletedByModeration` | Soft-delete every `comment_annotations` row whose `comment_id` matches. |
| `CascadeAnnotationsOnUserDeleted` | `Auth::UserDeleted` | Nullify `author_id` on every `comment_annotations` row authored by the user. |
| `CascadeAnnotationsOnUserDeactivated` | `Auth::UserDeactivated` | Soft-delete every `comment_annotations` row authored by the user. |
| `CascadeAnnotationsOnUserReactivated` | `Auth::UserReactivated` | Restore them. |

No events are emitted by annotation lifecycle in v1 (deferred — see spec §10).

### 3.7 Routes

Appended to `app/Domains/Comment/Private/routes.php`. Middleware mirrors the existing comment routes: `auth` + `compliant` for read, plus `role:user-confirmed` + `compliant` for writes; `role:moderator,admin,tech-admin` for moderation actions.

| Method | Path | Middleware | Description |
|--------|------|------------|-------------|
| `GET` | `/comments/annotations` | `auth`, `compliant` | Query params: `entity_type`, `entity_id`. Returns `AnnotationListDto` for the viewer. Used by `<x-comment::annotable>` on page load. |
| `POST` | `/comments` | `auth`, `compliant`, `role:user-confirmed` | The existing root-comment-create endpoint is extended to accept an optional `annotations[]` array. If provided, the controller wraps comment creation + annotation creation in a single DB transaction; failure on either rolls back both. (No middleware change beyond what already protects the endpoint today.) |
| `PATCH` | `/comments/{commentId}/annotations` | `auth`, `compliant`, `role:user-confirmed` | Body: `AnnotationChangeSetDto` payload. Applies the post-publish batch. Only the comment author can call. |
| `POST` | `/comments/annotations/{annotationId}/replies` | `auth`, `compliant`, `role:user-confirmed` | Creates a reply. |
| `DELETE` | `/comments/annotations/{annotationId}/replies/{replyId}` | `auth`, `compliant`, `role:user-confirmed` | Soft-delete own reply, or moderator delete. |
| `POST` | `/comments/annotations/{annotationId}/processed` | `auth`, `compliant`, `role:user-confirmed` | Body: `{ value: bool }`. Toggles processed flag. Policy: `canMarkAsProcessed`. |
| `DELETE` | `/comments/annotations/{annotationId}` | `auth`, `compliant`, `role:moderator,admin,tech-admin` | Moderator delete. |
| `POST` | `/comments/annotations/{annotationId}/empty-content` | `auth`, `compliant`, `role:moderator,admin,tech-admin` | Moderator empty-content. |

Replies are addressed under `/comments/annotations/{id}/replies/...` (not by their own ID at the top level) so the parent-child relationship stays visible in the URL.

Naming kept under `/comments/annotations/...` rather than a top-level `/annotations/...` to communicate the parent-child relationship (and keep route file ownership obvious).

### 3.8 Controllers

Two new controllers, mirroring the comment-controller layout:
- `AnnotationController` — create-via-comment-controller is handled by extending the existing `CommentController::store` (small diff: parse `annotations[]`, call `AnnotationPublicApi::createForRootComment` inside the same transaction). Other endpoints get their own controller methods.
- `AnnotationModerationController` — moderator delete / empty-content.

Form requests:
- `StoreAnnotationsWithCommentRequest` — extends the existing root-comment validation with annotation-array validation (each item has `body`, `highlighted_text`, `prefix`, `suffix`, length constraints from policy).
- `ApplyAnnotationChangesRequest` — diff payload validation.
- `StoreAnnotationReplyRequest`.

## 4. Frontend architecture

### 4.1 Component split

Three Blade components in `app/Domains/Comment/Public/View/Components/` (paired with templates in `Private/Resources/views/components/`):

#### `<x-comment::annotable>`
Wraps the annotable content. Renders:
- A wrapping `<div class="comment-annotable" data-annotable data-entity-type=… data-entity-id=… data-can-annotate=… data-viewer-role=…>`. JS bootstraps off this attribute set.
- A sibling `<div class="comment-annotation-gutter" aria-hidden="true">` (md+ only via Tailwind responsive classes) where avatars / count-bubbles are placed by JS. Hidden on `<md`.
- The slot content (the chapter body HTML).
- An always-rendered `<template>` containing the inline toolbar markup and the inline form markup so the JS can `cloneNode` them at runtime (no string-built HTML).

Behaviour:
- The component server-renders nothing about specific annotations — it only emits the bootstrap container. The annotation data is fetched client-side via `GET /comments/annotations?entity_type=…&entity_id=…`.
- It also boots the **floating save banner** for post-publish pending changes (rendered once at the chapter page level so it floats over the viewport).

Props: `entityType`, `entityId`, optional `gutter` (default `true`).

If the page contains **more than one** `[data-annotable]`, the JS logs a console warning and refuses to bootstrap (per your call — "the framework pouts"). Single annotable region per page in v1.

#### `<x-comment::annotation-filter>`
The filter menu (commenter checklist + Show processed toggle). Rendered **top-right of the chapter content area, next to the chapter title** (per the chapter Blade template). Hidden on `<md`. Reads its initial state from the Alpine `annotations` store that the annotable JS exposes.

Story drops this component manually in the chapter `show.blade.php` template — Comment exports the component; Story chooses where to place it.

#### `<x-comment::comment-list>` (existing — additive changes)
Two additions:
- For each root comment row that has at least one visible annotation (count comes from a new field on the DTO returned by `CommentPublicApi::getFor`, see §4.5), renders an **"N annotations"** button between the comment header and the comment body. Click → opens the per-commenter pop-up modal.
- The pop-up modal is a single instance rendered once at the end of the component (any button on any row addresses the same modal — content swapped on open).

The pop-up is a separate Alpine component (`commentAnnotationModal`) that fetches the per-commenter annotation list on open.

### 4.2 JS layout

New directory: `app/Domains/Comment/Resources/js/annotations/`.

```
annotations/
├── bootstrap.js              # Entrypoint. Finds [data-annotable], wires up Alpine stores, kicks off load.
├── api/
│   └── client.js             # axios calls: list, applyChanges, addReply, setProcessed, etc.
├── stores/
│   ├── annotations-store.js  # Alpine.store('annotations'): server-loaded list + view state
│   ├── drafts-store.js       # Pre-publish drafts (local storage)
│   ├── pending-store.js      # Post-publish pending changes (local storage)
│   └── filter-store.js       # Per-session commenter / processed filter state
├── selection/
│   ├── canonical-text.js     # buildCanonicalText(rootEl) → { text, emojiMap, blockBoundaries }
│   ├── extract-anchor.js     # extractAnchor(range, rootEl) → { highlighted, prefix, suffix }
│   └── reanchor.js           # findAnchor(canonicalText, { prefix, highlighted, suffix }) → match | 'missing'
├── ui/
│   ├── toolbar.js            # Floating selection toolbar (Alpine component)
│   ├── inline-form.js        # Quick-input form (Alpine component, reuses editor)
│   ├── gutter.js             # Avatar/badge placement in the gutter
│   ├── popover.js            # Right-margin popover (uses <x-shared::popover>)
│   ├── modal.js              # Per-commenter pop-up (uses <x-shared::modal>)
│   ├── filter-menu.js        # Filter UI behaviour
│   └── save-banner.js        # Floating "Vous avez X annotations non sauvegardées" banner
└── index.js                  # Registers all Alpine components/stores, calls bootstrap()
```

Add a new Vite input in `vite.config.js`:
```js
'app/Domains/Comment/Resources/js/annotations/index.js'
```
Loaded conditionally: each of `<x-comment::annotable>`, `<x-comment::annotation-filter>`, and the modified `<x-comment::comment-list>` issues a `@vite('app/Domains/Comment/Resources/js/annotations/index.js')` directive inside a `@once`-guarded `@push('scripts')` block, so the bundle is included exactly once per page when any of those components is rendered. If that turns out flaky in practice, the chapter `show.blade.php` template can `@vite(...)` the bundle directly as a fallback.

### 4.3 Pure functional core

The hard parts are pure functions, isolated from Alpine and DOM events:

- `buildCanonicalText(rootEl)` — walks the DOM of the annotable container and returns:
  - `text`: a single string with HTML stripped, custom emoji blots replaced by `:name:`, block boundaries materialised as a single space.
  - `nodeMap`: an array of `{ start, end, domNode }` entries letting downstream code map a `text` offset back to a DOM `Text` node (needed to *place* a highlight visually).
- `extractAnchor(range, rootEl, canonicalText)` — given a `Range` over `rootEl`, returns `{ highlighted, prefix, suffix }`:
  - converts Range start/end to canonical-text offsets via `nodeMap`
  - reads ≤5 words on each side (word boundary = whitespace run in canonical text)
  - validates length (highlighted ≤ 500 plain-text chars).
- `findAnchor(canonicalText, { prefix, highlighted, suffix })` — implements §5.2 of the spec exactly. Returns `{ status: 'ok'|'moved'|'missing', start, end }` where `start`/`end` are offsets into canonical text. (UI layer maps that back to DOM ranges via `nodeMap`.)

Everything else (gutter placement, Alpine bindings, fetch wiring) builds on these pure functions.

### 4.4 Local storage schema

Two keys per `(chapterId, userId)`:

```
chapter-annotations:drafts:{userId}:{chapterId}
chapter-annotations:pending:{userId}:{chapterId}
```

Values are JSON:
```json
{
  "version": 1,
  "rootCommentDraft": "<p>...</p>",          // drafts key only
  "annotations": [
    { "tempId": "uuid", "body": "<p>...</p>", "highlighted": "...", "prefix": "...", "suffix": "..." }
  ],
  "edited": [ { "id": 123, "body": "<p>...</p>" } ],   // pending key only
  "deleted": [ 124, 125 ]                                // pending key only
}
```

`version` lets us evolve the schema without leaving zombie data in user browsers; on read, mismatched versions are discarded.

### 4.5 Comment-list integration

`CommentPublicApi::getFor` returns each root comment's annotation count for the viewer (a single field added to `CommentDto`: `viewerAnnotationCount: int`). The count is the number of annotations on the chapter from that commenter that the **viewer** can see — i.e., the commenter herself sees her own count; the author sees the same count (filters never apply to this surface, per spec §4.5).

A single `viewerAnnotationCount > 0` triggers the "N annotations" button rendering in `<x-comment::comment-list>`.

### 4.6 Filter / processed updates between surfaces

The right-margin popover offers Reply + Mark-as-processed. Both immediate AJAX. When the response succeeds:
- The Alpine `annotations-store` is updated in-place.
- The gutter re-renders avatars (and re-runs the "first visible commenter" rule).
- If the pop-up modal is currently open, it also updates if the affected annotation belongs to the displayed commenter.

State flows top-down from one shared store; no surface fetches independently after the first load (except the modal on open, which fetches per commenter to keep that path simple).

## 5. JS testing strategy

### 5.1 Stack

Add to `package.json` devDependencies:
- `vitest`
- `happy-dom`
- `@testing-library/dom`
- `@testing-library/user-event`

`vitest.config.js` at repo root, sharing the existing Vite config:
```js
import { defineConfig } from 'vitest/config';
export default defineConfig({
  test: {
    environment: 'happy-dom',
    include: ['app/Domains/**/Resources/js/**/*.test.js'],
  },
});
```

Test files colocate with the modules they test, suffixed `.test.js`. Pre-commit and CI run `npx vitest run`.

### 5.2 Tiered tests

| Tier | What it covers | Where it runs |
|------|---------------|---------------|
| 1: pure functions | `canonical-text.js`, `extract-anchor.js`, `reanchor.js`, every store's reducer, local-storage round-trip, payload builders | Pre-commit (all JS tests) + CI |
| 2: Alpine DOM tests | Toolbar appearance on (synthetic) selection, inline-form save / cancel, modal open/close, filter menu state, save-banner show/hide | Pre-commit + CI (still cheap; happy-dom) |
| 3: visual / positioning | Avatar / badge placement, overlay re-layout | **Manual.** Not automated in v1. |

### 5.3 Mocking the Selection API

happy-dom's `Selection` / `Range` support is partial. The pattern we'll use:
- Tests never call `window.getSelection()`. They construct a `Range` directly with `document.createRange()`, set `setStart` / `setEnd` to existing text nodes, and pass that `Range` to `extractAnchor(range, rootEl)`.
- The DOM-event listener in `toolbar.js` is the thinnest possible layer: it reads the current selection's first range and forwards it to the pure extractor. This single thin layer is verified manually; the meat is tested via Tier 1.

### 5.4 Pre-commit and CI

- `scripts/husky-precommit.js`: append a `vitest run` step (after deptrac, before PHP staged tests). Fast; runs all JS tests every commit per your call.
- CI: add a `vitest run` job alongside `composer deptrac` and the PHP test suite. Same npm install step the existing build already runs.

## 6. File layout summary

```
app/Domains/Comment/
├── Database/
│   └── Migrations/
│       └── YYYY_..._create_comment_annotations_table.php
├── Public/
│   ├── Api/
│   │   ├── AnnotationPublicApi.php
│   │   └── Contracts/
│   │       ├── AnnotationDto.php
│   │       ├── AnnotationReplyDto.php
│   │       ├── AnnotationListDto.php
│   │       ├── AnnotationToCreateDto.php
│   │       └── AnnotationChangeSetDto.php
│   ├── View/
│   │   └── Components/
│   │       ├── AnnotableComponent.php
│   │       ├── AnnotationFilterComponent.php
│   │       └── CommentListComponent.php  (modified)
│   └── Providers/
│       └── CommentServiceProvider.php  (modified: register new component + new listeners)
├── Private/
│   ├── Controllers/
│   │   ├── AnnotationController.php
│   │   ├── AnnotationModerationController.php
│   │   └── CommentController.php  (modified store())
│   ├── Listeners/
│   │   ├── CascadeAnnotationsOnCommentDeleted.php
│   │   ├── CascadeAnnotationsOnUserDeleted.php
│   │   ├── CascadeAnnotationsOnUserDeactivated.php
│   │   └── CascadeAnnotationsOnUserReactivated.php
│   ├── Mappers/
│   │   └── AnnotationSnapshotFormatter.php
│   ├── Models/
│   │   └── CommentAnnotation.php
│   ├── Requests/
│   │   ├── StoreAnnotationsWithCommentRequest.php
│   │   ├── ApplyAnnotationChangesRequest.php
│   │   └── StoreAnnotationReplyRequest.php
│   ├── Resources/
│   │   ├── lang/
│   │   │   ├── fr/annotation_moderation.php  (new)
│   │   │   └── en/annotation_moderation.php  (new)
│   │   └── views/
│   │       ├── moderation/
│   │       │   └── annotation-snapshot.blade.php  (new)
│   │       └── components/
│   │           ├── annotable.blade.php
│   │           ├── annotation-filter.blade.php
│   │           ├── comment-list.blade.php  (modified)
│   │           └── partials/
│   │               ├── annotation-popover.blade.php
│   │               ├── annotation-modal.blade.php
│   │               ├── annotation-toolbar.blade.php
│   │               ├── annotation-inline-form.blade.php
│   │               └── annotation-save-banner.blade.php
│   ├── Services/
│   │   ├── AnnotationService.php
│   │   └── AnnotationAccessService.php
│   ├── Support/
│   │   └── CommentBodySanitizer.php  (modified: new 'annotation' profile)
│   └── routes.php  (modified)
└── Resources/
    └── js/
        └── annotations/  (per §4.2)

app/Domains/Shared/Resources/
├── js/editor-bundle.js  (modified: accept toolbar-options list)
└── views/components/editor.blade.php  (modified: toolbar-options prop replaces withHeadings/withLinks/withSpoiler)

app/Domains/Story/Private/
├── Resources/views/chapters/show.blade.php  (modified: wrap chapter content in <x-comment::annotable>)
└── Services/ChapterCommentPolicy.php  (modified: implement new annotation methods)
```

## 7. Editor refactor (shared)

The `editor.blade.php` toolbar customisation moves from boolean toggles to a single ordered list of features.

Before (current):
```blade
<x-shared::editor withHeadings="true" withLinks="true" withSpoiler="false" .../>
```

After (proposed):
```blade
<x-shared::editor :toolbar="['bold','italic','underline','strike','blockquote','list','align','custom-emoji','header','link']" .../>
```

For the annotation editor we pass:
```blade
<x-shared::editor :toolbar="['bold','italic','custom-emoji']" .../>
```

The blade component forwards the `toolbar` array as a JSON `data-toolbar` attribute; `editor-bundle.js::initQuillEditor` reads it and builds the toolbar + allowed-formats list dynamically. The current `withHeadings`/`withLinks`/`withSpoiler` props are removed and all call sites migrated in the same PR (search-and-replace).

This is a Shared-domain refactor consumed by Comment and Story (story-form editors, comment editors). Migration is mechanical and bounded.

## 8. Phased delivery — see the implementation plan

The concrete phase-by-phase delivery plan lives in [`Chapter_Annotations_Implementation_Plan.md`](./Chapter_Annotations_Implementation_Plan.md). This section is kept short and only summarises scope so this architecture doc stays a reference for the *contract* (data model, public API, JS structure) rather than a project tracker.

### 8.1 v1 scope (the only scope we commit to right now)

v1 ships the **core feedback loop** and deliberately omits everything that requires positioning math or post-publish state machines.

**In v1**
- Highlight a passage → click **"Annoter"** in a small floating toolbar → write a comment in a minimal editor → **Save** stages the annotation as a local-storage draft.
- A persistent banner above the root-comment editor shows: *"{N} annotations, écrivez votre commentaire pour les sauvegarder"*, with a **"Voir les annotations"** button that opens the pop-up modal in **drafts mode**: drafts list, with Edit and Delete on each row.
- Submitting the root comment commits everything atomically (root comment + N annotations in one DB transaction). Local-storage drafts are then cleared.
- After publish, both the commenter and the chapter author / co-author see an **"N annotations"** button just above each affected root comment row in the comments section. Clicking opens the pop-up modal in **server mode** scoped to that commenter.
- In the pop-up (server mode):
  - **Commenter** on their own row: read-only list (annotation body + highlighted passage as a blockquote).
  - **Chapter author / co-author**: per-row **Mark/Unmark as processed** (immediate AJAX).
  - **Moderator**: per-row **Delete annotation** (immediate AJAX).
- Root-comment local-storage drafting (phase 2) ships first as a standalone improvement and the rest of the local-storage pattern reuses it.

**Out of v1** (explicitly deferred to vNext)
- The three quick-emoji shortcuts (❤️ 🔥 👍) — v1 has only the **"Annoter"** button.
- Any in-chapter visual indicator of annotated passages (no tint, no gutter avatars, no count bubbles, no in-margin popover). The chapter body is unchanged visually.
- Post-publish editing of annotations (no add / edit / delete after submit; no pending-changes banner).
- Replies on annotations (the chapter author cannot reply from the pop-up).
- Per-annotation Report. (Reports keep targeting the root comment via the existing comment-list affordance.)
- Filter menu (commenter checklist + Show processed). With no gutter in v1, there is nothing to filter; the pop-up is per-commenter by construction.
- Client-side re-anchoring. With nothing displayed in-chapter, there is nothing to re-anchor; the anchor fields are stored but never read back in v1.

### 8.2 Post-v1 roadmap (not committed)

These are intentionally vague — we'll plan each properly when v1 ships and we have real usage feedback. They are listed only so the v1 data model and code structure can be checked against them.

| Letter | Theme | Notes |
|--------|-------|-------|
| A | Quick-emoji shortcut | Adds the three quick buttons (❤️ 🔥 👍) to the floating toolbar. No data-model impact. |
| B | Post-publish edit / add / delete | Builds the pending-changes store, save banner, `PATCH /comments/{commentId}/annotations` endpoint, `AnnotationPublicApi::applyChanges`. The model already supports it. |
| C | Replies on annotations | Adds `addReply` + reply UI in the pop-up; later in the gutter popover. Schema already supports replies (single table). |
| D | Gutter — count bubbles | First visual indicator in the right column, simplest primitive. Needs `reanchor.js` (also not in v1). |
| E | Gutter — avatars | Replaces count bubbles with the per-commenter avatar + "+N" badge. Only if D is insufficient. |
| F | Gutter popover | In-margin Reply + Mark-as-processed for the chapter author. |
| G | Filter menu | `<x-comment::annotation-filter>` component, top-right of chapter content; filter scope right margin only (per spec). |
| H | Per-annotation Report | Only if user testing surfaces the need. Would add a new moderation topic `chapter-annotation`. |
| I | In-chapter highlight tint | Subtle background tint on annotated passages. Cheap once D's `reanchor.js` exists. |

The functional spec ([`Chapter_Annotations.md`](./Chapter_Annotations.md)) remains the long-term vision document. The v1-vs-vNext split lives only here so the spec stays a stable target.

## 9. Decisions locked

| # | Decision | Choice |
|---|----------|--------|
| 1 | Annotations + replies schema | Single table `comment_annotations` with nullable `parent_annotation_id`. `comment_id` denormalised on reply rows for one-query fetch. |
| 2 | `processed_at` timestamp | Keep — cheap analytics surface later. |
| 3 | `viewerAnnotationCount` on `CommentDto` | Keep eager — one COUNT per `CommentPublicApi::getFor` page is acceptable. |
| 4 | Vite bundle inclusion | Conditional via `@push('scripts') @once @vite(...)` from the Comment components; chapter `show.blade.php` may include directly as a fallback. |
| 5 | `<x-comment::annotation-filter>` placement | Top-right of the chapter content area, next to the chapter title. Story-side placement. (vNext only — no filter UI in v1.) |
| 6 | Annotation-route middleware | `auth` + `compliant` + `role:user-confirmed` for writes; `role:moderator,admin,tech-admin` for moderation. `auth` + `compliant` for the read endpoint. |
| 7 | Phasing | v1 is the highlight → comment → save → pop-up loop (no in-chapter UI). vNext adds post-publish editing, replies, gutter, filter. See §8.1 / §8.3. |
| 8 | v1 trigger to open the drafts modal | A persistent banner *"{N} annotations, écrivez votre commentaire pour les sauvegarder"* above the root-comment editor with a **"Voir les annotations"** button. |
| 9 | v1 toolbar buttons | Single **"Annoter"** button. The three quick-emoji shortcuts (❤️ 🔥 👍) are vNext. |
| 10 | v1 report flow | Per-annotation Report available in the per-commenter pop-up (chapter author / co-author). New `chapter-annotation` moderation topic with a seeded set of default reasons, admin-managed afterwards. Submission reuses the existing `POST /moderation/report` endpoint — no new HTTP route added by Comment domain. |

## 10. Risks acknowledged

- **Gutter layout stability** (phases 12–13). We start with count-bubbles. If acceptable, avatars stay deferred indefinitely.
- **Selection-API gesture** (the "drag to highlight" interaction itself) is not automated. Only the pure functions consuming the resulting `Range` are tested.
- **Editor refactor blast radius** (phase 1). Mechanical but touches every form using `<x-shared::editor>`. Single atomic PR; manual smoke pass through every form before merge.
- **`compliant` + `role:user-confirmed` may be partially redundant.** We carry both deliberately; verify during phase 5 implementation whether one already implies the other and trim if so.

## 11. Next steps

Phase 1 (editor refactor) is the next concrete unit of work. Before starting it, I want to walk through:

1. The exact new prop shape on `<x-shared::editor>` — single `:toolbar="[...]"` array, what tokens are valid (`'bold'`, `'italic'`, `'underline'`, `'strike'`, `'blockquote'`, `'list'`, `'align'`, `'custom-emoji'`, `'header'`, `'link'`, `'spoiler'`), and how `editor-bundle.js::initQuillEditor` reads it.
2. The inventory of every existing call site that passes any of `withHeadings` / `withLinks` / `withSpoiler` or relies on the implicit default toolbar set — to migrate them in the same PR.
3. The smoke surface: which pages need to be eyeballed before merge (every form that renders the editor: story create/edit, chapter create/edit, root-comment, reply-comment, profile bio, news, FAQ if applicable).

Once that inventory is agreed, the refactor itself is one focused PR.
