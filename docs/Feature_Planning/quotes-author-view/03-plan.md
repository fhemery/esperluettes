# Quotes — in-chapter author view (vNext) — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads one phase at a time.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

> **UNBLOCKED 2026-08-01.** Both prerequisites of decision #21 have landed:
> `../chapters-multi-edit/` and `../story-author-check/` are wrapped.
>
> **DOM re-validation of phases 5–8, done 2026-08-01.** An advanced chapter now
> renders `div.ce-block.ce-block--text > p` (and `figure.media-image.ce-block`)
> inside the still-single `[data-quote-article]` root. Verdicts:
>
> - **Phase 5 is unaffected — build it as written.** The canonical offset space
>   is still one flat string over the whole article, and `DIV` is already in
>   `BLOCK_TAGS` (`Shared/Resources/js/anchoring/canonical-text.js`), so a
>   `ce-block` contributes the same single boundary space a `<p>` already did.
>   Per-block anchoring was explicitly a non-goal of `chapters-multi-edit/`.
> - **Phase 6, tint wrapping — valid but must be explicit.** `segmentByDepth()`
>   returns *canonical* ranges; they must be re-split per `nodeMap` entry exactly
>   as `chapter-highlights.js::_applyHighlight` does today. One segment yields N
>   marks across N `ce-block` divs. Never one `Range.surroundContents()` per
>   segment.
> - **Phase 6, margin markers — needs adjusting.** Advanced chapters contain
>   lazily-loaded `<img>` in the article, so line positions move long after
>   `document.fonts.ready`. Risk 4's mitigation is promoted to a deliverable: a
>   `ResizeObserver` on the article plus recompute on image `load`, not just
>   fonts + resize.
> - **Risk 2 was misdiagnosed and is now rewritten.** v1 already wraps per text
>   node, so no segment crosses an element boundary — italics/bold cause no gap.
>   The real seam is the synthetic boundary space the canonical text inserts
>   between blocks: it maps to no text node, so a passage spanning two blocks
>   shows a 1-char untinted hole per boundary. Pre-existing at `<p>` boundaries,
>   but more frequent with `ce-block` and more visible under a depth-graded tint
>   than under the flat yellow. **Resolved by decisions #22/#23: multi-block
>   quotes are prevented at capture time (new phase 5b), so phase 6 carries no
>   seam-handling logic and no cross-block quote can exist.**
> - Two facts phases 6–8 must account for: `<figure>`/`<figcaption>` are *not* in
>   `BLOCK_TAGS`, so a caption sits inside the offset space and glues to the next
>   block's first word; and reordering blocks shifts every offset, which is the
>   accepted silent detachment (`chapters-multi-edit/` decision #4) — those rows
>   land in phase 8's stale list.

**No migration, no schema change, no `deptrac.yaml` change.** If a phase seems to
need one, the phase is wrong — stop and report instead of adding it.

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Story — `getStoryIdByChapterId()` | S | — | DONE |
| 2 | Lifecycle — delete quotes on account deletion | S | — | DONE |
| 3 | Read path — DTOs, service, public API | M | — | DONE |
| 4 | Endpoint — policy, route, controller | M | 1, 3 | DONE |
| 5 | Pure JS — `groupPassages()` / `segmentByDepth()` | S | — | DONE |
| 5b | Reader — reject multi-block selections at capture | S | — | DONE |
| 6 | UI — store, badge, heat toggle, tint & markers | M | 4, 5, 5b | DONE |
| 7 | UI — passage popover (reader list) | M | 6 | DONE |
| 8 | UI — chapter summary popup & focus flow | M | 6, 7 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 1, 2, 3 and 5 are mutually independent and could be built in any order or
in parallel. Phase 5 can be pulled forward freely — it touches no PHP.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- French only, no literal strings in Blade: every new label lands in
  `app/Domains/Quote/Private/Resources/lang/fr/ui.php` in the phase that renders
  it. Counted strings use `trans_choice`.

---

## Phase 1 — Story: `getStoryIdByChapterId()`

**Goal.** Let another domain resolve a chapter's story id, so Quote can
authorise the aggregate endpoint from a `chapter_id` alone (decision #18).

**Deliverables.**
- `app/Domains/Story/Public/Api/StoryPublicApi.php` — new method
  `getStoryIdByChapterId(int $chapterId): ?int`, delegating to the existing
  chapter service/model rather than querying from the API class.
- Whatever private Story service method that delegation needs (read-only, one
  indexed lookup on `chapters.id`).

**Tests.**
- `app/Domains/Story/Tests/Feature/StoryPublicApiTest.php` (or the existing
  equivalent): `it('returns the story id of an existing chapter')`,
  `it('returns null for an unknown chapter id')`.

**Acceptance.**
- ✅ `StoryPublicApi::getStoryIdByChapterId($id)` returns the owning story id for
  a real chapter and `null` for an id that does not exist.
- ✅ No Story behaviour changes; no new table, column or route.
- ✅ `npm run gate` green.

---

## Phase 2 — Lifecycle: delete quotes on account deletion

**Goal.** Replace the v1 nullify-and-keep rule with an outright delete, so an
orphan quote row can never exist (decision #5).

**Deliverables.**
- `app/Domains/Quote/Private/Listeners/DeleteQuotesOnUserDeleted.php` — renamed
  from `NullifyUserOnUserDeleted.php` (rename, do not keep both). Performs a raw
  `DB::table('quotes')->where('user_id', $userId)->delete()`, deliberately
  bypassing the soft-delete scope so rows already soft-deleted by a prior
  deactivation go too.
- `app/Domains/Quote/Public/Providers/QuoteServiceProvider.php` — the
  `UserDeleted` subscription points at the renamed listener.
- `app/Domains/Quote/AGENTS.md` — the invariant "`user_id` nullable; nullified
  not deleted" is rewritten to state that quotes are hard-deleted with their
  owner, and that no orphan row exists.

**Tests.**
- `app/Domains/Quote/Tests/Feature/QuoteLifecycleTest.php`:
  - the existing `it('nullifies user_id when the owner is deleted')` becomes
    `it('deletes the quotes when the owner is deleted')` — `assertDatabaseMissing`
    on the row, replacing the `user_id => null` assertion.
  - new `it('also deletes quotes already soft-deleted by a prior deactivation')`
    — deactivate, then delete, then assert no row at all remains.
  - the deactivate (soft-delete) and reactivate (restore) tests are untouched and
    must still pass.

**Acceptance.**
- ✅ After `Auth::UserDeleted`, `quotes` contains no row for that user, including
  soft-deleted ones.
- ✅ Deactivation still soft-deletes and reactivation still restores.
- ✅ No class named `NullifyUserOnUserDeleted` remains in the codebase.
- ✅ `npm run gate` green.

---

## Phase 3 — Read path: DTOs, service, public API

**Goal.** Expose the chapter's quote count and its note-free aggregate rows
through `QuotePublicApi`, with no HTTP surface yet.

**Deliverables.**
- `app/Domains/Quote/Public/Api/Contracts/AggregateQuoteDto.php` — readonly
  `id`, `highlightedText`, `prefix`, `suffix`, `createdAt`, `quoter`.
  **It has no `note` property, in any form.** `QuoteDto` is left untouched.
- `app/Domains/Quote/Public/Api/Contracts/ChapterAggregateDto.php` — `items`
  (`AggregateQuoteDto[]`), `totalCount`.
- `app/Domains/Quote/Private/Services/QuoteService.php`:
  - `countForChapter(int $chapterId): int` — one `COUNT`, excludes soft-deleted,
    no authorisation (the caller is already behind the policy).
  - `getChapterAggregate(int $chapterId): ChapterAggregateDto` — selects only the
    columns of §2.1 (**never the `note` column**), ordered `created_at DESC`,
    resolves quoter profiles in **one** batched
    `ProfilePublicApi::getPublicProfiles()` call, skips defensively any row whose
    profile cannot be resolved.
- `app/Domains/Quote/Public/Api/QuotePublicApi.php` — `countForChapter()` and
  `getChapterAggregate()` delegating to the service.

**Tests.**
- `app/Domains/Quote/Tests/Feature/ChapterAggregateTest.php` (API-level half):
  - `it('counts only live quotes of the chapter')` — excludes soft-deleted rows
    and rows of other chapters.
  - `it('returns rows newest first with the quoter profile resolved')`.
  - `it('returns an empty aggregate for a chapter with no quotes')`.
  - `it('omits the quotes of a deactivated reader')` and
    `it('includes them again once the reader is reactivated')`.
  - `it('never exposes a note on the aggregate dto')` — reflection assertion that
    `AggregateQuoteDto` declares no `note` property, so the guarantee cannot be
    re-introduced by a later edit.
- Assert with a chapter carrying several quoters that only **one**
  `getPublicProfiles()` call is made (spy/fake on the contract) — no N+1.

**Acceptance.**
- ✅ `AggregateQuoteDto` has no `note` property and the SQL never selects the
  `note` column.
- ✅ A chapter with quotes from three readers resolves profiles in one batched
  call.
- ✅ Soft-deleted quotes are absent from both the count and the rows.
- ✅ `npm run gate` green.

---

## Phase 4 — Endpoint: policy, route, controller

**Goal.** Serve the aggregate over HTTP to the chapter's authors only, with the
story resolved server-side from the chapter.

**Deliverables.**
- `app/Domains/Quote/Private/Services/QuotePolicy.php` —
  `canViewChapterAggregate(int $chapterId, int $userId): bool`: resolves the
  story via `StoryPublicApi::getStoryIdByChapterId()` (phase 1), returns `false`
  when it is `null`, otherwise checks membership of
  `StoryPublicApi::getAuthorIds($storyId)`.
  **Not `isAuthorOrCoAuthor()`** — it delegates to `getCollaboratorIds()` and so
  returns true for beta readers (decision #19). `getAuthorIds()` filters to
  `role = 'author'`, which covers the author and any co-author. This is a
  deliberate divergence from `canQuote()`.
- The chapter's story is **never** read from the request. `chapter_id` is the
  endpoint's only parameter; no `story_id` exists to forge.
- `app/Domains/Quote/Public/Api/QuotePublicApi.php` —
  `canViewChapterAggregate(int $chapterId, int $userId): bool`.
- `app/Domains/Quote/Private/Controllers/ChapterAggregateController.php` — a
  single `show()`: validates the lone `chapter_id` query parameter inline (as
  `QuoteController::index` already does), authorises via `QuotePublicApi`,
  aborts 403 otherwise, and serialises the DTO **field by field** — no
  `toArray()` on a model, no automatic DTO dump.
- `app/Domains/Quote/Private/routes.php` — one line added to the **existing**
  `['web','auth','compliant','role:user-confirmed']` group (decision #17):
  `Route::get('/chapter-aggregate', [ChapterAggregateController::class, 'show'])->name('chapter-aggregate');`
  No new route group. No `PATCH`.

**Tests.** `app/Domains/Quote/Tests/Feature/ChapterAggregateTest.php`, the full
matrix of §6, each hitting the real route with a real user of a real role:
- `it('redirects a guest')`
- `it('forbids a confirmed reader who is not an author')` — 403
- `it('forbids a moderator')` / `it('forbids an admin')` — 403 (decision #4)
- `it('allows the author')` — 200
- `it('allows a co-author')` — 200 (assumption A1); the co-author is a second
  collaborator with `role = 'author'`
- `it('forbids a beta reader of the story')` — 403 (decision #19); this is the
  test that pins the `getAuthorIds()` choice, so a later switch back to
  `isAuthorOrCoAuthor()` fails loudly
- `it('forbids an author demoted to non-confirmed user')` — 403, answered by the
  role middleware before the policy (decision #17)
- `it('forbids a chapter belonging to another authors story')` — 403; the story
  is never taken from the client
- `it('forbids an unknown chapter id')` — 403 via a `null` story id
- `it('rejects a missing or non-numeric chapter_id')` — 422
- `it('returns no note key anywhere in the response body')` — asserted on the
  **raw JSON string / decoded array of the response**, not on the DTO, for a
  chapter whose quotes all carry a non-empty note.

**Acceptance.**
- ✅ Every row of the §6 table above is a passing test.
- ✅ The raw JSON response body contains the substring `note` nowhere.
- ✅ A beta reader of the story gets 403; `QuotePolicy::canViewChapterAggregate`
  contains no call to `isAuthorOrCoAuthor()`.
- ✅ The controller reads `chapter_id` and nothing else from the request; no
  `story_id` is accepted, and a request carrying one is unaffected by it.
- ✅ The route sits inside the pre-existing confirmed-only group; `routes.php`
  gained exactly one line and no new `Route::middleware(...)` block.
- ✅ `npm run gate` green.

---

## Phase 5 — Pure JS: `groupPassages()` and `segmentByDepth()`

**Goal.** Land the two pure aggregation functions with full vitest coverage,
before any UI consumes them.

**Deliverables.**
- `app/Domains/Quote/Resources/js/quote/ui/author-summary.js` — exports
  `groupPassages(rows)` (group by normalised `highlighted_text`: trimmed,
  whitespace collapsed; one entry per passage with its count, its rows and its
  readers; ordered by count descending) and `segmentByDepth(ranges)` (split at
  every boundary of the `{start,end}` ranges, return non-overlapping segments
  each carrying a `depth`).
- No Alpine, no DOM, no import of the store — these two functions stay pure so
  they remain unit-testable.

**Tests.** `app/Domains/Quote/Resources/js/quote/ui/author-summary.test.js`
(picked up by the existing vitest glob `app/Domains/**/Resources/js/**/*.test.js`):
- `groupPassages`: exact-text grouping; whitespace/case-insensitive-trim
  normalisation; count aggregation for two readers on the same passage; ordering
  by count descending; a passage with no resolved range sorts after the live ones
  (assumption A8); empty input.
- `segmentByDepth`: disjoint ranges (all depth 1); fully nested ranges; partial
  overlap (three segments, depths 1/2/1); identical ranges (one segment, depth 2);
  adjacent boundaries producing no zero-width segment; empty input.

**Acceptance.**
- ✅ Both functions are pure — the test file imports nothing but the module.
- ✅ The partial-overlap case yields exactly three segments with depths 1, 2, 1.
- ✅ `npm run gate` green (vitest included).

---

## Phase 5b — Reader: reject multi-block selections at capture

**Goal.** Make a cross-block quote impossible to create, so the author heat never
has to render one (decisions #22/#23). This is a **reader-side** change: it
touches the existing quote capture flow, not the author view.

**Deliverables.**
- `app/Domains/Shared/Resources/js/anchoring/` — export a block test rather than
  reusing the module-private `BLOCK_TAGS` set of `canonical-text.js` verbatim.
  That set contains `DIV`, so a decorative inner `div` would falsely split a
  selection; the exported helper must treat as a block only what actually is one
  (`P`, `BLOCKQUOTE`, `H1`–`H6`, `LI`, `PRE`, and `div.ce-block`).
- `app/Domains/Quote/Resources/js/quote/ui/mini-form.js` — in `openForm()`, after
  the region is resolved, compare the block ancestor of `range.startContainer`
  with that of `range.endContainer`. When they differ, set the error the same way
  the too-long case does at `:55-57` — a distinct flag beside `tooLong`, with
  `save()` early-returning on it.
- `app/Domains/Quote/Private/Resources/views/components/mini-form.blade.php` — a
  `data-error-*` attribute beside the existing `data-error-highlight-too-long`.
  The inline error paragraph and the disabled save button are **reused as they
  are**; no new error UI.
- `app/Domains/Quote/Private/Resources/lang/fr/ui.php` — one key under
  `errors.`, sibling of `highlight_too_long`.

**Not in this phase.** No server-side validation: `CreateQuoteRequest` never sees
the chapter HTML and `StoryChapterDto` carries no `content`, so enforcing this on
the server would mean a new Story accessor plus DOM parsing — disproportionate
for a guard whose failure mode is a 1-char cosmetic gap. This is a UX guard, not
an invariant, and the plan says so on purpose.

**Tests.** Vitest, beside the existing anchoring tests:
- the exported block helper: returns true for `p`/`li`/`div.ce-block`, false for a
  plain decorative `div` and for inline `em`/`strong`.
- a selection inside one paragraph is accepted; one spanning two paragraphs is
  rejected; one spanning two `div.ce-block` wrappers is rejected; one spanning
  `<em>` inside a single paragraph is **accepted** (this is the case risk 2
  wrongly blamed).

**Acceptance.**
- ✅ Selecting across two paragraphs shows the inline error and leaves save
  disabled; no quote is created.
- ✅ Selecting across italics or bold **within** one paragraph still saves.
- ✅ No new error component — the existing inline paragraph carries the message.
- ✅ The reader's own highlight and note flow are otherwise unchanged.
- ✅ `npm run gate` green.

---

## Phase 6 — UI: store, badge, heat toggle, tint and markers

**Goal.** An author opening their own chapter sees the « n citations » badge and
can toggle a depth-graded tint plus `md+` margin markers over the text.

**Deliverables.**
- `app/Domains/Quote/Resources/js/quote/api/client.js` — `getChapterAggregate(chapterId)`.
- `app/Domains/Quote/Resources/js/quote/stores/aggregate-store.js` — the
  `quoteAggregate` store: `rows`, `totalCount` (seeded server-side), `loaded`,
  `visible` (**starts `false` on every load, never persisted** — decision #14),
  `ensureLoaded(chapterId)` (fetch once, on first need), `toggle(chapterId)`.
- `app/Domains/Quote/Resources/js/quote/ui/author-heat.js` — the Alpine
  component: re-anchors rows with the existing
  `Shared/Resources/js/anchoring/` helpers, feeds the resolved ranges to
  `segmentByDepth()`, wraps each segment exactly once, and positions the markers
  in a gutter container that is `hidden md:block` (so below `md` the markers are
  never built, not merely hidden). Recompute positions on resize.
- `app/Domains/Quote/Resources/js/quote/index.js` — register store and component.
- `app/Domains/Quote/Private/View/Components/AuthorBadge.php` +
  `Resources/views/components/author-badge.blade.php` — renders **nothing**
  unless `QuotePublicApi::canViewChapterAggregate()` passes (**not** guarded on
  `$vm->isAuthor`, so the component and the endpoint can never disagree);
  otherwise the badge seeded with `countForChapter()` and the toggle icon beside
  it (decision #11). At zero it reads « 0 citation » and is inert (A7).
  It composes `<x-shared::popover>` / `<x-shared::badge>` directly rather than
  `<x-shared::metric-badge>`, whose panel is text-only (`label` + `tooltip`) and
  cannot host the phase-8 summary list — but it must reuse `metric-badge`'s
  `size` and `color` defaults so the metric row stays visually homogeneous.
- `app/Domains/Quote/Private/Resources/views/components/author-heat.blade.php` —
  the Alpine root wrapping the article region.
- `app/Domains/Quote/Private/Resources/lang/fr/ui.php` — badge label/tooltip
  (`trans_choice`), toggle aria-label, marker aria-label naming the count.
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` — **two
  Blade lines only, no PHP**: `<x-quote::author-badge>` in the existing metric
  row beside the reads and word-count badges, and `<x-quote::author-heat>`
  around the existing `<article data-quote-article>` region.

**Tests.**
- Feature: `it('renders the citations badge with the chapter count for the author')`
  and `it('renders no badge for a confirmed reader, a beta reader, a moderator
  or a guest')` — hitting `GET` on the chapter page and asserting on the HTML.
- Feature: `it('renders 0 citation on a chapter with no quotes')`.
- Vitest, `stores/aggregate-store.test.js`:
  `it('starts hidden and never writes to localStorage')`,
  `it('fetches the aggregate only once across repeated toggles')`.

**Acceptance.**
- ✅ A confirmed reader, a beta reader of the story, a moderator, an admin and a
  guest get chapter HTML containing no badge, no toggle and no heat root — the
  components gate on the policy, not on `$vm->isAuthor`.
- ✅ The toggle is off after a reload, on every chapter; no `localStorage` key
  and no setting is written.
- ✅ The rows are fetched lazily, once, on first toggle — not on page load.
- ✅ Below `md` the marker gutter contains no marker element.
- ✅ `npm run gate` green.

---

## Phase 7 — UI: passage popover (reader list)

**Goal.** Clicking or keyboard-activating a tint or a marker lists the readers
who quoted that passage — never a note.

**Deliverables.**
- `app/Domains/Quote/Private/Resources/views/components/author-passage-panel.blade.php`
  — a popover, **separate from `<x-quote::chapter-panel>`** (which stays the
  reader's own note/edit/delete panel): heading « Cité par n lecteur·s »
  (`trans_choice`), then one entry per quote covering the clicked point —
  avatar, display name, relative date — ordered newest first (A4), each name
  linking to the reader's profile page. Anchored below the passage on mobile,
  as the reader popover already is.
- `ui/author-heat.js` — open the panel from a tint or a marker; the tint and the
  marker are focusable, keyboard-activatable and carry an aria-label naming the
  count.
- `Resources/lang/fr/ui.php` — popover heading and the tint aria-label.

**Tests.**
- Vitest, `ui/author-heat.test.js`: `it('lists every quote covering the clicked point')`
  when several ranges overlap (§4.4.7); `it('opens the panel on Enter from a focused tint')`.
- The privacy guarantee is already proven at phase 4 (raw-JSON assertion); no
  template condition is added here — the panel simply has no note to render.

**Acceptance.**
- ✅ Clicking a point covered by three overlapping quotes lists all three.
- ✅ The panel template contains no reference to a note field.
- ✅ Tint and marker are reachable by keyboard and announce their count.
- ✅ `npm run gate` green.

---

## Phase 8 — UI: chapter summary popup and focus flow

**Goal.** Clicking the badge opens the chapter summary; clicking a live row
turns the heat on, scrolls to the passage and opens its popover.

**Deliverables.**
- The badge's `<x-shared::popover>` panel: one row per passage from
  `groupPassages()`, with its count, ordered by count descending; stale passages
  (rows whose `findAnchor()` returned missing) listed **below** the live ones,
  badged with the **existing** « Passage plus présent dans le chapitre » string —
  no second wording.
- `stores/aggregate-store.js` — `focus(groupKey)`: close the popup, set
  `visible = true` if it was off, scroll to the passage, open its popover
  (decision #11).
- Stale rows are rendered inert — not a button, not focusable as an action, no
  click handler.
- The summary works with the heat off, and opening it triggers
  `ensureLoaded()`.
- `Resources/lang/fr/ui.php` — summary heading.
- `app/Domains/Quote/README.md` — the author view added to the domain's scope.

**Tests.**
- Vitest, `ui/author-summary.test.js` (extended): `it('lists stale passages last with the stale badge')`,
  `it('turns the heat on when focusing a live row')`,
  `it('exposes no action on a stale row')`.

**Acceptance.**
- ✅ Two readers quoting the same passage produce one row with a count of 2.
- ✅ A stale passage is counted by the badge, absent from the heat, and present
  and badged in the summary.
- ✅ Clicking a live row with the heat off leaves the heat on and the passage
  popover open.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. Covers what §6 of the architecture lists as VERIFY-only
(Alpine does not run in PHPUnit), plus one row per role and per state named in
§5 of the functional spec.

| Surface | Check | OK? |
|---------|-------|-----|
| Author — chapter header | The « n citations » badge sits in the metric row beside reads and word count, indistinguishable from its `metric-badge` neighbours in size, colour and baseline, and reads the same number as the database | |
| Author — chapter header, empty state | On a chapter with no quotes the badge reads « 0 citation » and nothing happens on click; no empty-state message appears | |
| Author — toggle | Off on load; turning it on tints the text, turning it off restores the page exactly as it was; still off after a reload of the same chapter | |
| Author — heat legibility | Depths 1, 2 and 3+ are distinguishable from one another and from the surrounding prose, and the text stays readable | |
| Author — heat in dark mode | Same, in dark mode | |
| Author — heat on formatted prose | A passage spanning italics/bold shows no visible gap in the tint (risk #2) | |
| Author — margin markers `md+` | Markers are vertically aligned with the line of their passage, do not drift after fonts and images load, and reflow correctly on window resize | |
| Author — mobile (`< md`) | No margin marker at all; the tint alone is tappable and opens the popover, anchored below the passage | |
| Author — passage popover | Shows the count, then avatar, display name and relative date per reader, newest first; **no note anywhere**; names link to the reader's profile page | |
| Author — overlapping quotes | Clicking text covered by several quotes lists every one of them | |
| Author — chapter summary | Opens from the badge with the heat off; one row per passage with its count, ordered by count descending | |
| Author — summary, stale row | Stale passages appear below the live ones with « Passage plus présent dans le chapitre », and are visibly inert — no hover affordance, no cursor change, nothing happens on click | |
| Author — summary, focus flow | Clicking a live row with the heat off closes the popup, turns the heat on, scrolls the passage into view and opens its popover | |
| Author — keyboard | Badge, toggle, summary rows, tints and markers are reachable by Tab and activate with Enter/Space; each announces its count | |
| Co-author | A co-author of the story sees exactly the same badge, toggle, heat and popover as the author | |
| Confirmed reader | On the same chapter, sees only their own quote, own yellow tint and own note; no badge, no toggle, no marker, no aggregate tint | |
| Beta reader | A beta reader of the story sees the chapter exactly as an ordinary reader does — no badge, no toggle, no heat (decision #19) | |
| Moderator / admin | Sees exactly what an ordinary reader sees — no badge, no toggle, no heat | |
| Guest | Chapter page unchanged from today; no badge, no toggle, no extra element in the prose | |
| Lifecycle — deactivated reader | After a quoter deactivates, their quote leaves the heat, the counts and the popover list | |
| Lifecycle — reactivated reader | After reactivation it is back in all three | |
| Lifecycle — deleted reader | After a quoter deletes their account, their quote is gone permanently; no orphan entry is rendered anywhere and the badge count has dropped | |
| Lifecycle — edited chapter | Editing a quoted passage away leaves it untinted and unmarked, still counted by the badge, and explained in the summary as stale | |
| Author — unpublished chapter | The view still works on the author's own unpublished chapter | |

## Open items

1. **`ChapterQuotedNotification` and the reader pipeline are untouched** — worth
   a regression pass at phase 6/7: the reader's `quoteHighlighter` root and the
   author heat root live side by side in `chapters/show.blade.php`, and although
   an author cannot quote their own story (so they never coexist for one user),
   the Blade must still be valid for both audiences.

Resolved during PLAN, kept here as the reason two phases read the way they do:

- **Which authorship predicate** — `isAuthorOrCoAuthor()` returns true for beta
  readers; the policy uses `getAuthorIds()` instead (decision #19, architecture
  §3.3). Pinned by a test in phase 4.
- **Which shared badge component** — `<x-shared::metric-badge>`'s panel is
  text-only and cannot host the summary, so `author-badge` composes
  `<x-shared::popover>` / `<x-shared::badge>` while matching `metric-badge`'s
  `size` and `color` defaults (architecture §4.3). Phase 6.

Verified during PLAN, no longer open: `StoryPublicApi::getAuthorIds()`,
`ProfilePublicApi::getPublicProfiles()`, `<x-shared::popover>`, the anchoring
helpers in `app/Domains/Shared/Resources/js/anchoring/`, the existing
confirmed-only route group in `app/Domains/Quote/Private/routes.php`, the
`NullifyUserOnUserDeleted` registration in `QuoteServiceProvider`, and the vitest
include glob `app/Domains/**/Resources/js/**/*.test.js` (which already covers
`app/Domains/Quote/Resources/js/`).
