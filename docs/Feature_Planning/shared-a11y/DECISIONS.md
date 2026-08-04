# Shared — tabs & confirm-modal a11y — decisions log

Append-only. Every question the user arbitrated, with the answer as given.
BUILD reads this before asking anything already settled.

Format: one row per decision. Never edit a row — if a decision is reversed, add
a new row that supersedes it and note the number.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-08-04 | REFINE | Scope | Exactly the two gaps from quote-contest A40 (tabs panel ARIA + confirm-modal focus). No general a11y audit. | — |
| 2 | 2026-08-04 | REFINE | Confirm focus | Always move focus into confirm dialogs (same as existing `focusable` on Shared modal); no per-call-site opt-in required. | — |
| 3 | 2026-08-04 | DESIGN | Who owns tabpanel ARIA? | Shared stamps tab id/aria-controls; consumers stamp panel role/id/aria-labelledby under optional `id` prop (default `tabs`). Rejected: named-slot redesign; runtime ARIA injection. | — |
| 4 | 2026-08-04 | DESIGN | Confirm focus wiring | Always forward `focusable` on confirm-modal; no opt-out. | — |
| 5 | 2026-08-04 | VERIFY | Browser / e2e VERIFY? | Skip. Treat task as verified via Shared feature tests; delete temporary e2e files; wrap. | — |

## Assumptions made without asking

Used in `auto` mode, or when the user was unavailable. Each one is a decision
the user may want to reverse — surface these in the WRAP summary.

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Request text is the full functional contract; no interview needed. | REFINE | Yes — expand scope if wrong |
| A2 | Inactive tab panels stay in the DOM via Alpine `x-show` (as all five consumers do today); the fix must not switch to `x-if` / removal. | REFINE | Yes |
| A3 | Confirm-modal always forwarding focus is correct for every consumer (all are destructive/irreversible confirms). | REFINE | Yes — could make opt-in later |
| A4 | Existing English `aria-label="Tabs"` on the tablist is left as-is. | REFINE | Yes |
| A5 | Tabs "one-line fix" in the request may need consumer markup or a small API change because Shared tabs does not render panels today — that is DESIGN, not a functional change. | REFINE | N/A (finding) |
| A6 | Tabs: Shared stamps tab `id`/`aria-controls`; consumers stamp panel `role`/`id`/`aria-labelledby` under optional `id` prop (default `tabs`). No slot redesign, no runtime ARIA injection. | DESIGN | Yes — redesign tabs later if we want owned panels |
| A7 | Confirm-modal always forwards `focusable` (no opt-out prop). | DESIGN | Yes |
| A8 | ConfirmModalA11yTest asserts `firstFocusable().focus()` in rendered HTML, not a literal `focusable` attribute — modal consumes the attribute via `$attributes->has()` and never re-emits it. | BUILD | N/A (finding) |
| A9 | Phase 2 commit proceeded without waiting for a full `npm run gate` run (user: commit without gate). Smoke test for Search panels had already passed. | BUILD | N/A |
