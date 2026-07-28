# Story — one author check, not two — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Story** owns the public authorship predicate. **Quote** is the only consumer
and owns `canQuote` policy wording/docs. No new domain.

### 1.1 Changes in other domains

**Quote** — `QuotePolicy::canQuote()` calls `StoryPublicApi::isAuthor($userId, $storyId)` instead of `isAuthorOrCoAuthor($storyId, $userId)`. Update `Quote/AGENTS.md`. Add feature tests for beta-reader and co-author quoting.

**Story** — delete `StoryPublicApi::isAuthorOrCoAuthor()`. Leave `StoryService::getCollaboratorIds()` private as-is (still useful internally if anything uses it; do not remove unless unused after the public method goes).

## 2. Data model

### 2.1 Tables

None. No schema change.

### 2.2 Model

None.

### 2.3 Lifecycle rules

N/A — evaluated at request time from existing `story_collaborators` roles.

## 3. PHP architecture

### 3.1 Public API

- **Keep** `StoryPublicApi::isAuthor(int $userId, int $storyId): bool`
- **Remove** `StoryPublicApi::isAuthorOrCoAuthor(int $storyId, int $userId): bool`
- Do **not** add `isCollaborator()` in this task

### 3.2 Services

No new services. `QuotePolicy` remains the authorization helper for Quote.

### 3.3 Policy / authorization

`QuotePolicy::canQuote`:

1. Require `user-confirmed` (unchanged).
2. Return `!$this->storyApi->isAuthor($userId, $storyId)` — note argument order matches `isAuthor`, not the removed method.

Enforcement stays server-side in `QuoteService::create` (403) and in list DTO `canQuote` for the UI.

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

None.

## 4. Frontend architecture

No Blade/JS changes. Chapter show already gates the quote button with author-only `$vm->isAuthor`.

## 5. Deptrac

No new edges. Quote → Story public API already allowed.

## 6. Testing strategy

Integration (feature) tests in Quote:

- Existing: author cannot quote own story — keep.
- New: confirmed beta reader **can** POST a quote on a chapter of that story.
- New: confirmed co-author (`role = author`, second user) **cannot** quote.

No StoryPublicApi unit test required for the deleted method; no vitest.

VERIFY: optional smoke — beta reader sees Citer and can create a quote (policy/UI now aligned). No dedicated UI surface beyond that.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Leave `getCollaboratorIds` on StoryService? | A: keep private · B: delete if unused | A | Out of scope to hunt internal callers; public lie is what we remove |
| 2 | Add public `isCollaborator()` now? | A: no · B: yes | A | Spec: only when a real caller exists |
| 3 | Fix chapter Blade as well? | A: no change · B: switch to policy endpoint | A | Already author-only; matches target behaviour |
| 4 | Co-author regression test? | A: add · B: skip (covered by author) | A | Cheap pin; documents co-author = author role |

## 8. File layout

```
app/Domains/Story/Public/Api/StoryPublicApi.php          # remove isAuthorOrCoAuthor
app/Domains/Quote/Private/Services/QuotePolicy.php       # call isAuthor
app/Domains/Quote/AGENTS.md                              # rewrite canQuote line
app/Domains/Quote/Tests/Feature/QuoteControllerTest.php  # or sibling — beta + co-author cases
```

## 9. Risks acknowledged

- Stale references in old planning docs (`Quotes_Architecture.md`, etc.) may still name `isAuthorOrCoAuthor` — leave them; they are historical, not runtime.
- Argument-order swap when changing the call site is a footgun — review the one-line change carefully.
