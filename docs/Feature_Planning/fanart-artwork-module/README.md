# New fanart / artwork module

**Status:** DONE — 2026-10-18 · **Domain(s):** `Fanart` (new), `Story`, `Media`, `Moderation` · **Spec:**
[functional](./01-functional.md) · [architecture](./02-architecture.md) ·
[plan](./03-plan.md) · [decisions](./DECISIONS.md)

## What it does

Confirmed readers submit images (jpg/png/webp ≤ 2 Mo) and chapter readings (mp3 ≤ 15 Mo)
to other people's stories; any one author accepts or declines them. Authors post their own
« artworks », published at once for one chapter credit. Fanarts show in a « Fanarts » tab on
the story page, as an audio player + image block on the chapter page, as speaker icons with
an auto-advancing player in the chapter list, and in a profile tab. Reportable (file copy kept
with the report), optionally relayed to Discord. Domain docs: `app/Domains/Fanart/README.md`
and `AGENTS.md` — read those before changing anything.

## Key behaviour

- Visibility: confirmed users + staff only; guests and `user` see nothing (no tab, no icon, no widget). Files live on the private Media disk, streamed by gated routes.
- Readers see published fanarts of published chapters on stories they can read; authors and staff also see pending items and unpublished chapters' fanarts. Unread chapters are collapsed behind a spoiler notice (never for authors).
- Mode d'accueil per author (Paramètres › Histoires): images default `yes`, audio default `designated`; co-authored story → most restrictive (`ModeService`). Changing it never touches existing items.
- Fanartiste role is Fanart's own table (`fanart_fanartists`), **not** a collaborator role: no private-story access. Requests: one pending per user/story, 30-day cooldown after a decline.
- One published audio per chapter: accepting/posting a new one deletes the previous and notifies its artist (`replacePublishedAudio`).
- Private story: no new submission; existing items stay, pending can still be decided.
- Deactivated fanartist → their rows soft-deleted, restored on reactivation. Everything else (decline, withdraw, any delete, chapter/story/account deletion) is a hard delete; files are left to `media:gc`.
- Deactivated author → handled by Story: the story is soft-deleted (so its fanarts unreadable) only once **all** its authors are deactivated — Story's rule was changed for this (DECISIONS #32). Account deletion still hard-deletes every co-authored story and its fanarts (#33).
- Approving a report never deletes the fanart; moderators delete from the fanart's moderation menu (authors + artist notified, `Fanart.DeletedByModeration` emitted).
- Profile tab private by default: setting « Masquer mes fanarts » (`hide-fanart-tab`, Paramètres › Profil, checked by default).

## Where the code lives

| Concern | Path |
|---------|------|
| Domain docs | `app/Domains/Fanart/README.md`, `AGENTS.md` |
| Services | `app/Domains/Fanart/Private/Services/` (`FanartService`, `ModeService`, `FanartistService`, `FanartNotifier`) |
| Policy | `app/Domains/Fanart/Private/Policies/FanartPolicy.php` |
| Routes | `Private/web.routes.php` (loaded in `register()`), `Private/api.routes.php` (Discord) |
| Controllers | `Private/Controllers/` — fragments (HTML), files, review, artwork/store, fanartistes, role requests, moderation, Discord |
| Components | `Private/View/Components/` + `Private/Resources/views/components/` (`<x-fanart::…>` tags used in Story views) |
| JS | `Private/Resources/js/fanart-player.js` (Alpine store, vitest), `fanart-viewer.js` |
| Migrations | `Database/Migrations/2026_10_20_00000{1,2,3}_*` — `fanarts`, `fanart_fanartists`, `fanart_role_requests` |
| Tests | `app/Domains/Fanart/Tests/Feature/` (22 files) + `Unit/FanartModeTest.php` |
| Story additions | `StoryPublicApi`: `filterReadableStoryIds`, `canRead`, read-chapter ids, `getChaptersForStor{y,ies}`, chapter credits (atomic `spendChapterCredit`) |
| Media additions | private image variants, `streamVariant`, `copyPrivate`, scopes `fanart/{storyId}`, `moderation/{topicKey}` |
| Moderation additions | `_media` snapshot key: files copied at report time, GC-claimed while the report exists, moderator stream route |

## Extension points used

- Profile tab `fanarts` (order 55) with `FanartTabVisibility`.
- Settings: section `fanart` in Story's `stories` tab; `hide-fanart-tab` in `profile` › `privacy`.
- Notifications: group `fanart`, ten `fanart.*` types (`docs/notification-types.md`).
- Moderation topic `fanart` + `FanartSnapshotFormatter`; listener on `Moderation.ReportSubmitted`.
- Media usage registry: `FanartMediaUsageProvider` (reads `withTrashed()`).
- Events: listens to `Auth.UserDeleted/Deactivated/Reactivated`, `Story.Deleted`, `Chapter.Deleted`; emits `Fanart.DeletedByModeration`.
- Discord: `GET /api/discord/fanarts/pending`, `POST …/mark-sent`, signed 24 h image URL (`docs/Discord_Api_Usage.md`).
- Story views include `<x-fanart::…>` tags only (no PHP edge Story → Fanart).

## Decisions worth remembering

- Separate `Fanart` domain, no `FanartPublicApi`; Story only gained read/credit methods (Quote precedent).
- Fanartiste role kept out of `story_collaborators` (#28).
- Unpublished chapters, author deactivation and story visibility are **read-time filters** (`Fanart::visibleTo`, `filterReadableStoryIds`), not listeners (A13).
- Report keeps a copy of the file via a generic Moderation `_media` capability (#30); approval does not delete (#31).
- Author reporting a *pending* fanart = decline without comment (A1, listener).

## Code vs. plan / spec

- Spec 4.2.2 says granting the role sends Story's existing "role given" notification; the code sends Fanart's own `fanart.role_given` (consequence of #28).
- Spec §6 lists listeners for chapter unpublished/published and story visibility changes; none were built — read-time filtering covers them (A13).
- Spec §6 Moderation row still says "report approval deletes the fanart": superseded by #31, code does not.
- Architecture's route middleware `web, auth, verified` became the project convention `web, auth, compliant, role:user-confirmed,moderator,admin,tech-admin` (plan O3).
- Architecture §8's `FanartModerationReasonSeeder` was not built; `ModerationSeeder` seeds an « Other » for dev/e2e, production reasons are admin-created (plan O4).
- Discord feed also excludes fanarts of unpublished chapters (not in the spec).
- Plan's QA row names the setting « Masquer l'onglet Fanarts »; the shipped label is « Masquer mes fanarts » (same meaning: checked = private). Kept as is.

## Not done

- **Non-goals (spec §8):** statistics, submission cap, artists editing published fanarts, follower notifications, global search, GIF / non-mp3, audio on Discord, cross-page persistent player, comments/likes, teaser for non-confirmed users.
- **Cut mid-build:** nothing.
- **E2E:** `e2e/tests/features/fanart-artwork-module.spec.ts` and `e2e/pages/FanartStoryPage.ts` deleted at WRAP (feature-local; PHP tests cover rules). `E2eFanartSeeder` and the `FANART` fixture kept as e2e data.
- **Pushed to the backlog:**
  - [`fanart-fragment-redirects`](../fanart-fragment-redirects/00-request.md) — a user who has not accepted the CGU gets the CGU page injected into the tab / viewer modal.
  - [`shared-dark-heading-contrast`](../shared-dark-heading-contrast/00-request.md) — magenta `<x-shared::title>` headings dim in dark mode (site-wide).
  - [`story-deactivated-coauthor-label`](../story-deactivated-coauthor-label/00-request.md) — English « Unknown » for a deactivated co-author on the collaborators page.
  - Already tracked: [`fix-404-page-theme-500`](../fix-404-page-theme-500/00-request.md) (404 pages answer 500).
  - Production steps (fanart report reasons, ≥ 16 MB upload limits, bot maintainer) added to [`release-notes`](../release-notes/00-request.md).
- **Open:** plan O9 — staff reporting a fanart depends on Moderation's report-form route, which only allows `user` / `user-confirmed`; not verified.
