# Quotes — v1 Implementation Plan

Concrete, phase-by-phase plan for the v1 delivery.

- Functional spec: [`Quotes.md`](./Quotes.md)
- Architecture: [`Quotes_Architecture.md`](./Quotes_Architecture.md)

**Quote lands before Annotations.** Several phases here establish shared infrastructure that the Annotations implementation plan references as pre-existing dependencies. The cross-references are noted per phase.

## Working agreement

- One phase = one PR. Each phase ships independently, passes deptrac + the full test suite, and is revertable.
- Each phase has explicit **deliverables**, **tests**, and **acceptance criteria**. We don't move on until acceptance is met.
- Phase order is fixed unless we find a blocker; in that case we discuss before re-ordering.
- "Manual smoke" items in the acceptance lists are a hard requirement, not a nice-to-have.

## Phase index

| # | Phase | Estimated size | Dependencies | Shared infra for Annotations |
|---|-------|----------------|--------------|-------------------------------|
| 1 | JS test infrastructure | S | — | ✓ Annotations Phase 3 |
| 2 | Shared anchoring JS | M | 1 | ✓ Annotations Phase 9 (partial) |
| 3 | Schema + model | S | — | — |
| 4 | QuotePolicy + cascade listeners | S | 3 | — |
| 5 | QuoteService + QuotePublicApi | M | 3, 4 | — |
| 6 | Backend endpoints | M | 5 | — |
| 7 | Settings integration | S | — | — |
| 8 | Notification event + listener | S | 5 | — |
| 9 | `<x-comment::annotable>` toolbar slot | S | — | ✓ Annotations Phase 10 |
| 10 | Quote toolbar button + mini-form | M | 1, 2, 6, 9 | — |
| 11 | In-chapter visualization (tint + popover) | M | 10 | — |
| 12 | Profile tab | M | 5, 6, 7 | — |
| 13 | v1 polish (i18n, a11y, manual QA) | M | 11, 12 | — |

Total: 13 PRs. Sizes: S ≈ half a day, M ≈ 1–2 days.

---

## Phase 1 — JS test infrastructure

**Goal.** Stand up Vitest + happy-dom + testing-library. Wire into pre-commit and CI. This is the same infrastructure the Annotations plan calls "Phase 3" — delivered here first so Annotations inherits it.

**Deliverables.**
- `package.json` devDependencies: `vitest`, `happy-dom`, `@testing-library/dom`, `@testing-library/user-event`.
- `vitest.config.js` at repo root: `environment: 'happy-dom'`, `include: ['app/Domains/**/Resources/js/**/*.test.js']`.
- `npm run test` script (alias for `vitest run`).
- One trivial passing smoke test (e.g., a `1 + 1 === 2` assertion in a placeholder file) to confirm the setup works end-to-end.
- `scripts/husky-precommit.js`: append `npx vitest run` after deptrac, before PHP staged tests.
- CI: add a `vitest run` step alongside `composer deptrac` and the PHP test suite.

**Tests.**
- Self-validating: the infra runs the smoke test and it passes.

**Acceptance.**
- ✅ `npx vitest run` succeeds locally.
- ✅ Pre-commit hook invokes Vitest and blocks on failure.
- ✅ CI invokes Vitest as a required check.

---

## Phase 2 — Shared anchoring JS

**Goal.** Implement the three pure anchoring functions in `Shared/Resources/js/anchoring/`, fully unit-tested. These are the hardest JS in the whole feature — isolated here so they can be verified before any UI wiring.

This phase is the Quote-side delivery of what the Annotations plan calls "Phase 9 pure core" (minus the Annotations-specific `drafts-store`).

**Deliverables.**
- `app/Domains/Shared/Resources/js/anchoring/canonical-text.js`
  - `buildCanonicalText(rootEl)` → `{ text, nodeMap }`.
  - HTML tags stripped. Custom emoji blots `<span class="ql-custom-emoji-{name}">` replaced by `:{name}:`. Block boundaries (`</p>`, `</blockquote>`, etc.) contribute one space.
  - `nodeMap`: array of `{ start, end, domNode }` entries for converting text offsets back to DOM `Text` nodes.
- `app/Domains/Shared/Resources/js/anchoring/extract-anchor.js`
  - `extractAnchor(range, rootEl, canonicalText)` → `{ highlighted, prefix, suffix }` or `null` if the highlighted plain-text exceeds 500 chars.
  - Converts Range start/end to canonical-text offsets via `nodeMap`. Reads up to 5 words on each side.
- `app/Domains/Shared/Resources/js/anchoring/reanchor.js`
  - `findAnchor(canonicalText, { prefix, highlighted, suffix })` → `{ status: 'ok'|'moved'|'missing', start, end }`.
  - Implements the two-step algorithm: full triple match first; prefix+suffix pair fallback second; missing if neither or ambiguous.

**Tests (`*.test.js` alongside each module).**
- `canonical-text`: HTML stripped, emoji tokens correct, paragraph boundaries become spaces, `nodeMap` round-trips a known offset to the right `Text` node.
- `extract-anchor`: synthetic `Range` over a known DOM fixture → expected `{ prefix, highlighted, suffix }` for positions at chapter start, chapter end, mid-paragraph, and across a `<p>` boundary. Returns `null` on a 501-char selection.
- `reanchor`: `ok` on exact triple match; `moved` on prefix+suffix match with edited middle; `missing` on ambiguous second-step or no match.

**Acceptance.**
- ✅ Line coverage for these three files > 90%.
- ✅ All edge cases above explicitly have a named test.
- ✅ `npx vitest run` green.

---

## Phase 3 — Schema + model

**Goal.** `quotes` table, Eloquent model, scopes, basic model-level tests.

**Deliverables.**
- Migration `app/Domains/Quote/Database/Migrations/YYYY_MM_DD_HHMMSS_create_quotes_table.php` matching [Architecture §2.1](./Quotes_Architecture.md#21-quotes-table). Working `down()`.
- Indexes: `(user_id, deleted_at)`, `(chapter_id, user_id, deleted_at)`, `(story_id, deleted_at)`.
- Model `App\Domains\Quote\Private\Models\Quote` with `SoftDeletes`, Laravel 13 attribute syntax, no Eloquent relationships crossing into Story.

**Tests.**
- Create a quote row, read it back, assert all columns round-trip correctly.
- Soft-delete and restore.
- `deleted_at` scope filters soft-deleted rows by default.

**Acceptance.**
- ✅ Migration runs and rolls back cleanly.
- ✅ Model tests pass.
- ✅ Deptrac unaffected.

---

## Phase 4 — QuotePolicy + cascade listeners

**Goal.** Policy checks for who can quote; user-lifecycle cascade listeners.

**Deliverables.**
- `QuotePolicy`:
  - `canQuote(int $chapterId, int $userId): bool` — confirmed user, not an author/co-author of the chapter's story. Calls `StoryPublicApi::isAuthorOrCoAuthor(int $storyId, int $userId)`. **If this method does not exist on `StoryPublicApi`, add it in this phase** (small Story-side PR, separate commit or PR).
  - `canViewQuoteBook(int $profileUserId, int $viewerId): bool` — owner always, OR book public + viewer confirmed.
  - `canEditOrDelete(Quote $quote, int $userId): bool` — owner only.
- Listeners:
  - `NullifyUserOnUserDeleted`
  - `SoftDeleteOnUserDeactivated`
  - `RestoreOnUserReactivated`
- Registered in `QuoteServiceProvider::boot()`.

**Tests.**
- Unit tests for each `QuotePolicy` method across all role combinations (guest, unconfirmed, confirmed non-author, author/co-author).
- Integration tests: deactivate a user → their quotes are soft-deleted; reactivate → restored; delete → `user_id` nullified.

**Acceptance.**
- ✅ Policy tests cover every role × method combination.
- ✅ Cascade tests green.
- ✅ `StoryPublicApi::isAuthorOrCoAuthor` exists and is tested.

---

## Phase 5 — QuoteService + QuotePublicApi

**Goal.** Backend behaviour: create, update note, delete, fetch for chapter, fetch for profile.

**Deliverables.**
- `QuoteService` (private). Methods: `create`, `updateNote`, `delete`, `getForChapter`, `getForProfile`.
  - `create`: enforce highlight ≤ 500 plain-text chars, sanitize note via `QuoteNoteSanitizer`, persist, emit `ChapterPassageQuoted`.
  - `getForProfile`: batch-resolve story/chapter metadata via `StoryPublicApi`; filter out inaccessible entries for non-owner viewers.
- `QuoteNoteSanitizer` (private). HTMLPurifier profile: bold/italic/custom-emoji only.
- `QuotePublicApi` (public). Thin delegation layer over `QuoteService`.
- DTOs: `QuoteDto`, `CreateQuoteDto`, `QuoteListDto`.
  - `QuoteDto.note` is populated only when `viewerId === quote.userId`; `null` otherwise — enforced here.

**Tests.**
- Unit tests for `QuoteService` mocking the model and `StoryPublicApi`.
- Integration tests:
  - Confirmed non-author creates a quote → row present, event emitted.
  - Author of the chapter cannot quote → `canQuote` returns false, creation rejected.
  - Guest / unconfirmed user cannot quote.
  - `getForProfile` with a private quote book and a non-owner viewer → empty list.
  - `getForProfile` with a public quote book and a confirmed viewer → list visible, no notes in DTOs.
  - Quote from a soft-deleted chapter → `chapterAvailable: false` in the DTO.
- Sanitizer test: note with disallowed tags (headings, links) → tags stripped, bold/italic preserved.

**Acceptance.**
- ✅ All unit + integration tests green.
- ✅ Deptrac stays green.

---

## Phase 6 — Backend endpoints

**Goal.** HTTP routes + controllers wired to the public API. No UI yet.

**Deliverables.**
- Routes in `app/Domains/Quote/Private/routes.php` per [Architecture §3.7](./Quotes_Architecture.md#37-routes).
- `QuoteController`: `index` (GET /quotes), `store` (POST), `updateNote` (PATCH), `destroy` (DELETE).
- `QuoteProfileController`: `show` (GET /profile/{username}/quotes).
- Form requests: `CreateQuoteRequest`, `UpdateQuoteNoteRequest`.
- Middleware per architecture doc: `auth` + `compliant` for reads; + `role:user-confirmed` for writes.

**Tests (feature tests).**
- Guest cannot `GET /quotes` (auth middleware enforced).
- Unconfirmed user cannot `POST /quotes`.
- Author cannot quote their own chapter → 403.
- `POST /quotes` with valid payload → 201 + `QuoteDto` JSON.
- `PATCH /quotes/{id}/note` by non-owner → 403.
- `DELETE /quotes/{id}` by owner → 204, row soft-deleted.
- `GET /profile/{username}/quotes` with private book and non-owner → empty list (not 403).
- `GET /profile/{username}/quotes` with public book and confirmed viewer → list returned, no `note` field on items.

**Acceptance.**
- ✅ All feature tests green.
- ✅ Manual `curl` smoke for each endpoint.
- ✅ No regression on existing tests.

---

## Phase 7 — Settings integration

**Goal.** Add the `quote.book_public` toggle to the user settings page.

**Deliverables.**
- Quote domain registers a new boolean setting `quote.book_public` (default `false`) via the Settings extensibility mechanism. Follow the `add-setting` skill for the exact registration pattern.
- A toggle rendered in the appropriate settings section ("Confidentialité" or equivalent).
- The setting is read by `QuotePolicy::canViewQuoteBook` (already wired in Phase 4 — just confirm the key matches).

**Tests.**
- Toggle true → `canViewQuoteBook` returns true for confirmed viewer.
- Toggle false → `canViewQuoteBook` returns false for non-owner.

**Acceptance.**
- ✅ Toggle visible in the settings page.
- ✅ Changing it persists and is reflected immediately on the profile tab visibility.

---

## Phase 8 — Notification event + listener

**Goal.** Notify chapter authors when a reader quotes their work.

**Deliverables.**
- `ChapterPassageQuoted` event class in `app/Domains/Quote/Events/`. Payload: `quoterId`, `chapterId`, `storyId`, `highlightedText`.
- `NotifyAuthorsOnQuoteCreated` listener: resolves chapter authors via `StoryPublicApi`, calls `NotificationPublicApi::notify(...)` for each. Uses a new notification type `ChapterQuoteNotification` (or maps to an existing type — verify with the Notification domain's extension pattern).
- `QuoteService::create()` dispatches the event (already stubbed in Phase 5; fill it in here).

**Tests.**
- Integration test: create a quote → `ChapterQuoteNotification` dispatched for each author/co-author. Assert notification count and recipient IDs.
- No notification on note update or delete.
- No notification if the chapter has no author (edge case: deleted author → `user_id` null).

**Acceptance.**
- ✅ Notification appears in the author's notification inbox after a quote is created.
- ✅ Notification body shows the reader's name and the quoted passage (not the note).

---

## Phase 9 — `<x-comment::annotable>` toolbar slot

**Goal.** Add `@slot('toolbar-actions')` to the annotable component so Story can compose multiple toolbar buttons from different domains. This is the only phase that modifies the Comment domain.

This is the same component that Annotations Phase 10 will bootstrap — the slot is needed before the Quote button can be placed.

**Deliverables.**
- `app/Domains/Comment/Private/Resources/views/components/annotable.blade.php`: adds `@props(['toolbarActions' => ''])` and renders `{{ $toolbarActions }}` inside the toolbar `<template>`.
- `app/Domains/Comment/Public/View/Components/AnnotableComponent.php`: updated to declare the `toolbar-actions` slot prop.
- `app/Domains/Comment/Resources/js/annotable/toolbar.js` (new): generic selection detection → floating toolbar show/hide/position. No knowledge of specific buttons. Loaded by the annotable component via `@push('scripts') @once @vite(...)`.
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`: wraps chapter content in `<x-comment::annotable>` with the toolbar slot stub:
  ```blade
  <x-comment::annotable entity-type="chapter" :entity-id="$chapter->id"
                        :can-annotate="$canAnnotate" :viewer-role="$viewerRole">
      <x-slot:toolbar-actions>
          {{-- Quote button added in Phase 10; Annotation button added in Annotations phase --}}
      </x-slot:toolbar-actions>
      {!! $chapter->content !!}
  </x-comment::annotable>
  ```

**Tests.**
- Vitest DOM test: render minimal `[data-annotable]` fixture, simulate a text selection → toolbar `<div>` becomes visible.
- Simulate an empty selection or a whitespace-only selection → toolbar remains hidden.
- Manual smoke: open any chapter, select text → floating toolbar container appears (empty for now — button comes in Phase 10).

**Acceptance.**
- ✅ Toolbar container appears on selection; disappears on deselect.
- ✅ Existing chapter page passes manual smoke (nothing breaks visually).
- ✅ Deptrac unaffected.

---

## Phase 10 — Quote toolbar button + mini-form

**Goal.** "Citation" button in the toolbar → optional note mini-form → quote saved to server → success state.

**Deliverables.**
- `<x-quote::toolbar-button>` Blade component + `toolbar-button.js`. The button appears in the toolbar slot via Story's `show.blade.php`:
  ```blade
  <x-slot:toolbar-actions>
      <x-quote::toolbar-button />
  </x-slot:toolbar-actions>
  ```
- `mini-form.js` Alpine component: the note form that opens below the selection. Uses `<x-shared::editor :toolbar="['bold','italic','custom-emoji']">` (already refactored — Phase 1 of Annotations is done). No minimum length on the note.
- On **Save** (button or Ctrl/Cmd+Enter):
  1. Call `extractAnchor(range, rootEl, canonicalText)` from the shared anchoring module.
  2. `POST /quotes` with the anchor + note.
  3. On success: close form, push to `Alpine.store('quotes')`, trigger tint render (Phase 11 wires the visual; Phase 10 just updates the store).
  4. On failure: inline error, form stays open.
- On **Cancel**: close form, no request.
- Length guard: if `extractAnchor` returns `null` (selection > 500 chars), show tooltip "Sélection trop longue" and abort.

**Tests.**
- Vitest DOM tests via testing-library:
  - Simulate selection → Citation button renders in toolbar.
  - Click Citation → mini-form opens.
  - Type note + click Save → `POST /quotes` fired with correct payload; store updated.
  - Cancel → no request, form closed.
  - Overlong selection → Citation button disabled / tooltip shown.
- Manual smoke: highlight a short passage, click "Citation", type a note, save. Open devtools → confirm `POST /quotes` returns 201 and store has one entry.

**Acceptance.**
- ✅ Quotes persist across page reload (server-side).
- ✅ Form enforces 500-char highlight cap.
- ✅ No console errors.
- ✅ Mobile Safari touch selection — toolbar appears and "Citation" is tappable.

---

## Phase 11 — In-chapter visualization

**Goal.** Yellow tint on quoted passages; margin bookmark icon; popover with note, edit, delete.

**Deliverables.**
- `tint.js`: on chapter load, for each quote in `Alpine.store('quotes')`, call `findAnchor(canonicalText, quote)` and map the result back to DOM ranges via `nodeMap`. Apply a yellow background highlight to the matched range. Place a bookmark icon in the right margin at the line height of the quote end (md+ only).
  - `ok` / `moved` → tint applied.
  - `missing` → no tint; quote remains in the store but invisible in-chapter.
- `popover.js`: clicking tint or margin icon opens a small popover (`<x-shared::popover>`):
  - Shows quoted text (truncated with ellipsis).
  - Shows the private note (if any), labelled private.
  - **Edit note** action → opens note field in-place with the same editor, PATCH on save.
  - **Delete** action → DELETE request, remove tint + icon + store entry on success.
- Multiple quotes on the same passage: each gets its own tint layer (overlapping is accepted in v1). Each has its own margin icon stacked vertically.

**Tests.**
- Vitest DOM tests:
  - Fixture chapter HTML with a known passage → `tint.js` applies highlight to the right DOM range.
  - `missing` anchor → no tint applied.
  - Popover opens on icon click; Edit saves via PATCH; Delete fires DELETE and removes tint.
- Manual smoke: quote two passages on a chapter; reload; both tints appear. Edit a note; reload; updated note shows. Delete one; tint gone.

**Acceptance.**
- ✅ Tint is visually distinct from any existing annotation tint (color confirmed with designer/manual check).
- ✅ Margin icon does not appear on `< md` viewports.
- ✅ Tint still appears on `< md` viewports.
- ✅ No console errors.

---

## Phase 12 — Profile tab

**Goal.** "Citations" tab on the user's profile, paginated, with stale badges and in-place note editing.

**Deliverables.**
- `<x-quote::profile-tab>` Blade component + `quote-list.js` Alpine component.
- `QuoteProfileController::show` (already routed in Phase 6) serves paginated `QuoteListDto` JSON.
- Each list item (`partials/quote-list-item.blade.php`) displays:
  - Quoted text as a plain-text blockquote.
  - Story title (link). Chapter title (link, or "Chapitre non disponible" badge if `chapterAvailable: false`).
  - Author avatar(s) + display name(s).
  - Date added.
  - Private note (owner only) — absent from the rendered HTML for non-owners.
  - "Passage plus présent dans le chapitre" badge when `anchorMissing: true`.
- **Edit note** action (owner only): opens note in-place, same editor, PATCH on save.
- **Delete** action (owner only): DELETE request, row removed immediately.
- Hardcoded into `profile/show.blade.php` (no registry mechanism).
- The tab is hidden entirely for non-owner viewers when the book is private (`canViewQuoteBook === false`).

**Tests.**
- Feature tests:
  - Owner visits own profile → Citations tab visible, note shown on each item.
  - Confirmed non-owner visits a public profile → Citations tab visible, note absent from HTML.
  - Non-owner visits a private profile → Citations tab not rendered.
  - Entry with soft-deleted chapter → "Chapitre non disponible" badge shown, quoted text preserved.
  - Entry with missing anchor → "Passage plus présent" badge shown.
- Vitest DOM tests: pagination load-more, edit-note in-place save, delete removes row.

**Acceptance.**
- ✅ Private note never appears in the response body for non-owner requests (server-side enforcement verified in feature test).
- ✅ Inaccessible-chapter entries invisible to non-owners who lack chapter access.
- ✅ Pagination works (test with > 10 quotes).

---

## Phase 13 — v1 polish

**Goal.** Translate, make accessible, smoke-test broadly.

**Deliverables.**
- Lang files `fr/quotes.php` and `en/quotes.php`: toolbar button label, mini-form labels, popover labels, save/cancel, error messages, badge texts, profile tab title, notification content.
- Keyboard accessibility: focus trap on mini-form, Esc closes, tab order sane.
- Ctrl/Cmd+Enter saves the mini-form (already wired in Phase 10; confirm it works in both OS combos).
- Aria labels on the toolbar button, the mini-form editor, the popover close, the per-item action buttons.
- Manual QA checklist (below) filled.

**Acceptance.**
- ✅ Manual QA checklist 100%.
- ✅ Lighthouse a11y score on the chapter page does not regress.
- ✅ No untranslated strings remain.

---

## Manual QA checklist (filled during Phase 13)

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page (no quotes) | Loads as before; no extra network calls beyond the GET | |
| Create — basic | Highlight, Citation, type note, Save → tint appears, margin icon appears (md+) | |
| Create — no note | Click Citation, leave note empty, Save → quote saved, tint appears | |
| Create — highlight too long | Select > 500 chars → Citation button disabled or tooltip shown | |
| Create — cancel | Open form, Cancel → no quote saved, highlight dropped | |
| Create — network error | Force a 500 → inline error, form stays open | |
| Popover — view note | Click tint or margin icon → popover shows quoted text + note | |
| Popover — edit note | Edit note → PATCH fired → popover shows updated note | |
| Popover — delete | Delete → tint gone, margin icon gone, popover closed | |
| Reload persistence | After creating a quote, reload page → tint and icon still present | |
| Missing anchor | Author edits out the quoted passage → tint gone; profile tab shows badge | |
| Chapter unavailable | Chapter soft-deleted → profile tab entry shows "Chapitre non disponible" | |
| Profile tab — owner | Private book → tab visible; note shown; Edit + Delete present | |
| Profile tab — confirmed non-owner (public book) | Tab visible; note column absent from HTML | |
| Profile tab — confirmed non-owner (private book) | Tab not rendered | |
| Profile tab — guest | Tab not rendered even for public book | |
| Settings toggle | Toggle book to public → non-owner immediately sees tab on reload | |
| Notification | After quoting → chapter author receives notification with reader name + passage | |
| Auth — author quoting own story | Citation button absent or disabled | |
| Mobile (iOS Safari) | Touch selection → toolbar appears; tap Citation → mini-form opens; save works | |
| Mobile (Android Chrome) | Same | |
| Tablet (md–lg) | Tint visible; margin icon visible (inline, right after passage) | |
| Performance | 20 quotes on a chapter: page load + tint render both under 1 s | |

---

## Open items (none blocking v1)

- `StoryPublicApi::isAuthorOrCoAuthor`: confirm existence before Phase 4 starts. Add it if missing.
- `ChapterQuoteNotification` type: confirm the Notification domain extension pattern during Phase 8 to avoid surprises.
- Yellow tint shade: agree with designer before Phase 11.
