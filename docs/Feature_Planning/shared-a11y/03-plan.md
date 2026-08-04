# Shared — tabs & confirm-modal a11y — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.
>
> **Note:** PLAN ran in the orchestrator thread after `feature-planner` hit an
> API limit — same skill, same template.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Shared primitives + tests + docs | S | — | DONE |
| 2 | Consumer tab panels | S | 1 | TODO |

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

## Phase 1 — Shared primitives + tests + docs

**Goal.** Make Shared tabs expose tab↔panel id wiring on the strip, and make
confirm-modal always move focus into the dialog on open — both proven by Shared
feature tests and documented.

**Depends on architecture.** §4.1 (tabs contract), §4.2 (confirm-modal
`focusable`), §6 (testing), §7 tradeoffs 1–3.

**Deliverables.**
- `app/Domains/Shared/Resources/views/components/tabs.blade.php` — add optional
  `@props` `id` default `'tabs'`; on each tab `<button role="tab">` set
  `id="{id}-tab-{key}"` and `aria-controls="{id}-panel-{key}"`. Preserve
  existing roles, `aria-selected`, `tabindex`, arrow keys, hash tracking,
  scrollable chrome.
- `app/Domains/Shared/Resources/views/components/confirm-modal.blade.php` —
  pass bare `focusable` through to `<x-shared::modal>` (always on).
- `app/Domains/Shared/Tests/Feature/View/Components/TabsA11yTest.php` — render
  `<x-shared::tabs>` (Blade / component render) with two keys; assert each tab
  button has the expected `id` and `aria-controls`; assert custom `id` prop
  prefixes both.
- `app/Domains/Shared/Tests/Feature/View/Components/ConfirmModalA11yTest.php` —
  render `<x-shared::confirm-modal>`; assert the output includes the `focusable`
  attribute on the modal (same detection style as other Shared component HTML
  assertions, e.g. `CountdownTimerTest`).
- `app/Domains/Shared/README.md` and `app/Domains/Shared/AGENTS.md` — document
  the tabs id/`aria-controls` + consumer panel obligation, and that
  confirm-modal always forwards `focusable`.

**Tests.**
- `TabsA11yTest` — default prefix `tabs`; custom `id="cover"` prefixes.
- `ConfirmModalA11yTest` — `focusable` present in rendered HTML.

**Acceptance.**
- ✅ Tab buttons expose `id` + `aria-controls` per architecture §4.1.
- ✅ Confirm-modal rendered HTML includes `focusable` for the nested modal.
- ✅ Shared docs state the consumer panel contract (needed by phase 2).
- ✅ `npm run gate` green.

**Not in this phase.** Consumer panel markup — tabs association is incomplete in
the a11y tree until phase 2; that is intentional.

---

## Phase 2 — Consumer tab panels

**Goal.** Every existing `<x-shared::tabs>` host stamps `role="tabpanel"`,
matching `id`, and `aria-labelledby` on each panel root so the phase-1 tab
buttons resolve to real panels.

**Depends on architecture.** §4.1 consumer obligation, §1.1 other domains.
**Builds on phase 1.** Tab buttons already use `{id}-tab-{key}` /
`{id}-panel-{key}`; default prefix is `tabs` unless a host passes `id`.

**Deliverables.** Add attributes on each panel root (keep `x-show` / `x-cloak`
and existing classes). Use default prefix `tabs` unless the host sets a
distinct `id` on `<x-shared::tabs>` (only do that if a page has two strips —
none today).

| File | Panel keys |
|------|------------|
| `app/Domains/Calendar/Private/Activities/QuoteContest/Resources/views/components/quote-contest.blade.php` | `my-quotes`, `votes`, `results` (+ any other `x-show="tab === …"` panels in that file) |
| `app/Domains/Calendar/Private/Activities/SecretGift/Resources/views/components/secret-gift.blade.php` | `prepare`, `received` (+ any other tab panels in that file) |
| `app/Domains/Statistics/Private/Resources/views/pages/admin/index.blade.php` | `users`, `content`, `comments` |
| `app/Domains/Search/Private/Resources/views/partials/search-results.blade.php` | `stories`, `profiles` |
| `app/Domains/Story/Private/Resources/views/components/cover-tab-default.blade.php` | `default` |
| `app/Domains/Story/Private/Resources/views/components/cover-tab-themed.blade.php` | `themed` |
| `app/Domains/Story/Private/Resources/views/components/cover-tab-custom.blade.php` | `custom` |

On each panel root:
- `role="tabpanel"`
- `id="tabs-panel-{key}"` (or `{customId}-panel-{key}` if the parent tabs `id` was set)
- `aria-labelledby="tabs-tab-{key}"` (same prefix)

**Tests.**
- Prefer one Shared-or-consumer smoke feature test that renders (or visits) one
  host and asserts at least one panel has `role="tabpanel"` and matching
  `aria-labelledby` — e.g. extend coverage via Blade render of a minimal
  Quote Contest / Search partial if helpers allow, or a Story cover partial.
  Do **not** duplicate assertions for all five hosts.
- Existing domain feature tests must stay green (no behaviour change).

**Acceptance.**
- ✅ Every panel root listed above has `role="tabpanel"`, `id`, `aria-labelledby`
  matching the Shared convention.
- ✅ Inactive panels still use `x-show` (not removed from the DOM via `x-if`).
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Quote Contest activity page (confirmed user) | Tabs switch; active panel has `role="tabpanel"` and `aria-labelledby` matching the selected tab's `id` in the a11y tree / DevTools | |
| Quote Contest admin category delete | Opening confirm moves focus into the dialog; Escape closes; Tab stays inside | |
| Story cover selector modal | Three cover tabs; each panel stamped; keyboard arrows on tab strip still work | |
| Search results (stories/profiles tabs) | Both panels stamped; switching tabs still works | |
| Confirm delete story (or chapter) | Focus enters confirm on open | |
| Mobile (~375px) Quote Contest tabs | Tab strip + panel association still correct; confirm focus still works if opened | |

## Open items

None — `focusable` on `modal.blade.php` already exists (`$attributes->has('focusable')`); consumer panel paths verified by grep.
