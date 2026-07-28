# Quotes — private stories — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads one phase at a time.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Access gate + private/community tests | S | — | DONE |

## Working agreement

- One phase = one commit. Failing test first. Gate green before DONE.

---

## Phase 1 — Access gate + private/community tests

**Goal.** Confirmed readers with Story access (incl. beta on private) can quote
and notify; create without access is 403; Citations shows private entries only
to viewers with access.

**Deliverables.**
- `QuotePolicy::canQuote` — add `filterUsersWithAccessToStory` check after
  confirmed + not-author.
- Feature tests (paths above in architecture §8).
- Local `Quote/AGENTS.md` invariant if present: `canQuote` also requires story
  access; authors via `isAuthor`.

**Tests.**
- `beta reader can quote a private story`
- `confirmed user without story access cannot quote a private story`
- `fellow beta reader sees private-story quote on quoter Citations tab`
- `quoting a private story notifies the author` (extend lifecycle / notification test)

**Acceptance.**
- ✅ Private create by beta → 201 + DB row + author notified.
- ✅ Private create without access → 403 + no row.
- ✅ Fellow beta sees entry on profile; stranger confirmed does not (existing).
- ✅ `npm run gate` green.

---

## Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Private chapter as beta reader | Citer works; quote saves | ✅ feature tests (create + notify) |
| Citations as fellow beta | Private quote visible on quoter's tab | ✅ `fellow beta reader sees…` |
| Citations as confirmed stranger | Private quote absent | ✅ existing inaccessible-chapter test |
| N/A — no new UI chrome | — | ✅ |

## Open items

None — `story-author-check/` done; APIs confirmed.
