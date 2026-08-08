# News — pin to carousel white screen — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared toggle focus geometry | S | — | DONE |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Shared toggle focus geometry

**Goal.** Stop `<x-shared::toggle>` from scrolling Administration’s overflow pane
off-screen on focus, while keeping the same props and checkbox form semantics
(architecture §1, §4, §7 #1–#3).

**Depends on architecture.** §1 Domain placement · §4 Frontend architecture ·
§6 Testing strategy · §8 File layout. No schema/PHP API (§2–§3). No deptrac
edges (§5).

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/toggle.blade.php` — replace the
  clipped `sr-only` checkbox with an input that occupies the visible track box
  (`label` `relative`; input `absolute` inset over the track, opacity 0 /
  equivalent; keep `peer` so `peer-checked` / `peer-focus` on the track still
  work). Props (`name`, `checked`, `value`, `disabled`, `id`, `label`,
  `btnColor`, `textColor`) unchanged. No Alpine/JS.
- `app/Domains/Shared/Tests/Feature/View/Components/ToggleComponentTest.php` —
  new Blade render tests for the markup contract.
- `app/Domains/News/Tests/Feature/Admin/NewsControllerTest.php` — extend the
  existing `update` describe with one case that posts `is_pinned` so the
  consumer form still binds the named checkbox (architecture §6).

**Tests.**
- `Shared/Tests/Feature/View/Components/ToggleComponentTest.php`
  - `it('renders a named checkbox without sr-only')` — `$this->blade('<x-shared::toggle name="is_pinned" />')` asserts `name="is_pinned"`, `type="checkbox"`, and that the input class list does **not** include `sr-only`.
  - `it('positions the checkbox over the track for focus geometry')` — asserts the wrapping `label` is `relative` (or equivalent) and the input uses absolute overlay classes (e.g. `absolute` + inset / size matching the track) consistent with architecture §4.
  - `it('keeps peer styling hooks')` — asserts the input still has `peer` and the track span still uses `peer-checked:` / `peer-focus:` classes.
  - `it('honours checked, disabled, value, id, and label props')` — render with those props set; assert attributes and label text appear.
- `News/Tests/Feature/Admin/NewsControllerTest.php` (inside `describe('update')`)
  - `it('persists is_pinned when the form posts the toggle')` — admin updates an existing news item with `is_pinned => true` (plus required fields as in neighbouring tests); assert redirect and `is_pinned` true in DB. Pin still saves only on Enregistrer (functional §4.1 #5) — this proves the wire name, not the scroll bug.

Do **not** add Vitest (architecture §6). Scroll-stays-in-view is VERIFY-only.

**Acceptance.**
- ✅ Toggle markup no longer uses a clipped `sr-only` focus target; focus geometry matches the visible switch (architecture §4).
- ✅ Public props and form field name/value/`checked`/`disabled` behaviour unchanged for all consumers.
- ✅ News admin update with `is_pinned` still persists on submit.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| News admin **edit** — pin toggle (mouse) | Open an existing actualité; click « Épingler dans le carousel »; toggle flips; form fields and toggle stay in the viewport (no white/empty pane) | |
| News admin **edit** — pin toggle (keyboard) | Tab to the pin control; Space/Enter activates; same: state flips, no scroll-away of the admin main pane | |
| News admin **create** — pin toggle | Same click behaviour as edit if the create form exposes the control (shared `_form`) | |
| News admin edit — save path unchanged | Toggle on, then Enregistrer; pin persists as today (no AJAX on click alone); no unexpected redirect/flash change | |
| Narrow / mobile admin viewport | Repeat mouse toggle on a narrow width; control usable; no scroll-away | |
| Moderator (or Admin) on News edit | Eligible role can still see and use the pin toggle | |
| Spot-check another admin toggle (e.g. FAQ `is_active` or StoryRef) | Click once; no scroll-away; visual on/off still works | |
| If white screen still reproduces after Shared fix | Stop and re-open cause (architecture §9 / DECISIONS assumption #1 — possible Quill/editor interaction) | |

## Open items

None blocking for BUILD. Cause remains an assumption until VERIFY (DECISIONS
assumption #1); if phase 1 is green but visual QA fails, escalate before further
UI churn.
