# Story — one author check, not two — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Remove the misnamed public API `StoryPublicApi::isAuthorOrCoAuthor()`. Authorship
on the Story public API is expressed only by `isAuthor()`. The sole caller —
Quote’s `canQuote` — switches to that check, so **beta readers can quote**
chapters of stories they beta-read. Authors (including named co-authors) remain
blocked from quoting their own stories.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Author | A collaborator with role `author` on the story. Includes the creator and anyone named as co-author — there is no separate co-author role. |
| Beta reader | A collaborator with role `beta-reader`. Treated as a reader for quoting. |
| `canQuote` | Story-level rule: a confirmed user may create a quote on a chapter of that story only if they are not an author of the story. |

No new user-facing nouns; no French UI copy changes.

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Unchanged (no quote UI / cannot quote) | — |
| `user` (non-confirmed) | Unchanged | Cannot quote (still requires `user-confirmed`) |
| `user-confirmed` (plain reader) | Unchanged | Can quote (unchanged) |
| `user-confirmed` + beta reader on the story | Quote UI already shown | **Can quote** (behaviour change — previously blocked by accident) |
| Author / co-author of the story (`role = author`) | Unchanged | Cannot quote that story (unchanged) |
| Moderator / Admin | N/A for this change | No override introduced |

## 4. Functional requirements

### 4.1 Single author predicate on Story public API

1. `StoryPublicApi::isAuthor(userId, storyId)` remains the only public way to ask
   “is this user an author of this story?”.
2. `StoryPublicApi::isAuthorOrCoAuthor()` is removed. Callers must not keep using
   it under that name.
3. If a future feature needs “any collaborator”, it must introduce an honestly
   named check (e.g. `isCollaborator()`), not revive `isAuthorOrCoAuthor`.

### 4.2 Who may quote a story

1. A user may quote a chapter of a story only if they have role `user-confirmed`
   **and** they are **not** an author of that story (`role = author`).
2. Being a beta reader on the story does **not** block quoting.
3. Authors and co-authors (same role) remain unable to quote any chapter of
   their story.
4. The chapter UI already hides the quote action with an author-only check; after
   this change the policy matches that UI (today beta readers see “Citer” then
   get 403).

### 4.3 Documentation

1. `app/Domains/Quote/AGENTS.md` must describe `canQuote` as blocking **authors**
   (story-level) via `StoryPublicApi::isAuthor`, not “authors/co-authors” via
   `isAuthorOrCoAuthor`.

## 5. Lifecycle

N/A — no new data, no cascade rules. Existing quote rows and collaborator rows
are unchanged. Who may create a **new** quote is evaluated at request time from
current collaborator roles.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Confirmed users only for quoting (unchanged). Authors blocked; beta readers allowed. Non-confirmed unchanged. |
| Visibility / privacy | N/A — no visibility model change |
| Settings | N/A |
| Notifications | N/A — quote-created notifications already target authors via `getAuthorIds()` |
| Domain events | N/A — no new events; no listener changes |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A — see §5 |
| Media | N/A |
| Search | N/A |
| i18n | N/A — no new user-facing strings |
| Mobile | N/A — same chapter quote UI |
| Accessibility | N/A |
| Architecture boundaries | Story public API shrinks by one method; Quote is the only consumer. No new domain. |
| UI surface | No Blade change required — chapter show already uses author-only for the button |
| Performance | N/A — same shape of lookup, narrower filter |
| Data & migration | N/A — no schema change |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Mode | `auto` — request already answers the functional question |
| 2 | Remove `isAuthorOrCoAuthor`? | Yes; `isAuthor` is the single public author check |
| 3 | May beta readers quote? | Yes — intentional fix; they are readers |
| 4 | May authors / co-authors quote their story? | No (unchanged product rule) |
| 5 | Rename `Story::isAuthor` / `CollaboratorService::isAuthor`? | No — out of scope |
| 6 | Change collaborator roles or other permissions? | No — out of scope |

## 8. Out of scope

- Renaming `Story::isAuthor()` or `CollaboratorService::isAuthor()`.
- Any change to what collaborator roles exist or what they may do beyond quoting.
- Introducing `isCollaborator()` on the public API (only if a later caller needs it).
- Chapter UI changes (already author-only).
- The quotes author-view feature (`quotes-author-view/`) — this task is only its
  prequel API cleanup.

## 9. Open questions

None blocking.

Non-blocking: whether to add an explicit co-author-cannot-quote regression test
in addition to the beta-reader-can-quote pin (recommended; co-author = same
`author` role, so `isAuthor` already covers it).
