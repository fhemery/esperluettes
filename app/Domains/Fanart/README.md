# Fanart Domain

## Purpose

Readers (« fanartistes ») submit images and sounds inspired by a story; the story's authors review them and may also upload their own « artworks ». Files are confirmed-users-only: they live on the **private** Media disk and are streamed by gated routes, never linked publicly.

**Status: skeleton.** Only the data layer exists so far — tables, models, enums, and the Media usage claim. No route, policy, service, notification or UI is wired yet; later phases of the epic add them.

## Data model

| Table | Model | Notes |
|---|---|---|
| `fanarts` | `Fanart` | One row per image or audio. `kind` (`FanartKind`: image/audio), `status` (`FanartStatus`: pending/published), `chapter_id` null = whole work (images only), `is_artwork` = submitted by an author. `SoftDeletes` is used **only** for user deactivation; every other removal is a hard delete. |
| `fanart_fanartists` | `Fanartist` | Fanartiste membership of a story. Composite key `(story_id, user_id)` (unique), only `created_at`. |
| `fanart_role_requests` | `FanartRoleRequest` | Requests for the fanartiste role (`pending` / `declined`). Accepted requests are deleted. |

No foreign keys: every reference points at Story or Auth tables.

## Files and GC

Fanart never deletes a file; it only clears rows. `FanartMediaUsageProvider` claims `file_path` of **every** row, soft-deleted included (so a reactivated account finds its files), and `media:gc` collects the rest. Files live under the private scope `fanart/{storyId}`.

## Not done yet

Everything behavioural: settings and mode resolution, authorization policy, submit/review/delete flows, notifications, lifecycle listeners, moderation topic, UI, Discord feed.
