# Comment editor not displaying — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

On a chapter comments thread, opening **Répondre** or **Éditer** must show the
same rich-text editor (toolbar + editable area) that works elsewhere in the
app. Today the shell can appear while the editor stays blank — a regression
after Editor was isolated into its own domain. Fix restores that UI for every
authenticated user who is allowed to reply or edit, including when they cannot
post a new root comment. Coverage must include browser E2E so the blank editor
cannot regress unnoticed.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Éditeur de commentaire | The rich-text field used to compose a root comment, a reply, or an edit |
| Répondre | Action that opens the reply composer under an existing comment |
| Éditer | Action that opens the edit composer on one's own comment |
| Commentaire racine | Top-level comment on the chapter (not a reply) |

## 3. Roles & visibility

Unchanged from existing Comment policy. This bugfix does not alter who may
comment, reply, or edit — only that the editor **renders** when those actions
are allowed.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Comment list (existing rules) | No reply/edit editors |
| `user` / `user-confirmed` | Comments | Reply/edit when policy allows; editor must display |
| Chapter author / co-author | Comments | Typically no new root; reply/edit still need a working editor when allowed |
| Moderator / Admin | Per existing policy | Same rendering requirement when they use reply/edit |

## 4. Functional requirements

### 4.1 Reply with a visible editor

1. Authenticated user who may reply opens **Répondre** on a comment (including
   after comments were loaded via infinite scroll / fragment).
2. The reply composer shows a usable rich-text editor (toolbar + typing area),
   not an empty box.
3. They can type and submit as today; submission behaviour is unchanged.

### 4.2 Edit with a visible editor

1. Authenticated user who may edit opens **Éditer** on their comment.
2. The edit composer shows a usable rich-text editor with the existing body
   loaded for editing.
3. Save/cancel behaviour is unchanged.

### 4.3 No root composer still works

1. When the page does **not** show a root-comment form (e.g. chapter author, or
   reader who already posted a root), reply and edit must still show the editor.
2. When the root form **is** present, reply and edit continue to work; editor
   assets must not break (no double-broken init, no missing toolbar).

### 4.4 Regression protection (E2E)

1. After the fix, Playwright E2E covers comment reply and edit on a chapter.
2. Scenarios must include at least one user for whom the root form is absent
   (the broken path) and assert the editor is visible and interactive.
3. E2E stays in the suite (not a one-off VERIFY script that is deleted at WRAP).

## 5. Lifecycle

N/A for this fix — no change to comment create/edit/delete, user deactivation,
or chapter deletion. Existing Comment behaviour remains.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A change — existing Comment policy; fix is rendering only |
| Visibility / privacy | N/A — no new data surfaces |
| Settings | N/A |
| Notifications | N/A — no new notifications |
| Domain events | N/A — no new events |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | French UI labels unchanged |
| Mobile | Editor must display on mobile viewport as on desktop (same component) |
| Accessibility | Editor remains keyboard-usable as elsewhere; no new a11y scope beyond restoring display |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Task mode | `auto` — bug fix |
| 2 | E2E after fix | Required; permanent Playwright coverage for reply/edit |
| 3 | Surfaces in scope | Chapter comments only (only shipped host today) |

## 8. Out of scope

- News comments (`news-comments/` backlog) — not shipped
- Changing who may root/reply/edit
- Redesigning the comment UI or switching editor libraries
- Fixing unrelated Editor consumers that already self-load assets on full pages

## 9. Open questions

None blocking.

Non-blocking (assumed — reverse in DECISIONS if wrong): E2E covers both
`canCreateRoot` true and false; reply **and** edit; chapter page only.
