# Fanart Domain

## Purpose

Confirmed readers (« fanartistes ») submit images (jpg/png/webp, 2 Mo) and sounds (mp3, 15 Mo) inspired by a story; the story's authors review them (any one author decides) and may also upload their own « artworks », which are published at once and cost one chapter credit. Files are confirmed-users-only: they live on the **private** Media disk and are streamed by gated routes, never linked publicly. Guests and non-confirmed users see nothing of the feature.

## Key concepts

- **Fanart**: one row per image or audio. An image may target a chapter or the whole work (`chapter_id` null); a sound always targets a chapter, and a chapter has at most one published sound (a replacement deletes the previous one and notifies its artist).
- **Status**: `pending` (visible to the artist, the authors and staff) then `published` on acceptance. Declining or withdrawing hard-deletes.
- **Submission modes**: each author sets, in Paramètres › Histoires, whether images and sounds are open to everyone (`yes`), reserved to designated fanartistes (`designated`) or closed (`no`). On a co-authored story the most restrictive value applies (`ModeService`).
- **Fanartiste role**: lives here (`fanart_fanartists`), not in Story's collaborators; it grants no access to private stories. Readers may request it; an author accepts or declines (30-day cooldown after a decline).
- **Visibility**: readers see published fanarts of published chapters on readable stories; authors and staff also see pending ones and unpublished chapters' fanarts. Chapters not yet read are collapsed behind a spoiler notice.
- **Message to the authors**: a submitter may add an optional plain-text message (500 chars, not offered on author artworks). It is not stored on `fanarts`; it lives only in the authors' `fanart.submitted` notification, so deleting that notification loses it.
- **Discord relay**: published images of public stories, opt-in at submission, exposed to the bot through signed, expiring image URLs.

## Where it shows up

| Surface | Component / route |
|---|---|
| Story page | `<x-fanart::story-tabs>` (Chapitres / Fanarts, lazy-loaded), `<x-fanart::story-actions>` (inside the Fanarts tab: one button per kind, « Proposer une image / un son » or « Ajouter un artwork (image / son) », routes `fanart.create` per kind), `<x-fanart::request-role-button>`, `<x-fanart::story-player>` + `<x-fanart::chapter-audio-icon>` |
| Chapter page | `<x-fanart::chapter-audio>` (artist icon link « Par <nom> » before the report button), `<x-fanart::chapter-images>` |
| Collaborators page | `<x-fanart::collaborators-section>` |
| Profile | tab `fanarts` (`<x-fanart::profile-tab>`), visible by default; the `hide-fanart-tab` privacy setting (Paramètres › Profil, « Masquer mes fanarts », unchecked by default) restricts it to the owner and staff |
| Shared pieces | `<x-fanart::thumbnail>`, `<x-fanart::viewer-modal>` (focus-trapped dialog, Escape closes) |

## Data model

| Table | Model | Notes |
|---|---|---|
| `fanarts` | `Fanart` | `kind` (`FanartKind`), `status` (`FanartStatus`), `chapter_id`, `is_artwork`, `share_on_discord`. `SoftDeletes` is used **only** for user deactivation; every other removal is a hard delete. |
| `fanart_fanartists` | `Fanartist` | Composite key `(story_id, user_id)`, only `created_at`. |
| `fanart_role_requests` | `FanartRoleRequest` | `pending` / `declined`. Accepted requests are deleted. |

No foreign keys: every reference points at Story or Auth tables.

## Files and GC

Fanart never deletes a file; it only clears rows. `FanartMediaUsageProvider` claims `file_path` of **every** row, soft-deleted included (so a reactivated account finds its files), and `media:gc` collects the rest. Files live under the private scope `fanart/{storyId}`.

## Cross-domain delegation

| Concern | Delegated to |
|---|---|
| Storage, variants, streaming helpers, GC | Media (`MediaPublicApi`, usage registry) |
| Story, chapter, author and read-state lookups | Story (`StoryPublicApi`) |
| Notifications (ten `fanart.*` types) | Notification registry |
| Reporting and moderator deletion | Moderation topic `fanart` |
| Preferences (modes, profile-tab privacy) | Settings registry |
| Lifecycle (user deactivated / deleted, story / chapter deleted) | Listeners on Auth and Story events |
| Discord feed | `discord.api` guarded endpoints |

## Deliberately not built

No statistics, no submission cap, no editing of a published fanart by its artist (delete and resubmit), no follower notification on publication, no global search, no GIF or non-mp3 audio, no audio on Discord, no player that survives page navigation, no comments or likes on fanarts, nothing shown to guests or non-confirmed users (no teaser).

Sound download protection is friction only: players hide the native download entry and context menu, and opening a sound URL by navigation answers 403. It does not stop a determined user (devtools, curl with a session cookie, screen capture); there is no DRM, watermark or signed URL.

## Known gaps

- The lazy fragments (tab, chapter groups, profile groups, viewer) load through Shared's `fetchFragment`, so a CGU redirect navigates the window and an expired session reloads the page. But `compliant` stores the fragment URL as the post-CGU intended destination: after accepting, the user lands on raw fragment HTML (Auth middleware, not fixed here).
- Fanart report reasons are admin-created; production needs them before go-live (`ModerationSeeder` only seeds an « Other » reason for dev/e2e).
- Sounds go up to 15 Mo: the production front-end and PHP upload limits must allow ≥ 16 MB. Not checkable from the repo.
