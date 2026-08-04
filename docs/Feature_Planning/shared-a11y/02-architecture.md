# Shared — tabs & confirm-modal a11y — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Shared** owns both fixes. They are Blade primitives already under
`app/Domains/Shared/Resources/views/components/`. No new domain, no public PHP
API, no tables.

### 1.1 Changes in other domains

Consumer domains only adjust **panel markup** to honour the documented id
convention (see §4). No services, routes, or policies change.

| Domain | Change |
|--------|--------|
| Calendar (Quote Contest, Secret Gift) | Panel wrappers get `role` / `id` / `aria-labelledby` |
| Statistics | Same on admin tabs panels |
| Search | Same on results panels |
| Story | Same on cover-selector tab panels |

Confirm-modal consumers need **no** call-site change — focus is always on.

## 2. Data model

N/A — no persistence.

## 3. PHP architecture

N/A — Blade-only. No public API, services, policies, events, or routes.

## 4. Frontend architecture

### 4.1 Tabs

**Contract** (WAI-ARIA tabs pattern, additive):

- Optional component prop `id` (string). Default `'tabs'`. Used as a prefix so
  two tab strips on one page can avoid id collisions by passing distinct values.
- Each tab button gets:
  - `id="{id}-tab-{key}"`
  - `aria-controls="{id}-panel-{key}"`
- Existing `role="tablist"` / `role="tab"` / `aria-selected` / `tabindex` /
  arrow-key behaviour stay as they are.
- The component still does **not** render panels. Slot content remains
  consumer-owned.
- **Consumer obligation** on each panel root that is shown/hidden with
  `x-show="tab === '{key}'"`:
  - `role="tabpanel"`
  - `id="{id}-panel-{key}"` (same `{id}` as the parent `<x-shared::tabs>`)
  - `aria-labelledby="{id}-tab-{key}"`
- Inactive panels keep Alpine `x-show` (not `x-if`); no extra `aria-hidden` layer
  beyond what `display: none` already provides.

Shared README / AGENTS note documents this contract next to the other keyboard
a11y notes (popover precedent).

### 4.2 Confirm modal

`<x-shared::confirm-modal>` always passes the bare `focusable` attribute through
to `<x-shared::modal>`, matching Auth / Quote Contest direct-modal usage.
Underlying modal behaviour is unchanged: on open, after 100ms, focus moves to
the first focusable control inside the dialog; Tab cycle and Escape remain.

No new prop on `confirm-modal` — always-on per functional decision #2.

## 5. Deptrac

No new edges. Edits stay inside Shared Blade + consumer Blade only.

## 6. Testing strategy

| Layer | What |
|-------|------|
| Feature (Shared) | Render `<x-shared::tabs>` and assert tab buttons expose `id` + `aria-controls`; render `<x-shared::confirm-modal>` and assert the nested modal markup includes `focusable` (attribute present on the modal root / detectable in HTML). |
| Feature (optional smoke) | One consumer page (e.g. Quote Contest config or cover modal) asserting a panel has `role="tabpanel"` — only if Shared-only render tests cannot cover the convention; prefer not to duplicate across all five hosts. |
| Vitest | N/A — no JS module change. |
| VERIFY | Keyboard: open a confirm dialog and confirm focus lands inside; one tabs surface (Quote Contest or cover) with a screen-reader / a11y tree check if practical, else attribute spot-check in browser. |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Who owns `role="tabpanel"`? | **A** Shared stamps tab `id`/`aria-controls`; consumers stamp panel ARIA under a documented `{id}` convention. **B** Redesign tabs to render panels via named slots. **C** Alpine/JS walks the slot and injects ARIA at runtime. | **A** | Smallest reversible change; matches how panels already live in the slot; no API break. B is only worth it if we later want a single place that owns show/hide. C hides the contract and is harder to assert in PHP tests. |
| 2 | Confirm focus opt-in vs always | Always forward `focusable` · keep opt-in prop on confirm-modal | Always | Spec decision #2; every current consumer is a destructive confirm. |
| 3 | Id prefix | Required prop · optional with default `'tabs'` · random `uniqid` | Optional default `'tabs'` | Enough for today's single-strip pages; collisions are an explicit consumer fix. Random ids break stable assertions. |

## 8. File layout

No new classes. Touched surfaces (ownership only — not a change list):

```
app/Domains/Shared/Resources/views/components/
  tabs.blade.php
  confirm-modal.blade.php
app/Domains/Shared/Tests/…          # new feature test(s) for the two contracts
app/Domains/Shared/{README,AGENTS}.md
(+ consumer Blade panel roots under Calendar / Statistics / Search / Story)
```

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| A consumer forgets panel ARIA after adding a new tabs host | New Shared tabs usage without following the README contract — catch in code review / VERIFY checklist |
| Two tab strips with default `id="tabs"` on one page | Duplicate ids in the a11y tree — pass distinct `id` props |
| `focusable` on every confirm surprises a future non-destructive use | Add an opt-out only if such a consumer appears |
