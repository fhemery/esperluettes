# Shared — tabs & confirm-modal a11y

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.

**Status:** DONE — 2026-08-04 · **Domain(s):** `Shared` (+ consumer Blade in
Calendar, Statistics, Search, Story) · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

Closes two Shared a11y gaps left by `quote-contest/` (A40). Tab buttons expose
`id` / `aria-controls`; each consumer panel root is a `role="tabpanel"` with
matching `id` / `aria-labelledby`. Confirmations always forward `focusable` so
opening a confirm moves keyboard focus into the dialog.

## Key behaviour

- No role/visibility change — whoever already sees the host page gets the fixed
  markup and focus behaviour.
- Tabs still hide inactive panels with Alpine `x-show` (not `x-if`).
- New tabs hosts must stamp panel ARIA using the same `{id}` prefix as the
  parent `<x-shared::tabs>` (`id` prop defaults to `tabs`).
- Confirm-modal focus is always on; there is no opt-out prop.

## Where the code lives

| Concern | Path |
|---------|------|
| Tabs component | `app/Domains/Shared/Resources/views/components/tabs.blade.php` |
| Confirm modal | `app/Domains/Shared/Resources/views/components/confirm-modal.blade.php` |
| Underlying focus | `app/Domains/Shared/Resources/views/components/modal.blade.php` (`focusable`) |
| Docs | `app/Domains/Shared/{README,AGENTS}.md` |
| Tests | `app/Domains/Shared/Tests/Feature/View/Components/{TabsA11y,ConfirmModalA11y,TabsConsumerPanelsSmoke}Test.php` |
| Consumer panels | Quote Contest, Secret Gift, Statistics admin, Search results, Story cover tabs |

## Extension points used

None — Blade-only Shared primitives.

## Decisions worth remembering

- Panel ARIA is a **shared contract**: Shared owns tab-button ids; consumers own
  panel roots (no named-slot redesign, no runtime injection).
- Confirm focus is **always** forwarded — every current consumer is destructive.
- VERIFY skipped browser/e2e (user): markup + `focusable` wiring covered by
  Shared feature tests; temporary e2e files removed at WRAP.

## Not done

- General a11y audit of Shared modals (`role="dialog"`, `aria-modal`, …).
- Translating the tablist `aria-label="Tabs"`.
- Changing `scrollable-tabs`.
- No new backlog leftovers.
