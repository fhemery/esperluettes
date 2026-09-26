# Quotable blocks opt in — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 0. The rule in one line

**A quotable area is a `.ce-block--text` element inside the quote zone
(`[data-quote-article]`). Nothing else is quotable.** Simple-mode content is
wrapped in one `div.ce-block.ce-block--text` at display time, so both modes
obey the same rule (#7, #9).

Why this holds without storing anything: the block wrapper class is already in
the stored HTML and is derived from the block's `type` —
`ContentBlocksRenderer` emits `ce-block--text` for `type: text`,
`ce-block--image` (on a `figure.media-image`, caption inside) for
`type: image`, and the planned chapter-choice block follows the same
convention (`ce-block--chapter-choice`). "Default not quotable" is simply
"any class other than `ce-block--text`". Satisfies spec §4.1.5 (display-time,
no backfill, decision #2).

## 1. Domain placement

**Quote** owns the rule (which selector is quotable). The mechanics it relies
on are generic and live where they already are: canonical text in **Shared**
anchoring, the selection toolbar in **Comment**, the quote zone markup in
**Story**. No new domain, no PHP class, no table.

### 1.1 Changes in other domains

**Story — Simple-mode wrapper (display time).** On the chapter read page, when
the chapter has no `content_blocks`, the content inside `[data-quote-article]`
is wrapped in `<div class="ce-block ce-block--text">…</div>`. Advanced content
is output untouched (it already consists of `ce-block` wrappers). The stored
`content` is not changed. Verified harmless:

- CSS: `.rich-content > .ce-block:last-child p:last-of-type` gives the same
  final-paragraph padding as `.rich-content > p:last-of-type` did;
- canonical text: the wrapper `DIV` only appends a trailing `\n`, which
  `buildCanonicalText` trims — Simple-mode canonical text is byte-identical to
  today, so no Simple-mode quote goes stale.

The moderation snapshot view has no quote machinery and is left alone.

**Shared — canonical text gains an optional area filter.**
`buildCanonicalText(rootEl, { within } = {})`:

- `within` omitted → behaviour identical to today (existing callers and tests
  unaffected);
- `within` a CSS selector → only text nodes that have an ancestor matching
  `within` (up to `rootEl`) contribute to `text` and `nodeMap`; other subtrees
  are skipped entirely (no text, no block newline). Emoji blots follow the same
  rule.

Shared stays feature-agnostic: it never names `ce-block--text`. The future
`annotations/` task can pass a different selector that includes images
(#8).

`block-elements.js` is **unchanged**: its `div.ce-block` predicate already
coincides with the quotable areas (all `ce-block--text` are `DIV`s), and the
figcaption hole it had (a selection wholly inside a caption passed the
same-block check) is closed upstream by the toolbar (below) and downstream by
the filtered canonical text (caption nodes are absent from `nodeMap`, so
`extractAnchor` returns `null` and the form does not open).

**Comment — per-action applicability on the selection toolbar (new generic
extension point, declarative).** Any element in the `toolbar-actions` slot may
carry:

```
data-requires-selection-within="<css selector>"
```

On each toolbar show, `toolbar.js` evaluates every action that declares it: the
action is applicable iff **every non-whitespace text node intersecting the
selection range** has an ancestor matching the selector. Non-applicable
actions get hidden; if no action remains visible, the toolbar is not shown at
all (no empty bubble). Actions without the attribute are always applicable
(today's behaviour). The "too long" state is evaluated only when at least one
action is applicable. Comment learns nothing about Quote (#10).

**Quote — the consumer.**

- One JS constant `QUOTABLE_AREA_SELECTOR = '.ce-block--text'`, passed as
  `within` to every `buildCanonicalText` call in Quote (capture, reader
  highlights, author heat, author anchoring/summary).
- The toolbar button declares
  `data-requires-selection-within=".ce-block--text"`. The Blade literal and the
  JS constant are the two places the rule is written; both carry a comment
  pointing at the other.
- Capture converges on the quote zone: `mini-form.js` resolves its root with
  `closest('[data-quote-article]')` instead of `.annotable-region`, like every
  other consumer (spec §9, second open question).
- Cross-block behaviour unchanged (#6): a selection spanning two text blocks
  passes the toolbar predicate (all its text is quotable) and still gets the
  « plusieurs blocs » error in the mini-form.

## 2. Data model

None. No table, column, migration or backfill (#2).

### 2.3 Lifecycle rules

None. Quotability is recomputed on each page load from the current HTML
(spec §5).

## 3. PHP architecture

No new PHP class, public API, service, policy, event, route or form request.
The only server-side change is the Simple-mode wrapper in the Story read view.
Server-side quote creation does not validate anchors against chapter content
today and does not start to (anchoring is client-side by design).

## 4. Frontend architecture

| Concern | Owner | Contract |
|---------|-------|----------|
| Quote zone | Story view | `article[data-quote-article]`; content always made of `.ce-block` children |
| Quotable rule | Quote JS + Quote Blade | `.ce-block--text` |
| Canonical text | Shared `anchoring/canonical-text.js` | `buildCanonicalText(root, { within? })` |
| Block boundary for cross-block check | Shared `anchoring/block-elements.js` | unchanged (`div.ce-block`) |
| Action applicability | Comment `annotable/toolbar.js` | `data-requires-selection-within` on slot actions |

Consistency requirement: capture, re-anchoring, reader highlights, author heat
and author summary all build the canonical text from `[data-quote-article]`
with `within: '.ce-block--text'` — the same function, same root, same filter —
so a quote captured today re-anchors identically everywhere (spec §4.3.1).

No new French string: a non-applicable action is hidden, not explained.

## 5. Deptrac

No new edge. Nothing on the PHP side crosses a boundary: the Story view already
renders `x-comment::annotable` and `x-quote::…` components (Blade is invisible
to deptrac), and the JS contracts are DOM attributes and a Shared module Quote
already imports.

## 6. Testing strategy

- **Vitest (Shared)** — `buildCanonicalText` with `within`: image figure and
  caption excluded; text blocks either side of an image joined by exactly one
  newline; `nodeMap` offsets still map back to the right text nodes; without
  `within`, output unchanged; a Simple-mode content wrapped in one
  `ce-block--text` yields the same text as the unwrapped content.
- **Vitest (Comment)** — `toolbar.js` (currently untested): action with the
  attribute shown for a selection inside a matching area, hidden when the range
  touches a caption partially or wholly, toolbar hidden when no action is
  applicable, action without the attribute unaffected, whitespace-only nodes
  between two areas ignored.
- **Vitest (Quote)** — `mini-form`: root is `[data-quote-article]`; a caption
  selection yields no anchor; existing multi-block cases still pass; anchors
  captured on an illustrated fixture re-anchor through `author-anchoring` with
  the same filter.
- **Feature (Story)** — read page: Simple-mode chapter content sits in exactly
  one `ce-block--text` inside `data-quote-article`; Advanced chapter is not
  double-wrapped; stored `content` untouched.
- **Feature (Quote)** — the toolbar button carries
  `data-requires-selection-within=".ce-block--text"` for a confirmed reader.
- **VERIFY (browser, E2E seed)** — illustrated chapter (id 7): selecting a
  caption, or text running into a caption → no « Citer »; selecting inside a
  text block → « Citer » works; across two text blocks → existing error.
  Simple chapter (id 3): existing seeded quotes still highlight and show in the
  author heat map/summary; layout unchanged. Mobile touch selection same.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where is "not quotable" decided at display time? | **A client-side from the stored block class** · B server-side HTML rewrite per view (`EditorPublicApi`, DOM parse) · C re-render from `content_blocks` per view | A, simplified: hardcode `.ce-block--text` as the only quotable area, no `data-quotable` attribute | Class already carries the block type; zero server cost; no risk of the PHP parser altering the HTML and staling quotes; C contradicts the save-time rendering of `multiedit-chapter-switch-block` (#7) |
| 2 | How does Simple mode fit the rule? | special case "no `.ce-block` → whole zone" · **wrap Simple content in `div.ce-block.ce-block--text` at display** | Wrap | One rule for both modes; CSS and canonical text verified unchanged (#9) |
| 3 | Where does the quotable selector live? | hardcoded in Shared anchoring · **Quote constant passed as `within` to a generic Shared function** | Quote constant | Future annotations will want images too; Shared stays generic (#8) |
| 4 | How is « Citer » hidden? | **A declarative `data-requires-selection-within` on the action, evaluated by Comment's toolbar** · B Quote button hides itself via Alpine | A | Only option without an empty toolbar bubble; generic for future actions; no deptrac edge (#10) |
| 5 | Capture root | `.annotable-region` · **`[data-quote-article]`** | `[data-quote-article]` | Every other consumer already uses it; one root guarantees capture and re-anchoring agree |
| 6 | Block predicate | extend to `figure` / quotable-aware · **unchanged** | Unchanged | Quotable areas are all `div.ce-block`; the caption hole is closed by the toolbar and the filtered canonical text |

## 8. File layout

No new PHP class. New test file only:

```
app/Domains/Comment/Resources/js/annotable/
└── toolbar.test.js        (new)
```

## 9. Risks acknowledged

- **Existing Advanced-mode quotes near an image may go stale**: removing
  caption text changes canonical text around images, so a prefix/suffix that
  ran through a caption no longer matches. Accepted (#4); surfaces through the
  existing stale-passage handling. Revisit if authors report a wave of stale
  quotes on illustrated chapters.
- **The rule is written twice** (Blade attribute, JS constant). Revisit if a
  second block type ever opts in — then emit the selector once from PHP.
- **Toolbar predicate cost**: walks the text nodes of the selection on each
  mouseup; bounded by the 500-char selection cap in practice (the walk stops at
  the first non-matching node).
- **Third-party block types** would need the `ce-block--text` class to be
  quotable; none exist and none are planned to be quotable (chapter-choice is
  deliberately not).
