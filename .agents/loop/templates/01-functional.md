# <Task title> — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Two or three sentences: what the feature is, for whom, and the value.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| | |

Define every new noun the feature introduces, in French if it is user-facing
(the app is French-only).

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | | |
| `user` (non-confirmed) | | |
| `user-confirmed` | | |
| Author / co-author of the target | | |
| Moderator | | |
| Admin | | |

## 4. Functional requirements

### 4.1 <flow name>

Numbered steps of the user flow. One sub-section per flow. Include the error
and edge paths, not just the happy one.

## 5. Lifecycle

What happens to this data when the parent is deleted, unpublished, or edited;
when the user is deactivated, reactivated, or deleted. What the UI shows in each
of those states.

## 6. Cross-cutting concerns

One line per applicable item from
[`../references/cross-cutting-checklist.md`](../references/cross-cutting-checklist.md).
Items that do not apply are listed as "N/A — <reason>", so a reader can see they
were considered.

| Concern | Decision |
|---------|----------|
| Roles | |
| Visibility / privacy | |
| Settings | |
| Notifications | |
| Domain events | |
| Statistics | |
| Moderation | |
| Lifecycle / cascade | |
| Media | |
| Search | |
| i18n | |
| Mobile | |
| Accessibility | |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | | |

Mirror of `DECISIONS.md`, restricted to functional decisions.

## 8. Out of scope

Explicit non-goals for this version, so nobody re-opens them mid-build.

## 9. Open questions

Anything unresolved, each marked **blocking** or **non-blocking**. The step
cannot close with a blocking question open.
