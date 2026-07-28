# Story — one author check, not two — request

Prequel refactoring, split out of
[`../quotes-author-view/`](../quotes-author-view/) (decision #20 of its
`DECISIONS.md`).

## What I want

Remove `StoryPublicApi::isAuthorOrCoAuthor()`. `isAuthor()` becomes the single
author check on the API.

## Why

"Co-author" does not mean anything in this design. Once an author names another
author, both hold identical rights — including the fact that neither can remove
the other. There is no *original* author to distinguish, so there is nothing for
a second method to express.

Worse, the name lies. `isAuthorOrCoAuthor()` delegates to
`StoryService::getCollaboratorIds()`, which plucks **every** row of the
`collaborators` relation — so it currently returns `true` for
`CollaboratorService::ROLE_BETA_READER` as well. It means "is any collaborator",
which is neither what it is called nor what its caller wants.

## Scope

It has exactly **one** caller in the codebase:

- `app/Domains/Quote/Private/Services/QuotePolicy.php:30` — `canQuote()`, which
  blocks authors from quoting their own story.

Everything else already uses `isAuthor()`, `Story::isAuthor()` or
`CollaboratorService::isAuthor()`.

## Deliberate behaviour change

Switching `canQuote()` to the author-only check means **beta readers become able
to quote a story they beta-read**. This is intended and confirmed by the user.
[`../Quotes.md`](../Quotes.md) decision #2 blocks "authors and co-authors" and
never mentions beta readers — blocking them was an accident of the misnamed
method, not a rule. A beta reader is a reader.

So this is a bug fix wearing a refactor's clothes, and it needs a test that
pins the new behaviour: a beta reader on a story **can** quote its chapters.

## Constraints

- Check whether any test asserts the current beta-reader-cannot-quote behaviour;
  if one does, it flips rather than being deleted.
- `app/Domains/Quote/AGENTS.md` documents `canQuote` as blocking
  "authors/co-authors at the story level" — that line needs rewriting.
- If a collaborator-wide check is genuinely needed somewhere later, it should be
  named for what it is (`isCollaborator()`), not reintroduced under this name.

## Explicitly out of scope

- Renaming `Story::isAuthor()` or `CollaboratorService::isAuthor()` — they are
  already correct.
- Any change to what collaborator roles exist or what they may do.
