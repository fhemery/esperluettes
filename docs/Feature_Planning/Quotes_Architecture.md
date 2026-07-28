# Quotes — Architecture

Companion to [`Quotes.md`](./Quotes.md). The functional spec is normative; this doc translates it into a concrete implementation plan.

**Quote lands before Annotations.** The common infrastructure established here — JS test tooling, shared anchoring functions, and the `<x-comment::annotable>` toolbar slot — becomes the foundation that the Annotations feature builds on. See [`annotations/02-architecture.md`](./annotations/02-architecture.md) for how Annotations references these bricks.

Conventions:
- File paths under `app/Domains/<Domain>/Public|Private/...` follow the project's domain layout (see `docs/Domain_Structure.md`).
- Code snippets are illustrative, not final.

## 1. Domain placement

### 1.1 New `Quote` domain

All quote behaviour lives in `app/Domains/Quote`. Quotes are personal reading artefacts independent of the comment system; they have their own table, service, public API, profile tab, settings entry, and notification event. Placing them in Comment (no comment dependency), Story (they are a reader artefact, not story content), or Profile (Profile renders, it does not own data) would blur domain boundaries.

### 1.2 Changes in other domains

| Domain | File | Change |
|--------|------|--------|
| **Comment** | `annotable.blade.php` | Gains a `@slot('toolbar-actions')` so consuming pages can inject toolbar buttons without Comment knowing about Quote or Annotations. |
| **Shared** | `Resources/js/anchoring/` | New directory with three pure JS modules (`canonical-text.js`, `extract-anchor.js`, `reanchor.js`) — established by this feature, reused by Annotations. |
| **Story** | `chapters/show.blade.php` | Wraps chapter content in `<x-comment::annotable>` and places `<x-quote::toolbar-button>` in the toolbar slot. |
| **Profile** | `show.blade.php` | Hardcoded `<x-quote::profile-tab>` component (no registry mechanism in v1). |
| **Settings** | *(service provider)* | Quote registers the `quote.book_public` boolean setting via the existing Settings extensibility mechanism. |

## 2. Data model

### 2.1 `quotes` table

Owned by the Quote domain.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | unsignedBigInteger, nullable | Nullified on user deletion (same pattern as comments). No cross-domain FK. |
| `chapter_id` | unsignedBigInteger | Plain integer, no FK constraint (cross-domain — Story owns chapters). |
| `story_id` | unsignedBigInteger | Denormalised. Avoids a join when loading the quote book, which needs story title and authors. No FK constraint. |
| `highlighted_text` | text | Plain text. Verbatim selection. Immutable after creation. |
| `prefix` | string(255), nullable | Up to 5 plain-text words before the selection. Immutable. |
| `suffix` | string(255), nullable | Up to 5 plain-text words after the selection. Immutable. |
| `note` | text, nullable | HTML, sanitized (bold / italic / custom-emoji only). Mutable. |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete. |

Indexes:
- `(user_id, deleted_at)` — primary access path for the quote book.
- `(chapter_id, user_id, deleted_at)` — fetch the viewer's own quotes when opening a chapter.
- `(story_id, deleted_at)` — available for future story-level queries.

### 2.2 Eloquent model

`App\Domains\Quote\Private\Models\Quote`

Uses `SoftDeletes`. Laravel 13 attribute syntax (`#[Table]`, `#[Fillable]`).

No relationships to Story models — cross-domain references are resolved through `StoryPublicApi` at query time, not via Eloquent joins.

### 2.3 Cascade & lifecycle rules

| Trigger | Action |
|---------|--------|
| User deleted (`Auth::UserDeleted`) | Nullify `user_id` on all Quote rows for that user. |
| User deactivated (`Auth::UserDeactivated`) | Soft-delete all Quote rows for that user. |
| User reactivated (`Auth::UserReactivated`) | Restore soft-deleted rows. |
| Chapter/story soft-deleted | **No cascade.** Quote rows survive. The service detects missing references at render time via `StoryPublicApi` and applies the "non disponible" badge. |

### 2.4 Migration

`app/Domains/Quote/Database/Migrations/YYYY_MM_DD_HHMMSS_create_quotes_table.php`

Working `down()` required. No backfill — green-field feature.

## 3. PHP architecture

### 3.1 `QuotePublicApi`

`app/Domains/Quote/Public/Api/QuotePublicApi.php`

| Method | Description |
|--------|-------------|
| `getForChapter(int $chapterId, int $viewerId): QuoteListDto` | Returns the viewer's own quotes on a chapter. Used by the chapter page on load. |
| `create(int $chapterId, int $userId, CreateQuoteDto $dto): QuoteDto` | Validates, sanitizes, persists, emits `ChapterPassageQuoted`. Policy-checked. |
| `updateNote(int $quoteId, int $userId, string $note): QuoteDto` | Sanitizes and persists the updated note. Policy-checked (owner only). |
| `delete(int $quoteId, int $userId): void` | Soft-deletes. Policy-checked (owner only). |
| `getForProfile(int $profileUserId, int $viewerId, int $page): QuoteListDto` | Paginated quote list for the profile tab. Applies visibility rules and filters out inaccessible-chapter entries for non-owners. |

DTOs under `app/Domains/Quote/Public/Api/Contracts/`:

- `QuoteDto` — `{ id, chapterId, storyId, highlightedText, prefix, suffix, note (owner only, null otherwise), storyTitle, storyUrl, chapterTitle, chapterUrl, authorProfiles[], createdAt, canEditNote, canDelete, chapterAvailable: bool, anchorMissing: bool }`.
  - `note` is populated only when `viewerId === quote.userId`; it is `null` in all other contexts (public profile view, notification content, etc.).
  - `chapterAvailable` / `anchorMissing` let the client render the appropriate badge without re-checking the database.
- `CreateQuoteDto` — `{ highlightedText, prefix, suffix, note }`.
- `QuoteListDto` — `{ items: QuoteDto[], viewerIsOwner: bool, canQuote: bool, page: int, totalCount: int }`.

### 3.2 `QuoteService`

Private service. Orchestrates all mutations. Wraps DB writes in transactions. Calls `QuoteNoteSanitizer`. Enforces policy. Emits events.

Key responsibilities:
- On `create`: validate anchor fields, enforce highlight length (≤ 500 chars), sanitize note, persist, emit `ChapterPassageQuoted`.
- On `getForProfile`: call `StoryPublicApi` to resolve story/chapter metadata in a single batch call; for entries where the chapter is inaccessible to the viewer, filter them out.

### 3.3 `QuotePolicy`

`app/Domains/Quote/Private/Services/QuotePolicy.php`

| Method | Logic |
|--------|-------|
| `canQuote(int $chapterId, int $userId): bool` | User is confirmed; user is **not** an author or co-author of the chapter's story. Calls `StoryPublicApi::isAuthorOrCoAuthor(int $storyId, int $userId)`. |
| `canViewQuoteBook(int $profileUserId, int $viewerId): bool` | Viewer is the owner, OR the book is public (`quote.book_public === true`) AND the viewer is confirmed. |
| `canEditOrDelete(Quote $quote, int $userId): bool` | Quote's `user_id === $userId`. |

### 3.4 `QuoteNoteSanitizer`

`app/Domains/Quote/Private/Support/QuoteNoteSanitizer.php`

Standalone HTMLPurifier wrapper for the `note` field. Allowed tags and attributes: `<strong>`, `<em>`, `<span class="ql-custom-emoji-*">` only. Mirrors the `annotation` profile in `CommentBodySanitizer` but lives entirely in the Quote domain to avoid a cross-domain dependency on Comment internals.

### 3.5 Domain event

`App\Domains\Quote\Events\ChapterPassageQuoted`

Emitted by `QuoteService::create()`. Payload: `quoterId`, `chapterId`, `storyId`, `highlightedText`. Dispatched via the Events domain bus.

### 3.6 Listeners

`app/Domains/Quote/Private/Listeners/`

| Listener | Event | Action |
|----------|-------|--------|
| `NullifyUserOnUserDeleted` | `Auth::UserDeleted` | `UPDATE quotes SET user_id = NULL WHERE user_id = ?` |
| `SoftDeleteOnUserDeactivated` | `Auth::UserDeactivated` | Soft-delete all rows for the user. |
| `RestoreOnUserReactivated` | `Auth::UserReactivated` | Restore soft-deleted rows. |
| `NotifyAuthorsOnQuoteCreated` | `ChapterPassageQuoted` | Calls `StoryPublicApi` to resolve chapter author(s)/co-author(s), then calls `NotificationPublicApi::notify(...)` for each. |

Registered in `QuoteServiceProvider::boot()`.

### 3.7 Routes

`app/Domains/Quote/Private/routes.php`

| Method | Path | Middleware | Description |
|--------|------|------------|-------------|
| `GET` | `/quotes` | `auth`, `compliant` | Query param `chapter_id`. Viewer's own quotes for the chapter. |
| `POST` | `/quotes` | `auth`, `compliant`, `role:user-confirmed` | Create quote. |
| `PATCH` | `/quotes/{quoteId}/note` | `auth`, `compliant`, `role:user-confirmed` | Update note. |
| `DELETE` | `/quotes/{quoteId}` | `auth`, `compliant`, `role:user-confirmed` | Delete quote. |
| `GET` | `/profile/{username}/quotes` | *(public, auth optional)* | Paginated profile tab. Auth needed to enforce confirmed-user gate on public books. |

### 3.8 Controllers and form requests

- `QuoteController` — `index`, `store`, `updateNote`, `destroy`.
- `QuoteProfileController` — `show` (profile tab endpoint).
- `CreateQuoteRequest` — validates `chapter_id`, `highlighted_text` (required, ≤ 500 plain-text chars), `prefix`, `suffix`, `note` (optional, ≤ 1000 chars).
- `UpdateQuoteNoteRequest` — validates `note` (optional, ≤ 1000 chars).

## 4. Frontend architecture

### 4.1 Blade components (public)

#### `<x-quote::toolbar-button>`

A single Blade component exported by the Quote domain: the "Citation" button. No logic beyond rendering the button markup with an Alpine `@click` binding that the Quote JS bundle handles.

Story places this in the toolbar slot:

```blade
<x-comment::annotable entity-type="chapter" :entity-id="$chapter->id"
                      :can-annotate="$canAnnotate" :viewer-role="$viewerRole">
    <x-slot:toolbar-actions>
        <x-quote::toolbar-button />
        {{-- Annotations will add <x-comment::annotate-button> here in its own phase --}}
    </x-slot:toolbar-actions>
    {!! $chapter->content !!}
</x-comment::annotable>
```

#### `<x-quote::profile-tab>`

Renders the Citations tab content: paginated quote list, stale badges, note editing in-place, delete. Hardcoded into `profile/show.blade.php`.

### 4.2 Comment domain change: toolbar slot

`app/Domains/Comment/Private/Resources/views/components/annotable.blade.php` gains a `@slot('toolbar-actions')` inside the toolbar `<template>` element. The toolbar template structure:

```html
<template id="comment-toolbar-template">
    <div class="comment-toolbar" role="toolbar">
        {{ $toolbarActions ?? '' }}
    </div>
</template>
```

The slot is server-rendered into the template at page load; the toolbar JS (`app/Domains/Comment/Resources/js/annotable/toolbar.js`) clones the template and positions it near the selection. Each button in the slot carries its own Alpine `x-data` or `@click` binding handled by the domain that contributed it.

The generic toolbar JS (selection detection → toolbar show/hide/position) lives in the Comment domain: `app/Domains/Comment/Resources/js/annotable/toolbar.js`. It is loaded by `<x-comment::annotable>` via `@push('scripts')`.

### 4.3 Shared anchoring JS (new — Shared domain)

`app/Domains/Shared/Resources/js/anchoring/`

```
anchoring/
├── canonical-text.js   # buildCanonicalText(rootEl) → { text, nodeMap }
├── extract-anchor.js   # extractAnchor(range, rootEl, canonicalText) → { highlighted, prefix, suffix } | null
└── reanchor.js         # findAnchor(canonicalText, { prefix, highlighted, suffix }) → { status, start, end }
```

These are **pure functions** with no framework dependency. Both Quote and Annotations import them as ES modules. No additional Vite entry point is required — they are imported by the consuming bundles.

Spec for each function matches the Annotations architecture doc §4.3 exactly (that spec was written first; these functions are the implementation).

Full unit test coverage via Vitest lives alongside them in `*.test.js` files.

### 4.4 Quote JS layout

`app/Domains/Quote/Resources/js/`

```
quote/
├── index.js              # Registers Alpine components/stores, calls bootstrap()
├── api/
│   └── client.js         # fetch wrappers: create, updateNote, delete, getForChapter
├── stores/
│   └── quotes-store.js   # Alpine.store('quotes'): server-loaded list for current chapter
├── ui/
│   ├── toolbar-button.js # Citation button handler: open mini-form on click
│   ├── mini-form.js      # Note mini-form Alpine component (optional editor + Save/Cancel)
│   ├── tint.js           # Yellow tint + margin icon placement (uses reanchor.js)
│   └── popover.js        # Tint/icon click → popover (view note, edit note, delete)
└── profile/
    └── quote-list.js     # Profile tab Alpine component (pagination, edit, delete)
```

Vite entry point added in `vite.config.js`:
```js
'app/Domains/Quote/Resources/js/quote/index.js'
```

Loaded via `@once @push('scripts') @vite(...)` from `<x-quote::toolbar-button>` and `<x-quote::profile-tab>`.

### 4.5 Local storage

Quotes use **no local storage**. Each save is an immediate AJAX call. The only client-side state is the Alpine store populated from `GET /quotes?chapter_id=` on chapter open.

## 5. JS testing strategy

### 5.1 Infrastructure (established by this feature)

Quote is the first feature to require JS unit tests. The infrastructure it sets up is identical to what the Annotations plan calls "Phase 3":

- `package.json` devDependencies: `vitest`, `happy-dom`, `@testing-library/dom`, `@testing-library/user-event`.
- `vitest.config.js` at repo root: `environment: 'happy-dom'`, `include: ['app/Domains/**/Resources/js/**/*.test.js']`.
- `npm run test` alias for `vitest run`.
- Pre-commit hook addition: `npx vitest run` after deptrac.
- CI: `vitest run` step.

Annotations will inherit this without needing to set it up.

### 5.2 What to test

| Tier | Coverage | Priority |
|------|----------|----------|
| Shared anchoring functions | `canonical-text`, `extract-anchor`, `reanchor` — full unit tests with synthetic DOM fixtures | Critical |
| Quote stores | `quotes-store` reducer, round-trips | High |
| Quote UI components | Mini-form open/close/save/cancel, popover actions, profile tab pagination | Medium |
| Tint placement | Avatar/icon Y-position — **manual only** | Low |

## 6. File layout summary

```
app/Domains/Quote/
├── Database/
│   └── Migrations/
│       └── YYYY_..._create_quotes_table.php
├── Events/
│   └── ChapterPassageQuoted.php
├── Public/
│   ├── Api/
│   │   ├── QuotePublicApi.php
│   │   └── Contracts/
│   │       ├── QuoteDto.php
│   │       ├── CreateQuoteDto.php
│   │       └── QuoteListDto.php
│   ├── View/
│   │   └── Components/
│   │       ├── ToolbarButtonComponent.php
│   │       └── ProfileTabComponent.php
│   └── Providers/
│       └── QuoteServiceProvider.php
├── Private/
│   ├── Controllers/
│   │   ├── QuoteController.php
│   │   └── QuoteProfileController.php
│   ├── Listeners/
│   │   ├── NullifyUserOnUserDeleted.php
│   │   ├── SoftDeleteOnUserDeactivated.php
│   │   ├── RestoreOnUserReactivated.php
│   │   └── NotifyAuthorsOnQuoteCreated.php
│   ├── Models/
│   │   └── Quote.php
│   ├── Requests/
│   │   ├── CreateQuoteRequest.php
│   │   └── UpdateQuoteNoteRequest.php
│   ├── Resources/
│   │   ├── lang/
│   │   │   ├── fr/quotes.php
│   │   │   └── en/quotes.php
│   │   └── views/
│   │       └── components/
│   │           ├── toolbar-button.blade.php
│   │           ├── profile-tab.blade.php
│   │           └── partials/
│   │               ├── quote-mini-form.blade.php
│   │               ├── quote-popover.blade.php
│   │               └── quote-list-item.blade.php
│   ├── Services/
│   │   ├── QuoteService.php
│   │   └── QuotePolicy.php
│   ├── Support/
│   │   └── QuoteNoteSanitizer.php
│   └── routes.php
└── Resources/
    └── js/
        └── quote/ (per §4.4)

app/Domains/Shared/Resources/js/
└── anchoring/
    ├── canonical-text.js
    ├── canonical-text.test.js
    ├── extract-anchor.js
    ├── extract-anchor.test.js
    ├── reanchor.js
    └── reanchor.test.js

app/Domains/Comment/
├── Public/View/Components/
│   └── AnnotableComponent.php   (modified: toolbar-actions slot prop)
└── Private/Resources/views/components/
    ├── annotable.blade.php       (modified: toolbar-actions slot)
    └── ... (existing)
app/Domains/Comment/Resources/js/annotable/
└── toolbar.js                   (new: generic selection → toolbar show/hide/position)

app/Domains/Story/Private/Resources/views/chapters/
└── show.blade.php               (modified: <x-comment::annotable> + toolbar slot)

app/Domains/Profile/Private/Resources/views/
└── show.blade.php               (modified: hardcoded <x-quote::profile-tab>)

app/Domains/Settings/           (modified: register quote.book_public setting)
```

## 7. Decisions locked

| # | Decision | Choice |
|---|----------|--------|
| 1 | Domain | Standalone `Quote` domain |
| 2 | Anchor mechanism | Identical to Annotations spec §5; implemented in `Shared/Resources/js/anchoring/` |
| 3 | JS test infrastructure | Established by Quote; inherited by Annotations |
| 4 | `story_id` on quotes | Denormalised, no FK constraint (cross-domain) |
| 5 | Note sanitizer | Standalone `QuoteNoteSanitizer` in Quote domain; mirrors annotation profile; avoids cross-domain dep on Comment internals |
| 6 | Toolbar slot | `<x-comment::annotable>` gains `@slot('toolbar-actions')`; Story composes buttons from multiple domains |
| 7 | Generic toolbar JS | Owned by Comment domain (`annotable/toolbar.js`); handles selection detection and toolbar positioning only |
| 8 | Profile tab | Hardcoded `<x-quote::profile-tab>` in `profile/show.blade.php` (no registry in v1) |
| 9 | Save mechanism | Direct AJAX on Save / Ctrl+Cmd+Enter; no local storage |
| 10 | Cascade on chapter/story deletion | No cascade; missing reference detected at render time via `StoryPublicApi` |
| 11 | Notification | `ChapterPassageQuoted` event → `NotifyAuthorsOnQuoteCreated` → `NotificationPublicApi` |
| 12 | Note visibility | `note` field in `QuoteDto` is `null` for all viewers except the quote owner, enforced in `QuoteService::toDto()` |
| 13 | `canQuote` authorship check | Calls `StoryPublicApi::isAuthorOrCoAuthor(storyId, userId)` — story-level block (not just chapter-level) |

## 8. Risks acknowledged

- **`StoryPublicApi::isAuthorOrCoAuthor`**: verify this method exists or plan to add it during Phase 4. If it doesn't exist, Phase 4 adds it to the Story public API as a prerequisite.
- **Profile tab hardcoding**: as more domains add profile tabs, the Profile view will accumulate hardcoded references. Flagged as a known debt item; a `ProfileTabRegistry` should be designed when a third tab appears.
- **Toolbar slot and selection API on mobile**: touch selection behaviour varies. Manual testing on iOS Safari and Android Chrome is required during Phase 10.
- **`<x-comment::annotable>` ownership**: Quote contributes a change to Comment domain's component. Deptrac must be verified — Quote domain itself does not depend on Comment; it contributes via a PR that touches Comment. The Blade component change is in Comment, not in Quote.
