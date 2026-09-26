# Quotable blocks opt in — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared — `buildCanonicalText` gains a `within` area filter | S | — | DONE |
| 2 | Comment — per-action `data-requires-selection-within` on the selection toolbar | S | — | DONE |
| 3 | Story — wrap Simple-mode content in one `ce-block--text` on the read page | S | — | DONE |
| 4 | Quote — read and capture only `.ce-block--text` areas; « Citer » declares its area | M | 1, 2, 3 | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

Phases 1–3 are independent of each other and each changes **no visible
behaviour** on its own (1 and 2 add opt-in extension points nobody uses yet; 3
produces byte-identical canonical text). Phase 4 is the only one that switches
the behaviour on, and it must come last: filtering on `.ce-block--text` before
phase 3 has wrapped Simple-mode content would make every Simple chapter
unquotable and stale every existing quote.

Phases 1 and 2 are **shared infrastructure**: the future `annotations/` task is
expected to reuse both (a `within` selector that includes images; a toolbar
action with its own applicability).

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.
- Settled decisions are in [`DECISIONS.md`](./DECISIONS.md) (#1–#10). Do not
  re-open them.

---

## Phase 1 — Shared: `buildCanonicalText` gains a `within` area filter

**Goal.** `buildCanonicalText(rootEl, { within })` can restrict the canonical
text to the subtrees matching a CSS selector, without changing anything for
callers that omit it.

Architecture: §1.1 "Shared — canonical text gains an optional area filter",
§4, §6 (Vitest Shared). Spec §4.1.

**Deliverables.**
- `app/Domains/Shared/Resources/js/anchoring/canonical-text.js`
  - Signature becomes `buildCanonicalText(rootEl, { within } = {})`; update the
    JSDoc (`@param {{ within?: string }} [options]`).
  - `within` omitted / falsy → exact current behaviour (same code path).
  - `within` a selector → the walk tracks whether it is currently *inside* a
    matching element (an element strictly between `rootEl` and the node, or the
    node itself, matching `within`). Outside a match, the walk still descends
    (a match may be nested) but contributes **nothing**: no text, no `nodeMap`
    entry, no emoji `:name:`, no block newline. Inside a match, today's rules
    apply unchanged. Suggested shape: pass an `inside` boolean down `walk()`,
    set to `inside || node.matches(within)` for element nodes below `rootEl`.
  - The module must not name `ce-block--text` or any Quote notion — the
    selector is always supplied by the caller.
- `app/Domains/Shared/README.md` — update the `anchoring/canonical-text.js` row
  of the JS table to `buildCanonicalText(rootEl, { within? })` and add one
  sentence: `within` restricts the text to matching areas; consumers own the
  selector.

**Tests.** In `app/Domains/Shared/Resources/js/anchoring/canonical-text.test.js`
(new `describe('buildCanonicalText — within filter')`):
- `excludes a figure and its caption between two matching blocks` — fixture
  `<div class="ce-block ce-block--text"><p>avant</p></div><figure class="ce-block ce-block--image"><img><figcaption>légende</figcaption></figure><div class="ce-block ce-block--text"><p>après</p></div>`
  with `within: '.ce-block--text'` → text is exactly `'avant\naprès'`
  (one newline, no `légende`).
- `maps nodeMap offsets back to the matching text nodes` — every `nodeMap`
  entry's `domNode.textContent` equals `text.slice(start, end)`, and no entry's
  `domNode` sits inside the `figcaption`.
- `ignores whitespace text nodes between areas` — the stored `"\n"` between
  sibling blocks contributes nothing.
- `skips emoji blots outside the area and keeps them inside` —
  `ql-custom-emoji-*` span inside a caption is absent; inside a text block it
  still yields `:name:`.
- `returns empty text when nothing matches`.
- `is unchanged when within is omitted` — same fixture as the first test,
  called without options → equals the current output (caption included).
- `yields the same text for Simple content wrapped in one area` —
  `<p>a</p>\n<p>b</p>` vs `<div class="ce-block ce-block--text"><p>a</p>\n<p>b</p></div>`
  with `within` → both `text` values are identical (and equal the unwrapped
  call without `within`).
- All existing tests in the file stay green unmodified.

**Acceptance.**
- ✅ Every existing caller (`mini-form.js`, `chapter-highlights.js`,
  `author-heat.js`, `author-anchoring.js`) is untouched and its tests pass
  unmodified.
- ✅ With `within: '.ce-block--text'`, an image caption contributes no
  character and no `nodeMap` entry.
- ✅ `grep -n "ce-block--text" app/Domains/Shared/Resources/js/anchoring/canonical-text.js`
  returns nothing.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Comment: per-action applicability on the selection toolbar

**Goal.** A toolbar action carrying `data-requires-selection-within="<css>"` is
shown only when every non-whitespace text node touched by the selection lies
inside an element matching `<css>`; if no action is applicable the toolbar is
not shown at all.

Architecture: §1.1 "Comment — per-action applicability on the selection
toolbar", §4, §6 (Vitest Comment), §7 #4. Decisions #5, #10.

Context: the toolbar (`toolbar.js`) is a template rendered by
`<x-comment::annotable>`
(`app/Domains/Comment/Private/Resources/views/components/annotable.blade.php`),
cloned **once** into `document.body` as `#comment-toolbar-active`; actions are
the elements inside its `[data-toolbar-actions]` container, contributed by
other domains through the `toolbar-actions` slot. `showToolbar()` runs on
`mouseup` / `touchend` (via `setTimeout`). Nothing uses the new attribute yet —
Quote adopts it in a later phase.

**Deliverables.**
- `app/Domains/Comment/Resources/js/annotable/toolbar.js`
  - New exported pure helper
    `selectionIsWithin(range, selector): boolean` — walks the text nodes under
    `range.commonAncestorContainer` (a `TreeWalker` on `SHOW_TEXT`, or the node
    itself when it is a text node), keeps those with
    `range.intersectsNode(node)` and non-whitespace `textContent`, and returns
    `false` at the first one whose `parentElement.closest(selector)` is null;
    `true` otherwise.
  - New exported `applyActionApplicability(toolbar, range): number` — for each
    element child of `[data-toolbar-actions]`: if it (or, for wrapped slot
    content, its descendant) carries `data-requires-selection-within`, set
    `style.display = selectionIsWithin(range, sel) ? '' : 'none'`; elements
    without the attribute stay visible. Returns the number of visible actions.
    Use `style.display`, **not** the Tailwind `hidden` class: the Quote button
    carries `inline-flex`, and `hidden` vs `inline-flex` would depend on CSS
    order.
  - `showToolbar()`: after the region / `canAnnotate` / empty-text checks and
    `getOrCreateToolbar()`, call `applyActionApplicability(toolbar, range)`;
    if it returns `0`, `hideToolbar()` and return (no empty bubble). Only then
    evaluate the too-long state (`setTooLongState`) and position/show as today.
  - Export `showToolbar` and `hideToolbar` so the test can drive them directly
    (the module's `document.addEventListener` side effects stay as they are).
  - Update the header comment to document the attribute contract.
- `app/Domains/Comment/Resources/js/annotable/toolbar.test.js` (**new**, first
  test file for this module).
- `app/Domains/Comment/README.md` — document the `<x-comment::annotable>`
  toolbar extension point: `toolbar-actions` slot, and the optional
  `data-requires-selection-within` attribute (semantics above; "Comment never
  knows which domain declared it").

**Tests.** `app/Domains/Comment/Resources/js/annotable/toolbar.test.js`
(happy-dom; fixture = an `annotable-region` with `data-annotable`,
`data-can-annotate="true"`, `data-max-selection="500"`, a
`<template id="comment-toolbar-template">` holding two actions — one with
`data-requires-selection-within=".ok"`, one without — and content
`<div class="ok"><p>a</p></div><figure><figcaption>c</figcaption></figure><div class="ok"><p>b</p></div>`;
reset `document.body` and remove `#comment-toolbar-active` in `beforeEach`):
- `selectionIsWithin — true for a range inside one matching area`
- `selectionIsWithin — true for a range spanning two matching areas` (the
  whitespace-only text nodes between them are ignored)
- `selectionIsWithin — false when the range is wholly inside a caption`
- `selectionIsWithin — false when the range runs from a matching area into a caption`
- `showToolbar — shows the declaring action for a selection inside a matching area`
- `showToolbar — hides the declaring action but keeps the plain action when the selection touches a caption`
- `showToolbar — does not show the toolbar when no action is applicable` (fixture
  with only the declaring action, caption selection → `#comment-toolbar-active`
  has `display: none`)
- `showToolbar — an action without the attribute is always visible` (template
  with only a plain action, caption selection → toolbar shown)
- `showToolbar — re-shows a previously hidden action on the next applicable selection`
  (the clone is reused across selections)
- `showToolbar — too-long state is applied only when an action is applicable`

**Acceptance.**
- ✅ No existing page changes behaviour: no Blade in the repo carries
  `data-requires-selection-within` yet, so every action stays applicable.
- ✅ A selection touching a non-matching text node hides the declaring action;
  when it was the only action, the toolbar is not displayed.
- ✅ `toolbar.js` names no Quote class or selector.
- ✅ `pnpm run gate` green (deptrac unchanged — JS/DOM contract only).

---

## Phase 3 — Story: wrap Simple-mode content in one `ce-block--text`

**Goal.** On the chapter read page, Simple-mode content (no `content_blocks`)
is rendered inside exactly one `<div class="ce-block ce-block--text">` within
`article[data-quote-article]`; Advanced content is output untouched.

Architecture: §0, §1.1 "Story — Simple-mode wrapper (display time)", §7 #2.
Decisions #2, #9. The stored `content` is never modified. This phase changes no
visible behaviour: the architecture verified the CSS
(`.rich-content > .ce-block:last-child p:last-of-type`) and that the canonical
text is byte-identical (the wrapper `DIV` only adds a trailing newline, which
`buildCanonicalText` trims).

**Deliverables.**
- `app/Domains/Story/Private/ViewModels/ChapterViewModel.php` —
  `CurrentChapterViewModel` gains `public readonly bool $isAdvanced`, set in
  `from()` to `! empty($chapter->content_blocks)`.
- `app/Domains/Story/Private/Resources/views/chapters/show.blade.php` (around
  line 220, inside `<article data-quote-article …>`):
  `@if ($vm->chapter->isAdvanced) {!! $vm->chapter->content !!} @else <div class="ce-block ce-block--text">{!! $vm->chapter->content !!}</div> @endif`.
  Add a Blade comment: the quote system treats `.ce-block--text` as the only
  quotable area, so Simple content is wrapped to obey the same rule.
  The moderation snapshot view is **not** touched.
- `app/Domains/Story/Database/Seeders/E2eStorySeeder.php` —
  `createIllustratedChapter()`: give the image block a `'caption'`
  (e.g. `'Légende de l\'illustration E2E'`) so VERIFY can select a caption.
  No other seed changes.
- `app/Domains/Story/README.md` — extend the paragraph on the single
  `<article data-quote-article>` root (currently ~line 73): its children are
  always `.ce-block` wrappers — Advanced content already is, Simple content is
  wrapped in one `ce-block--text` at display time — and only `.ce-block--text`
  areas are read by the quote system.

**Tests.**
- `app/Domains/Story/Tests/Feature/Chapters/ViewChapterTest.php` —
  `it('wraps simple-mode content in exactly one quotable text block')`: a
  published Simple chapter (`content = '<p>Un</p><p>Deux</p>'`) viewed by a
  confirmed reader → between `data-quote-article` and `</article>`,
  `substr_count(…, 'ce-block--text') === 1`, and the wrapper is followed by the
  stored content; the DB `content` column is unchanged after the request.
- `app/Domains/Story/Tests/Feature/Chapters/ChapterAdvancedModeTest.php` —
  `it('does not wrap advanced content a second time')`: reuse the
  text / image / text fixture of `prints advanced content in a single quote root`
  → `substr_count($article, 'ce-block--text') === 2` (one per text block, none
  added) and `ce-block--image` still present.

**Acceptance.**
- ✅ A Simple chapter's read page has exactly one `ce-block--text` inside
  `data-quote-article`; an Advanced chapter has as many as it has text blocks.
- ✅ `chapters.content` is byte-identical before and after viewing.
- ✅ Existing Quote/Story feature tests (`AuthorHeatViewTest`, `ViewChapterTest`,
  `ChapterAdvancedModeTest`) pass unchanged.
- ✅ `pnpm run gate` green.

---

## Phase 4 — Quote: only `.ce-block--text` is quotable

**Goal.** Capture, reader highlights, author heat and author summary all build
their canonical text from `[data-quote-article]` filtered to `.ce-block--text`,
and « Citer » declares that area so the toolbar hides it on any selection
touching a non-quotable area.

Architecture: §0, §1.1 "Quote — the consumer", §4 (consistency requirement),
§6 (Vitest Quote, Feature Quote), §7 #1, #3, #5, #6. Decisions #5–#10.

What earlier phases left behind:
- `buildCanonicalText(rootEl, { within })` (Shared,
  `anchoring/canonical-text.js`) only reads subtrees matching `within`.
- The Comment toolbar hides any action carrying
  `data-requires-selection-within="<css>"` when the selection touches a text
  node outside `<css>`, and hides itself when no action remains.
- On the chapter read page, `article[data-quote-article]` only ever contains
  `.ce-block` children: Simple content is wrapped in one
  `div.ce-block.ce-block--text`; Advanced text blocks are
  `div.ce-block.ce-block--text`, images `figure.ce-block.ce-block--image`
  (caption inside).

**Deliverables.**
- `app/Domains/Quote/Resources/js/quote/ui/author-anchoring.js`
  - `export const QUOTABLE_AREA_SELECTOR = '.ce-block--text';` with a comment:
    the rule is also written in
    `app/Domains/Quote/Private/Resources/views/components/toolbar-button.blade.php`
    (`data-requires-selection-within`) — keep both in sync.
    (Placed here rather than in a new module because it is the Quote anchoring
    module that `author-heat.js` already imports; architecture §8 plans no new
    source file.)
  - `resolveRows()` (line ~42): `buildCanonicalText(articleEl, { within: QUOTABLE_AREA_SELECTOR })`.
- `app/Domains/Quote/Resources/js/quote/ui/author-heat.js` (line ~85) — same
  `within`, importing the constant from `./author-anchoring.js`.
- `app/Domains/Quote/Resources/js/quote/ui/chapter-highlights.js` (line ~36) —
  same `within`, importing the constant.
- `app/Domains/Quote/Resources/js/quote/ui/mini-form.js`
  - Resolve the root with `closest('[data-quote-article]')` instead of
    `closest('.annotable-region')` (both branches, lines ~25–27); rename
    `region` → `articleEl`.
  - `buildCanonicalText(articleEl, { within: QUOTABLE_AREA_SELECTOR })` and pass
    `articleEl` to `extractAnchor`.
  - Cross-block check (`closestBlock`) unchanged — a selection spanning two text
    blocks still opens the form with the « plusieurs blocs » error (#6).
- `app/Domains/Quote/Private/Resources/views/components/toolbar-button.blade.php`
  — add `data-requires-selection-within=".ce-block--text"` on the `<button>`,
  with a Blade comment pointing at `QUOTABLE_AREA_SELECTOR` in
  `author-anchoring.js`.
- Existing Vitest fixtures that the filter would empty — update them to the
  real page shape (`<article data-quote-article>` root, content inside
  `div.ce-block.ce-block--text`), without changing what each test asserts:
  - `app/Domains/Quote/Resources/js/quote/ui/mini-form.test.js` — fixtures use
    `<div class="annotable-region">` with bare `<p>`s. Root becomes
    `<article data-quote-article>`; bare-paragraph fixtures get one
    `ce-block ce-block--text` wrapper (= Simple mode). The test
    `accepts a selection spanning two paragraphs outside any editor block`
    becomes `… inside the Simple-mode wrapper` (same assertions). Multi-block
    tests keep two `ce-block--text` wrappers.
  - `app/Domains/Quote/Resources/js/quote/ui/author-heat.test.js` — fixtures use
    `<div class="ce-block">`; add `ce-block--text`.
  - `app/Domains/Quote/Resources/js/quote/ui/author-summary.test.js` — same
    (`div.ce-block` → `div.ce-block.ce-block--text`).
- `app/Domains/Quote/README.md` (and `app/Domains/Quote/AGENTS.md` if it
  describes the canonical text root) — state the rule: only `.ce-block--text`
  inside `[data-quote-article]` is quotable; the selector lives in
  `QUOTABLE_AREA_SELECTOR` and on the toolbar button; image blocks and captions
  are not quotable; existing quotes whose text/prefix/suffix ran through a
  caption may go stale (accepted).

**Tests.**
- `mini-form.test.js`, new `describe('quoteMiniForm.openForm — quotable areas')`:
  - `resolves the root from [data-quote-article], not .annotable-region` —
    article nested in an `.annotable-region` that also holds text outside the
    article; the anchor's prefix/suffix never contain that outer text.
  - `does not open for a selection wholly inside an image caption` —
    text / `figure.ce-block--image > figcaption` / text fixture → `open === false`.
  - `builds prefix and suffix that skip the caption` — selection at the end of
    the first text block → `anchor.suffix` starts with the second block's text,
    not the caption's.
  - `still flags a selection spanning two text blocks as multi-block` (existing
    behaviour, on the illustrated fixture).
- `app/Domains/Quote/Resources/js/quote/ui/author-anchoring.test.js` (**new**) —
  `re-anchors a quote captured by the mini-form on an illustrated chapter`:
  capture via `quoteMiniForm.openForm` on a text / image-with-caption / text
  fixture, then `resolveRows([{…anchor}], articleEl)` on the same DOM → the row
  resolves to the same text node range (proves capture and re-anchoring agree).
- `author-anchoring.test.js` — `does not resolve a stored quote whose highlighted text only exists in a caption`
  (`resolveRows` leaves it unresolved/stale; `chapter-highlights.js` and
  `author-heat.js` share the same filtered canonical text, so this covers
  them).
- Feature — `app/Domains/Quote/Tests/Feature/QuoteToolbarButtonViewTest.php`
  (new; same helpers as `AuthorHeatViewTest.php`: `alice`, `bob`,
  `publicStory`, `createPublishedChapter`):
  - `it('declares the quotable area on the Citer button for a confirmed reader')`
    → page contains `data-requires-selection-within=".ce-block--text"`.
  - `it('renders no Citer button for a guest')` → attribute absent (guards that
    the declaration did not leak outside `@if ($canQuote)`).

**Acceptance.**
- ✅ `grep -rn "buildCanonicalText(" app/Domains/Quote/Resources/js --include=*.js | grep -v test`
  shows every call passing `{ within: QUOTABLE_AREA_SELECTOR }`.
- ✅ `grep -rn "annotable-region" app/Domains/Quote/Resources/js` returns only
  test fixtures, if any.
- ✅ A selection inside an image caption yields no anchor in the mini-form; the
  « Citer » button carries `data-requires-selection-within=".ce-block--text"`.
- ✅ Simple-mode fixtures produce the same canonical text as before this phase
  (no existing Simple-mode quote goes stale).
- ✅ A confirmed reader sees the attribute; a guest sees no button.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh. E2E seed: Simple chapter id 3 (heat
scenario quotes), illustrated Advanced chapter id 7 (text / image **with
caption, added in phase 3** / text; quotes `de l'italique et du gras` and
`qui suit l'image`). Re-seed after phase 3 so the caption exists.

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter 7, confirmed reader, desktop | Select a few words inside the first text block → toolbar with « Citer »; the mini-form opens and saves | |
| Chapter 7, confirmed reader, desktop | Select text wholly inside the image caption → no toolbar at all (no empty bubble) | |
| Chapter 7, confirmed reader, desktop | Drag from the end of the first text block into the caption → no « Citer » | |
| Chapter 7, confirmed reader, desktop | Drag from the first text block across the image into the second → « Citer » shows; mini-form shows the « plusieurs blocs » error | |
| Chapter 7, confirmed reader, desktop | Triple-click the last paragraph before the image → note whether « Citer » shows and, if it does, that the form opens (not a dead button) | |
| Chapter 7, confirmed reader | Seeded quotes `de l'italique et du gras` and `qui suit l'image` still highlight in place | |
| Chapter 7, author | Heat tint and summary list both seeded quotes; nothing tinted on the image/caption | |
| Chapter 3 (Simple), confirmed reader | Seeded highlights still render at the same places; quoting a new passage works | |
| Chapter 3 (Simple), author | Heat map, gutter markers, badge count and summary (incl. the stale row) unchanged from before the feature | |
| Chapter 3 (Simple), any viewer | Layout unchanged: text indent, paragraph spacing, last-paragraph padding (compare with `main`) | |
| Chapter 3 / 7, guest and non-confirmed user | No « Citer », no toolbar — unchanged | |
| Chapter 7, mobile (touch) | Long-press selection in a text block → toolbar below the selection with « Citer »; long-press on the caption → no toolbar | |

## Open items

- **Phase 2** — `selectionIsWithin` relies on `range.intersectsNode`, which
  counts a text node the range merely *touches* at offset 0 (e.g. a
  triple-click whose end lands at `(captionText, 0)`). That hides « Citer »
  even though no caption character is selected. It is the conservative choice
  (consistent with the filtered canonical text, where such a range may not
  extract an anchor), so the plan keeps it; VERIFY's triple-click row checks
  whether it is noticeable. Not blocking.
- **Phase 2** — whether a slot action may be wrapped (attribute on a
  descendant of the `[data-toolbar-actions]` child rather than the child
  itself). Today `<x-quote::toolbar-button>` renders the `<button>` directly;
  the plan handles both, BUILD may simplify to direct children if it confirms
  no wrapper exists.
- *Resolved during PLAN.* Quote Vitest files are exactly `mini-form.test.js`
  (bare `<p>` in `.annotable-region`), `author-heat.test.js` and
  `author-summary.test.js` (both `div.ce-block` without `--text`). There is no
  `chapter-highlights` test and no `author-anchoring.test.js`: phase 4 creates
  `author-anchoring.test.js` and puts the caption-not-tinted test there via
  `resolveRows`.
