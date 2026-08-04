# Shared — tabs & confirm-modal a11y — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Close two known accessibility gaps in Shared UI primitives so every domain that
renders them gets correct screen-reader and keyboard behaviour. Tabs must expose
a proper tab↔panel relationship; confirmation dialogs must move focus into the
dialog when they open. No new user-facing copy or flows.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Tab strip | The Shared `tabs` control: a row of tab buttons. |
| Panel | The content region shown when a tab is selected (today, consumer markup in the component slot). |
| Confirmation dialog | The Shared `confirm-modal` wrapper around a modal, used for destructive or irreversible actions. |

## 3. Roles & visibility

N/A for product roles — these are Shared primitives. Behaviour applies to
whoever already sees the host page (guest through admin). No role gains or loses
access.

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` / `user-confirmed` / Author / Moderator / Admin | Unchanged — whatever the consuming page already shows | Unchanged interaction; assistive tech gets correct structure and focus |

## 4. Functional requirements

### 4.1 Tabs — panel association

1. When a page uses the Shared tabs control, each visible content panel is
   announced as a tab panel and is associated with the tab that controls it
   (`role="tabpanel"` + labelling that ties the panel to its tab).
2. Selecting a tab still shows only that panel's content (existing show/hide
   behaviour preserved). Inactive panels must not be treated as reachable
   content by a screen reader beyond what `display: none` / Alpine `x-show`
   already does today.
3. Existing keyboard behaviour on the tab strip (arrow keys, `aria-selected`,
   `tabindex`) is preserved.
4. No consumer already ships conflicting panel roles; the fix must not break the
   five known hosts (Quote Contest, Secret Gift, Statistics admin, Search
   results, Story cover selector).

### 4.2 Confirm modal — focus on open

1. When a Shared confirmation dialog opens, keyboard focus moves into the
   dialog (same behaviour as Shared modals that already opt into `focusable`).
2. Existing modal keyboard contracts remain: Tab/Shift+Tab cycle inside the
   dialog, Escape closes.
3. Applies to every current consumer (Quote Contest category delete, Story
   delete, chapter delete, collaborator leave/remove/add-author) without each
   call site needing a new prop.

### 4.3 Regression / non-goals of behaviour

1. Visual appearance of tabs and confirmations is unchanged for sighted users.
2. No new settings, notifications, or routes.

## 5. Lifecycle

N/A — no new persisted data. Opening/closing dialogs and switching tabs remain
ephemeral UI state owned by the existing Alpine/modal wiring.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — Shared primitives; no role gating change |
| Visibility / privacy | N/A — no data exposure change |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A — no persisted entities |
| Media | N/A |
| Search | N/A beyond Search's existing tabs host keeping working |
| i18n | N/A — no new user-facing strings (existing `aria-label="Tabs"` stays English as today unless DESIGN touches it; out of scope to translate) |
| Mobile | Unchanged layout; focus-on-open must work on the same breakpoints as today |
| Accessibility | **In scope** — the whole task |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Scope | Exactly the two gaps from `quote-contest/` A40: tabs panel ARIA, confirm-modal focus forwarding |
| 2 | Confirm focus default | Always move focus into confirm dialogs (match existing `focusable` modal precedent); consumers should not need a new opt-in |
| 3 | General a11y audit | Out of scope |

(From the request; auto-mode assumptions below fill the rest.)

## 8. Out of scope

- A general accessibility audit of the app or of Shared modals (`role="dialog"`,
  `aria-modal` on the base modal, etc.).
- Changing `scrollable-tabs` (link/button nav, no panels).
- Translating or redesigning the tabs `aria-label`.
- Requiring every domain to adopt tabs/confirm-modal if they use ad-hoc markup.
- Changing how consumers hide inactive panels (`x-show` stays).

## 9. Open questions

None blocking. DESIGN must choose *how* tab panels get their roles given that
the Shared tabs component currently renders only the strip and dumps the slot
(consumers own the panel divs) — that is a technical tradeoff, not a functional
unknown.
