# Discord fanart API: approval timestamp

**Status:** DONE — 2026-10-25 · **Domain(s):** `Fanart`

## What it does

Each item of the bot feed `GET /api/discord/fanarts/pending` now carries `approvedAt`, an ISO 8601 UTC timestamp. No schema, route, event or write-path change: it only exposes the existing `fanarts.published_at`.

## Key behaviour

- `approvedAt` = `published_at`: set to `now()` when an author approves a reader fanart, and at upload for author artworks (published without review), so for those it is the submission date.
- Always non-null: eligibility already requires a published status.
- Eligibility, ordering (oldest `published_at` first) and the other fields are unchanged. Only the pending feed gains the field.
- Bot-token endpoint (`discord.api`); no user-facing surface.

## Where the code lives

| Concern | Path |
|---------|------|
| Item array | `app/Domains/Fanart/Private/Services/FanartService.php` (`discordPending()`, `'approvedAt' => $fanart->published_at->toISOString()`) |
| Tests | `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php` (reader fanart, author artwork) |
| API docs | `docs/Discord_Api_Usage.md` (example JSON + paragraph) |

## Decisions worth remembering

- Field name `approvedAt` (camelCase like sibling fields), via `toISOString()`.
- Plan and code agree; no deviation.

## Not done

- Non-goals: showing the date in the web UI; changing `mark-sent`; adding a date to other Discord feeds; backfilling (all published fanarts have `published_at`).
- Cut mid-build: nothing. Open questions: none.
- No e2e spec was left by this task. No new backlog rows.
