# Discord fanart API: approval timestamp — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 1 /5 · **Mode:** auto

## 0. Handoff

### BUILD phase 1 → WRAP (2026-10-25)

Shipped as planned, no deviation.

### DESIGN+PLAN → BUILD (2026-10-25)

`fanarts.published_at` already is the approval time: `FanartService` sets it to `now()` on review approval (~line 248) and, for author artworks, at upload (~line 91, which is the "submission date" the request wants). So no schema or write-path change; only expose it. Assumption most likely reversed: field name `approvedAt` (ISO 8601). Existing tests for pending items live in `Tests/Feature/DiscordFeedTest.php` (helper `sharedImage`, `botGetPending`).

### REFINE → DESIGN+PLAN (2026-10-25)

0 blocking questions. Fast path taken (rated 1); §2 and §3 written by the refiner.

## 1. Functional spec

### 1.1 Overview

The bot-facing `GET /api/discord/fanarts/pending` feed adds, to each item, the date the fanart was approved. For an artwork uploaded by a story author (no review), that date is the upload (submission) date.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| `approvedAt` | ISO 8601 timestamp of the fanart's publication: review approval, or submission for author artworks |

### 1.3 Roles & visibility

Unchanged: the endpoint is reachable only with the bot token (`discord.api`). No user-facing surface.

### 1.4 Functional requirements

1. Each item of `data` in `/api/discord/fanarts/pending` carries `approvedAt`.
2. Reader fanart: `approvedAt` is when an author approved it.
3. Author artwork (published without review): `approvedAt` is when it was uploaded.
4. Eligibility, ordering (oldest `published_at` first) and the other fields are unchanged.

### 1.5 Lifecycle

Read-only exposure of an existing column; nothing new to cascade.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — bot-token endpoint |
| Visibility / privacy | Only items already exposed; a timestamp leaks nothing new |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A — see 1.5 |
| Media | N/A |
| Search | N/A |
| i18n | N/A — API, no wording |
| Mobile | N/A |
| Accessibility | N/A |

### 1.7 Out of scope

Displaying the date in the web UI; changing the `mark-sent` endpoint; adding a date to other Discord feeds; backfilling (published fanarts all have `published_at`).

### 1.8 Open questions

None blocking.

## 2. Architecture

`FanartService::discordPending()` (domain Fanart) adds `'approvedAt' => $fanart->published_at->toISOString()` to each item array, using the existing `published_at` datetime cast. Eligibility already requires a published status, so it is non-null; guard with `optional(...)` is unnecessary. No table, route, event, or deptrac change. Docs: add the field to the example and the explanatory paragraph in `docs/Discord_Api_Usage.md`.

Testing: extend `DiscordFeedTest.php` with a reader fanart (assert `approvedAt` equals `published_at`) and an author artwork created through the upload flow (assert `approvedAt` equals the submission time).

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Expose `approvedAt` in pending feed + docs | S | — | DONE |

### Phase 1 — Expose `approvedAt`

**Goal.** Pending items carry the approval timestamp. **Reads.** §2.

**Deliverables.** `app/Domains/Fanart/Private/Services/FanartService.php` (item array); `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php`; `docs/Discord_Api_Usage.md` (example JSON + paragraph below it).

**Tests.** `it('exposes the approval date of a reader fanart')`; `it('uses the submission date as approval date for an author artwork')`.

**Acceptance.**
- ✅ Both tests written first, failing, then passing.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

N/A — API only.

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | Field named `approvedAt`, camelCase like sibling fields, ISO 8601 UTC via `toISOString()` | REFINE | Yes |
| 2 | `published_at` is the approval time; author artwork upload sets it to upload time, matching the request, so no write-path change | REFINE | Yes |
| 3 | Only the pending feed gains the field | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE: `approvedAt` exposed in pending feed, two tests, docs updated; gate green.
