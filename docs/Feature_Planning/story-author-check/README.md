# Story — one author check, not two

> WRAP output — the compact record of the finished feature. **This is the only
> file in the folder an agent should load by default.** The phase documents
> (`01`–`03`) remain as history; link to them from here when detail is needed.
>
> Target: under ~120 lines. If it grows past that, cut prose, not facts.

**Status:** DONE — 2026-07-28 · **Domain(s):** `Story`, `Quote` · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

Removes the misnamed `StoryPublicApi::isAuthorOrCoAuthor()`. Authorship on the
Story public API is only `isAuthor()`. Quote’s `canQuote` uses that check, so
**beta readers can quote** chapters of stories they beta-read; authors
(including co-authors — same `author` role) still cannot.

## Key behaviour

- `canQuote`: `user-confirmed` and **not** an author of the story.
- Beta readers (`role = beta-reader`) are readers for quoting — intentional.
- Co-authors are authors (`role = author`); there is no separate co-author role.
- No schema or lifecycle change; evaluated at request time.
- Chapter UI already gated the quote button with author-only `$vm->isAuthor`;
  policy now matches (previously beta readers saw “Citer” then got 403).

## Where the code lives

| Concern | Path |
|---------|------|
| Public API | `app/Domains/Story/Public/Api/StoryPublicApi.php` (`isAuthor` only) |
| Service / policy | `app/Domains/Quote/Private/Services/QuotePolicy.php` |
| Controllers / routes | unchanged |
| Views / components | unchanged (`chapters/show.blade.php` already author-only) |
| JS | unchanged |
| Tests | `app/Domains/Quote/Tests/Feature/QuoteControllerTest.php` |
| Migrations | none |

## Extension points used

None.

## Decisions worth remembering

- “Co-author” is not a distinct role — both hold `author`.
- Do not revive `isAuthorOrCoAuthor`; if a collaborator-wide check is needed,
  name it `isCollaborator()` (not added here).
- `StoryService::getCollaboratorIds()` left private even though unused by the
  public API after this change.

## Not done

- Renaming `Story::isAuthor()` / `CollaboratorService::isAuthor()` — out of scope.
- Public `isCollaborator()` — deferred until a real caller exists.
- `quotes-author-view/` — separate feature; was blocked on this prequel (still
  needs `chapters-multi-edit/`).
