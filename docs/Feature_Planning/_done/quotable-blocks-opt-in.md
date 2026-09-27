# Quotable blocks opt in

**Status:** DONE — 2026-09-27 · **Domain(s):** `Quote`, `Shared`, `Comment`,
`Story`. Decision numbers (#n) refer to the task's `DECISIONS.md`, and
"arch." / "spec" to its `02-architecture.md` / `01-functional.md`. All three
are deleted; git history has them.

## What it does

Quote anchoring now reads only text that opts into quoting: `.ce-block--text`
areas inside the quote zone `article[data-quote-article]`. Image blocks and
their captions (and any future block type, e.g. chapter-choice) are neither
quotable nor part of the canonical text. Nothing is stored: the rule is derived
at display time from the block class Editor already writes. Simple-mode
chapters qualify because the read page wraps their content in one
`div.ce-block.ce-block--text`. Prerequisite of `multiedit-chapter-switch-block/`
(its decision #12).

## Key behaviour

- Roles/visibility unchanged: who can quote, see highlights, heat or summary is
  as before; only *which text* narrowed.
- Capture, reader highlights, author heat and author summary all call
  `buildCanonicalText(articleEl, { within: '.ce-block--text' })` from the same
  root `[data-quote-article]`. Capture used to read `.annotable-region`; it no
  longer does.
- « Citer » carries `data-requires-selection-within=".ce-block--text"`: Comment's
  toolbar hides it when the selection **covers** any non-blank text outside a
  text block. On the chapter page it is the only action, so the toolbar is not
  shown at all (no empty bubble).
- "Covers", not "touches": a boundary sitting on a block edge without covering
  its text is ignored (`Shared/…/anchoring/text-range.js`). A triple-click that
  ends at `(figure, 0)` quotes the paragraph. Toolbar check, `extractAnchor` and
  the mini-form's multi-block guard all use this same trimming (decision #11).
- Dragging across an image **with a caption** into the next text block hides
  « Citer » (caption covered). Across an image without caption, or across two
  adjacent text blocks, « Citer » shows and the mini-form gives the existing
  « plusieurs blocs » error. Spec §4.2.3 is worded as if the error always shows;
  the code hides the button whenever a caption is in the way.
- Existing Advanced quotes whose text/prefix/suffix ran through a caption may go
  stale; accepted, no audit (decision #4).
- Lifecycle: no data, no migration. Quotability follows the current content on
  every view.

## Where the code lives

| Concern | Path |
|---------|------|
| Quotable selector (JS) | `app/Domains/Quote/Resources/js/quote/ui/author-anchoring.js` — `QUOTABLE_AREA_SELECTOR` |
| Quotable selector (Blade) | `app/Domains/Quote/Private/Resources/views/components/toolbar-button.blade.php` — keep in sync with the JS constant |
| Quote consumers | `…/quote/ui/mini-form.js`, `chapter-highlights.js`, `author-heat.js`, `author-anchoring.js` (`resolveRows`) |
| Canonical text filter | `app/Domains/Shared/Resources/js/anchoring/canonical-text.js` — `within` option |
| Covered-text trimming | `app/Domains/Shared/Resources/js/anchoring/text-range.js` (new) — `coveredTextSlices`, `trimRangeToText`; used by `extract-anchor.js` |
| Toolbar applicability | `app/Domains/Comment/Resources/js/annotable/toolbar.js` — `selectionIsWithin`, `applyActionApplicability` (now exported, with `showToolbar`/`hideToolbar`) |
| Simple-mode wrapper | `app/Domains/Story/Private/Resources/views/chapters/show.blade.php`; flag `CurrentChapterViewModel::$isAdvanced` (`! empty(content_blocks)`) in `Story/Private/ViewModels/ChapterViewModel.php` |
| E2E seed | `Story/Database/Seeders/E2eStorySeeder.php` — illustrated chapter 7's image now has a caption |
| Tests (Vitest) | `Shared/…/anchoring/{canonical-text,extract-anchor,text-range}.test.js`, `Comment/…/annotable/toolbar.test.js` (new), `Quote/…/ui/{mini-form,author-anchoring (new),author-heat,author-summary}.test.js` |
| Tests (Pest) | `Quote/Tests/Feature/QuoteToolbarButtonViewTest.php`, `Story/Tests/Feature/Chapters/{ViewChapterTest,ChapterAdvancedModeTest}.php` |

## Extension points used / created

- **Created — Shared `buildCanonicalText(root, { within })`**: generic area
  filter; the caller owns the selector. Meant for `annotations/` too (which may
  include images).
- **Created — Comment toolbar `data-requires-selection-within`**: any
  `toolbar-actions` slot element (or a descendant) may declare it; Comment never
  knows the selector. Documented in `app/Domains/Comment/README.md`.
- No PHP registry, no deptrac edge (JS/DOM contract only; Comment JS imports
  Shared JS).

## Decisions worth remembering

- Hardcoded client-side from the stored class; no `data-quotable` attribute, no
  server HTML rewrite, no re-render, no backfill (#2, #7).
- Quotability fixed by block type, not a per-block author toggle (#3).
- Selector lives in Quote and is passed to generic Shared code (#8).
- Simple mode is wrapped rather than special-cased (#9). The moderation
  snapshot view is **not** wrapped (it does no quoting).
- `block-elements.js` (`closestBlock`) is unchanged: quotable areas are all
  `div.ce-block`, so the cross-block rule still works (arch. §7 #6).

## Code vs plan

- Arch. §8 "no new source file" is wrong: phase 5 (VERIFY fix, decision #11)
  added `Shared/…/anchoring/text-range.js`, and `selectionIsWithin` became
  `coveredTextSlices(...).every(...)` instead of the planned
  `TreeWalker` + `intersectsNode` early-exit.
- Arch. §9 says the toolbar walk "stops at the first non-matching node": it no
  longer does — `coveredTextSlices` walks every text node under the range's
  common ancestor (the whole article for a multi-block drag), then filters.
  Harmless at chapter sizes; revisit only if selection feels slow.
- Without `within`, `FIGURE`/`FIGCAPTION` still add no newline in the canonical
  text (assumption #6); irrelevant to Quote, which always filters.

## Not done

- Non-goals (spec §8): the chapter-choice block itself; per-block quotable
  toggle; audit of caption-spanning quotes; changing the cross-block error;
  quoting images/captions; per-block comment annotations.
- Rule written twice (Blade attribute + JS constant): emit it once from PHP if a
  second block type ever opts in.
- Nothing cut mid-build; no open question left. No new backlog row.
- E2E: `e2e/tests/features/quotable-blocks-opt-in.spec.ts` and its only user
  `e2e/pages/ChapterQuotes.ts` deleted — Quote-only, client-side behaviour
  covered by the Vitest suites above; not cross-app enough for `core/`.
