# Quotes — private stories — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Quote** owns create policy and Citations filtering. **Story** already exposes
`filterUsersWithAccessToStory` and `isAuthor` — no Story behaviour change except
optional doc wording. No new domain.

### 1.1 Changes in other domains

**Story** — none required for behaviour. Optional: correct `StoryPublicApi`
docblock that says private is “authors only” when the filter uses all
collaborators (out of scope unless we touch that file; prefer leave alone).

**Quote** — extend `QuotePolicy::canQuote`; pin with feature tests; keep
`filterVisibleForViewer` as-is.

### 1.2 Sequencing with `story-author-check/`

**Already done** on this branch. Beta readers are not blocked by `isAuthor`.
This task does **not** absorb that refactor — only adds story-access to
`canQuote` and the missing private/community coverage.

## 2. Data model

None. No migrations.

## 3. PHP architecture

### 3.1 Public API

No new Quote or Story public methods. Reuse:

- `StoryPublicApi::isAuthor($userId, $storyId)`
- `StoryPublicApi::filterUsersWithAccessToStory([$userId], $storyId)`

### 3.2 Services

`QuoteService::create` unchanged aside from policy outcome.
`getForProfile` / `filterVisibleForViewer` unchanged.

### 3.3 Policy / authorization

`QuotePolicy::canQuote($storyId, $userId)` becomes:

1. Require `user-confirmed` (unchanged).
2. Refuse if `isAuthor($userId, $storyId)`.
3. **New:** require
   `in_array($userId, $storyApi->filterUsersWithAccessToStory([$userId], $storyId), true)`.

Same method feeds POST create and GET `/quotes` `can_quote`. Chapter Blade
toolbar stays author+confirmed only — the chapter page already 404s without
read access via `Gate::view`.

### 3.4 Events and listeners

No change. `ChapterPassageQuoted` + `NotifyAuthorsOnQuoteCreated` already fire
for any successful create (visibility-agnostic).

### 3.5 Routes, controllers, form requests

No change. Middleware `role:user-confirmed` stays on POST.

## 4. Frontend architecture

No Blade/JS changes.

## 5. Deptrac

No new edges. Quote → StoryPublicApi already allowed.

## 6. Testing strategy

Integration tests in Quote:

| Case | Expect |
|------|--------|
| Beta reader POST quote on **private** story | 201 |
| Confirmed non-collaborator POST on private story | 403 |
| Confirmed reader without community access? (community is confirmed-readable) — confirmed non-collaborator on private is the access denial case |
| Fellow beta viewer: private quote on quoter's Citations tab | entry present |
| Non-collaborator confirmed: private quote on Citations | omitted (existing test keep) |
| Private create → author receives `quote.chapter_quoted` | notification asserted |
| Unconfirmed cannot see Citations / community entries | existing tab gate; keep |

No vitest. VERIFY: light smoke optional; create+profile covered by tests.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to enforce story access on create? | A: `canQuote` · B: only controller · C: Story Gate in CreateQuoteRequest | A | Single source for create + `can_quote` JSON |
| 2 | Access API | A: `filterUsersWithAccessToStory` · B: new `canAccess` · C: call StoryPolicy | A | Already used for profile; same rules as read |
| 3 | Change profile filtering? | A: keep · B: rewrite | A | Already matches spec |
| 4 | Redo story-author-check here? | A: no · B: absorb | A | Already landed |
| 5 | Mod override on Citations? | A: no · B: yes | A | Spec / DECISIONS #6 |
| 6 | Performance / pagination refactor? | A: out of scope · B: batch access API now | A | Bugfix size |

## 8. File layout

```
app/Domains/Quote/Private/Services/QuotePolicy.php
app/Domains/Quote/Tests/Feature/QuoteControllerTest.php   # private create / 403
app/Domains/Quote/Tests/Feature/QuoteProfileControllerTest.php  # fellow beta sees
app/Domains/Quote/Tests/Feature/QuoteLifecycleTest.php     # private notify
app/Domains/Quote/AGENTS.md                                # if tracked / local
```

## 9. Risks acknowledged

- `filterUsersWithAccessToStory` does not grant moderators access — intentional
  for Citations; moderators reading a private chapter via admin UI still cannot
  forge-create a quote unless they are collaborators (same as confirmed strangers).
- Duplicate story/chapter fetches between filter and DTO build remain; accept for
  this fix.
