# Fix chapter table of contents alignment — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions: [`DECISIONS.md`](./DECISIONS.md)

**Read Open items before starting phase 1.** The literal class list recorded in
`02-architecture.md` §3 (`h-full items-center`) does not produce vertical
centering on the containers it names, and one of the two containers needs a
second change the architecture does not mention. Both are recorded below with
the reasoning; neither changes the *intent* locked at REFINE ("add vertical
centering classes to chapter info containers to match button alignment",
`01-functional.md` §7 #2).

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Author chapter list — vertically centre the chapter info cell | S | — | DONE |
| 2 | Reader chapter list — vertically centre the chapter info cell | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

The two phases are independent — neither reads nor moves the other's file, and
either can ship or be reverted alone. They are split by view rather than bundled
because the reader view needs an extra change the author view does not, and
because each view is a separate visual surface to check.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- No new automated test: this is a CSS-only change to Blade markup.
  `02-architecture.md` §6 records that decision — asserting Tailwind class
  strings in a feature test is brittle and would pin the very markup the next
  layout change must move. Proof of the fix is the Visual QA checklist below,
  executed at VERIFY. What each phase *does* owe is that the existing Story
  feature tests that render these views stay green.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Author chapter list, vertically centre the chapter info cell

**Goal.** Make the chapter title in the author's table of contents sit on the
same vertical centre line as the metric badges and the edit/delete buttons in
the same grid row.

**Context.** `02-architecture.md` §3 and §4.1 are the only sections this phase
needs. The file is a grid
(`grid-cols-[1fr_auto] sm:grid-cols-[1fr_auto_auto_auto_auto_auto]`) with one
`<div>` per cell, emitted per chapter. Today the action-buttons cell already
carries `h-full flex items-center justify-center` and the word-count cell
`hidden sm:flex items-center justify-center`, so those two are centred; the
chapter info cell is a `flex flex-col` whose content therefore stacks from the
top of the row. Rows are taller than one line of title because the `sm`-size
metric badges in the neighbouring cells are taller — hence the upward offset the
user reported.

**Deliverables.**
- `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/author-list.blade.php`
  — line 4, the chapter info cell. Add `justify-center` to the existing class
  list:
  - before: `class="flex flex-col gap-2 flex-1 min-w-0 surface-read p-2 text-on-surface"`
  - after: `class="flex flex-col justify-center gap-2 flex-1 min-w-0 surface-read p-2 text-on-surface"`

  `justify-center`, not `items-center`: the container is `flex-col`, so the main
  axis is vertical and `justify-content` is what centres vertically —
  `align-items` would centre the title *horizontally* instead, which is a
  regression, and see Open item #1. `h-full` is deliberately not added: the div
  is a grid item and already stretches to the full row height (`align-self`
  defaults to `stretch`), so `height: 100%` changes nothing here.

- Nothing else. Do not touch the other cells of this grid, the button
  arrangement, or `reorder-list.blade.php` (a different `ul`-based layout, not
  affected) — `02-architecture.md` §8 puts those out of scope.

**Tests.**
- No new test (see Working agreement).
- Regression only: `app/Domains/Story/Tests/Feature/Stories/StoryShowTest.php`
  renders this view for an author and must stay green unchanged. Run
  `./vendor/bin/sail artisan test Story` if you want the narrow loop before the
  gate.

**Acceptance.**
- ✅ `author-list.blade.php` line 4 contains `justify-center` and the diff for
  the phase is that one line.
- ✅ No `items-center` was added to a `flex-col` container in this file.
- ✅ `StoryShowTest` passes with no edits to it.
- ✅ On a story's table of contents seen as its author, at `sm` and above, the
  chapter title's text baseline is vertically centred in its row, level with the
  edit/delete icons — checked in a browser, recorded in the Visual QA table.
- ✅ Below `sm` the two-line cell (title + inline badges) is unchanged.
- ✅ `pnpm run gate` green.

---

## Phase 2 — Reader chapter list, vertically centre the chapter info cell

**Goal.** Make the chapter title in the reader's table of contents sit on the
same vertical centre line as the read toggle, the date and the metric badges in
the same grid row.

**Context.** `02-architecture.md` §3 and §4.1 are the only sections this phase
needs. This file is the reader-facing twin of the author list and has the same
symptom for the same reason, but **not** the same fix: every other cell here
already carries `flex items-center h-full`, while the chapter info cell is a
`flex flex-col` whose `<a>` child carries `flex-1`. That `flex-1` means the
anchor grows to fill the whole column height, so adding a vertical-centring
class to the parent alone would do nothing — there is no free space left to
distribute. The anchor's text then renders at the top of its own grown box.
Phase 1 made the equivalent change in the author view; this phase does not
depend on it and does not touch that file.

**Deliverables.**
- `app/Domains/Story/Private/Resources/views/chapters/partials/chapter-list/reader-list.blade.php`
  — two edits in the chapter info cell (lines 24–27):
  1. Line 24, the cell itself — add `justify-center`:
     - before: `class="flex flex-col col-span-1 surface-read text-on-surface p-2 min-w-0"`
     - after: `class="flex flex-col justify-center col-span-1 surface-read text-on-surface p-2 min-w-0"`
  2. Line 25, the title anchor — drop `flex-1` so the anchor stops absorbing the
     row's spare height:
     - before: `class="flex-1 truncate text-fg hover:text-fg/80 font-semibold py-2"`
     - after: `class="truncate text-fg hover:text-fg/80 font-semibold py-2"`

  Keep `truncate` on the anchor exactly as it is and do **not** turn the anchor
  into a flex container: `text-overflow: ellipsis` only applies to the anchor's
  own inline text, and wrapping it in a flex context breaks the truncation of
  long chapter titles. Removing `flex-1` does not narrow the anchor — as a
  column-flex child it still stretches to the full cell width.

  `justify-center`, not `items-center`, for the same reason as the author view:
  on a `flex-col` container `align-items` is the horizontal axis. See Open
  item #1.

- Nothing else. The read-toggle cell, the `@auth`/`@else` branch, the empty
  state and the `storyReadItem` script block are untouched.

**Tests.**
- No new test (see Working agreement).
- Regression only: `app/Domains/Story/Tests/Feature/Stories/StoryShowTest.php`
  and `app/Domains/Story/Tests/Feature/ReadingProgressTest.php` both render this
  view (the second exercises the read toggle that lives in its first column) and
  must stay green unchanged.

**Acceptance.**
- ✅ `reader-list.blade.php` line 24 contains `justify-center` and line 25 no
  longer contains `flex-1`; the diff for the phase is those two lines.
- ✅ No `items-center` was added to a `flex-col` container in this file.
- ✅ `StoryShowTest` and `ReadingProgressTest` pass with no edits to them.
- ✅ On a story's table of contents seen as a logged-in reader, at `sm` and
  above, the chapter title is vertically centred in its row, level with the read
  toggle and the date.
- ✅ A chapter title long enough to overflow its column is still truncated with
  an ellipsis on one line — it does not wrap and does not push the row taller.
- ✅ Seen as a guest (no read-toggle column rendered), the alignment is the same.
- ✅ Below `sm` the two-line cell (title + inline badges) is unchanged.
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

All rows are on a story's table of contents page (`/stories/{slug}`), which is
the only surface either phase touches.

| Surface | Check | OK? |
|---------|-------|-----|
| Author view, desktop (`sm`+) | Chapter title and date sit on the same centre line as the edit/delete icons and the word/comment/read badges, for every row | |
| Author view, desktop, mixed row heights | A story whose chapters have very different word counts (badge widths differ) still shows every title centred, no row drifting | |
| Author view, unpublished chapter | The `visibility_off` / `schedule` popover icons still sit inline with the title and the popovers still open | |
| Author view, long title | A title longer than its column truncates with an ellipsis on one line; the row does not grow | |
| Author view, mobile (< `sm`) | Title on line 1, date + badges on line 2, unchanged from before the fix; nothing horizontally centred | |
| Reader view, logged-in confirmed user, desktop | Title centred level with the read toggle, the date and the badges | |
| Reader view, read toggle | Clicking the toggle still marks/unmarks the chapter and the icon swaps without the row shifting vertically | |
| Reader view, guest, desktop | With no read-toggle column, the title is still centred in its row | |
| Reader view, long title | Truncated with an ellipsis on one line, no wrap, row height unchanged | |
| Reader view, mobile (< `sm`) | Title on line 1, date + badges on line 2, unchanged from before the fix | |
| Reader view, empty story | The "no chapters" message still renders (the empty state is outside the grid and must not have moved) | |
| Both views, single-chapter story | One row, still centred — the fix must not depend on there being several rows | |

Screenshots worth keeping in `shots/`: author desktop, reader desktop, and one
mobile capture per view — ideally paired before/after, since the whole change is
a few pixels of vertical offset and is hard to judge from a single image.

## Open items

1. **The class list recorded in `02-architecture.md` §3 and `DECISIONS.md` #2
   (`h-full items-center`) does not do what it is described as doing, and this
   plan implements the recorded *intent* with a different class.** Needed by:
   phase 1 and phase 2. Both target containers are `flex flex-col`. In a column
   flex container the main axis is vertical, so `justify-content` controls
   vertical placement and `align-items` controls the horizontal one:
   `items-center` would shrink the title row to its content width and centre it
   horizontally — a visible regression — while leaving the vertical offset the
   user reported exactly where it is. `h-full` is a no-op on these two elements
   because both are grid items and already stretch to the full row height.
   The intent recorded at REFINE (`01-functional.md` §7 #2, "add vertical
   centering classes to chapter info containers to match button alignment") is
   unchanged and is what the phases deliver, via `justify-center`. The task is in
   `auto` mode, so this could not be put to the user; **WRAP must surface it**,
   and if the user in fact wanted the titles horizontally centred, that is a new
   request, not this one.

2. **The reader view needs `flex-1` removed from the title anchor**, which
   `02-architecture.md` §3 does not mention. Needed by: phase 2. Without it the
   anchor grows to fill the column and the parent's `justify-center` has no free
   space to distribute, so the fix would silently do nothing. The author view
   does not have this problem — its `flex-1` sits on an anchor nested inside a
   row-direction wrapper, where it controls width, not height, and must stay.

3. **The diagnosis that rows are taller than the title because of the `sm`-size
   metric badges was established by reading the markup, not by measuring in a
   browser.** Needed by: VERIFY. If the real cause turns out to be something else
   (a `py-2` mismatch, a line-height difference), `justify-center` still centres
   whatever spare height exists, so the fix holds — but the "mixed row heights"
   row of the checklist is the one that would expose a wrong diagnosis.

4. **No automated test guards this fix.** Needed by: WRAP. A future layout change
   to either grid can silently reintroduce the offset. This is the accepted
   tradeoff from `02-architecture.md` §6, recorded here so it is a known gap
   rather than an oversight.
