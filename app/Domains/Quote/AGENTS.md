# Quote — agent notes

- README: [app/Domains/Quote/README.md](README.md)

## Public API

- [QuotePublicApi](Public/Api/QuotePublicApi.php) — the only entry point other domains may call (Profile uses it for the tab + `canViewQuoteBook`). Do not reach into `Private/`. DTOs are under `Public/Api/Contracts/`.

## Events emitted

- `ChapterPassageQuoted` (event name `Quote.ChapterPassageQuoted`) — emitted by `QuoteService::create()` **only**, after the DB transaction commits. Note edits and deletions emit nothing. Payload carries `quoterId`, `chapterId`, `storyId`, `highlightedText` — never the note.

## Listens to (wired in QuoteServiceProvider)

- `Auth::UserDeleted` → nullify `user_id` on the user's quote rows (raw UPDATE, keeps the rows).
- `Auth::UserDeactivated` → soft-delete the user's quotes.
- `Auth::UserReactivated` → restore the soft-deleted quotes.
- `Quote::ChapterPassageQuoted` → `NotifyAuthorsOnQuoteCreated`, which notifies each chapter author/co-author (excluding the quoter) via `NotificationPublicApi`.

These are four separate listeners, not one handler — deactivation and deletion are intentionally different (recoverable soft-delete vs. permanent nullify).

## Registrations

- **Notification**: registers group `quote` and the `ChapterQuotedNotification` type (`quote.chapter_quoted`).
- **Settings**: registers the `hide-quotes-tab` boolean parameter (default `false`, i.e. the book is visible), but **inside `app->booted()`** — it must run after Profile has registered its tab/section, because it attaches to the Profile tab (`TAB_PROFILE`) / Privacy section. Read/write it with `SettingsPublicApi` using `QuoteServiceProvider::TAB_PROFILE` + `::KEY_HIDE_QUOTES_TAB`, never a hardcoded string. Note the polarity: **true means hidden**, matching `follow.hide-following-tab` and `profile.hide-comments-section`. It replaced an earlier opt-in `book_public` key (default hidden); the migration kept only the deliberate "keep it hidden" choices.

## Invariants (span multiple files — easy to break)

- **Note is owner-only.** `QuoteDto.note` must be `null` for every viewer except the quote owner. This is enforced in `QuoteService` when building DTOs (`getForProfile` / `buildProfileItems` pass `note` only when `isOwner`; `getForChapter` queries the owner's rows only). Any new read path must preserve this — the note must never appear in a non-owner response body.
- **Anchor fields are immutable.** `highlighted_text`, `prefix`, `suffix` are set at creation and never rewritten; re-anchoring is client-side and read-only against them. Only `note` is mutable after creation.
- **Profile visibility filtering happens before pagination for non-owners.** `getForProfile` loads all rows, filters out unavailable-chapter / inaccessible-story entries, then slices the page and reports the filtered total. Do not switch non-owners back to DB-level `paginate()` — it makes totals and pages wrong. Owner path keeps DB pagination (no filtering needed). Story access is resolved once per unique story, not per row.
- **`canQuote` takes a story ID, not a chapter ID**, and blocks authors/co-authors at the story level via `StoryPublicApi::isAuthorOrCoAuthor`.
- **No FK to `users`, `chapters`, or `stories`** — cross-domain by rule. `user_id` nullable; `story_id` denormalised. Never add a constraint; resolve references through `StoryPublicApi` and treat missing ones as "unavailable".

## Cross-domain UI contribution (not a PHP dependency)

- The "Citer" toolbar button renders inside Comment's `<x-comment::annotable>` `toolbar-actions` slot, placed by **Story's** `chapters/show.blade.php`. The generic selection toolbar and the `data-annotable` / `data-max-selection` contract live in the **Comment** domain (`annotable.blade.php` + `annotable/toolbar.js`) — the over-length "selection too long" gate and toolbar label are Comment-owned strings (`comment::annotable`). Quote domain PHP must **not** depend on Comment; the coupling is Blade-only, composed by Story.
- The profile "Citations" tab component is hardcoded into Profile's `show.blade.php`; `ProfilePrivate` depends on `QuotePublic` (see `deptrac.yaml`). There is no profile-tab registry in v1.
- Anchoring JS is imported from `app/Domains/Shared/Resources/js/anchoring/`; do not fork local copies — Annotations shares them.
