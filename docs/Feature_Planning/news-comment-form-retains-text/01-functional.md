# News comment form retains text after submit — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

After a confirmed user posts a root comment on a published news article, the
compose form that stays available for further comments must appear empty — not
pre-filled with the text they just submitted. The same rule applies to reply
composers after a successful reply. This is a regression fix in Comment's
shared compose behaviour; News only makes the bug visible because it allows
unlimited roots.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Formulaire racine | The compose form for a new top-level comment on the thread |
| Brouillon local | Client-side draft of the in-progress comment body (Comment's existing draft autosave) |
| Commentaire soumis | A comment that has just been successfully stored and redirected back to the thread |

## 3. Roles & visibility

Unchanged from existing Comment / News comment rules (`news-comments` done
record). This fix does not alter who can comment.

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Members-only prompt (unchanged) | Nothing |
| `user` / `user-confirmed` / moderator / admin | Thread + form per existing policy | Post; after success, form is empty |

## 4. Functional requirements

### 4.1 Post a root comment on news, form empty afterwards

1. A logged-in user who may create roots opens a published news article.
2. They write a root comment in the compose form and submit.
3. The comment appears in the thread (existing success path: flash + deep link).
4. The root compose form is still shown (news allows multiple roots — unchanged).
5. **The form body is empty** — no residual text from the comment just posted,
   and no restored local draft of that submission.

### 4.2 Post a reply, composer empty afterwards

1. User opens a reply composer, writes a reply, submits successfully.
2. After redirect, if a reply composer is shown again for that thread, its body
   is empty — same clearing expectation as roots.

### 4.3 Draft autosave still works mid-compose

1. User starts typing a comment and navigates away without submitting.
2. On return to the same thread, the unfinished draft may still restore
   (existing behaviour — unchanged).
3. Only a **successful** submit must consume/clear that draft so it does not
   reappear as if it were still in progress.

### 4.4 Chapter comments unchanged in behaviour

1. After a chapter root comment, the root form remains hidden (one root per
   user — existing policy).
2. No requirement to change chapter policy or UI gating.

### 4.5 Validation failure

1. If submit fails validation, the user keeps their input so they can fix it
   (existing behaviour — unchanged). This fix must not clear drafts on failed
   submit.

## 5. Lifecycle

N/A for new data. Comment create/redirect/cascade behaviour unchanged. Only the
post-success state of the compose UI changes.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — no change |
| Visibility / privacy | N/A — no change |
| Settings | N/A |
| Notifications | N/A — existing news reply notifications unchanged |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no new strings expected |
| Mobile | Same empty-form expectation on mobile viewport |
| Accessibility | Form remains usable; empty after success is the success criterion |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Mode | `auto` — bug fix, no product interview |
| 2 | Expected post-submit UI | Form visible (news) but body empty |
| 3 | Scope of hosts | Shared Comment compose behaviour; news is the visible case, replies included |
| 4 | Mid-compose drafts | Keep; only successful submit must clear |

## 8. Out of scope

- Changing News to one-root-per-user (would only mask the bug; contradicts
  `news-comments`).
- Redesigning draft autosave UX beyond clearing after successful submit.
- Chapter policy or "hide form after root" behaviour.
- New notification, event, setting, or permission changes.

## 9. Open questions

None blocking.
