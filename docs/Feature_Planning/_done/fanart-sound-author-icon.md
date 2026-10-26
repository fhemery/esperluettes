# Fanart sound author icon

**Status:** DONE — 2026-10-26 · **Domain(s):** `Fanart` (uses `Profile` public API)

## What it does

Beside each fanart sound player a small `account_circle` icon links to the
artist's public profile, tooltip and aria-label « Par <nom> ». Two surfaces:
the chapter page player (`chapter-audio`, between player and report button) and
the story page's single player (`story-player`).

## Key behaviour

- No new visibility rule: the icon shows exactly when the player does
  (confirmed users only). Shown on the user's own sound too.
- Artist = fanart submitter (`fanarts.user_id`).
- If the artist's profile cannot be resolved (deleted/deactivated) no icon is
  rendered; the player is unaffected.
- Story page: one `<audio>` serves all chapters, so the icon follows the
  currently playing chapter (store `current()`), hidden while nothing plays.

## Where the code lives

| Concern | Path (`app/Domains/Fanart/`) |
|---------|------------------------------|
| Playlist enrichment | `Private/Services/FanartService.php::audioPlaylist` (`artistName`, `artistUrl`) |
| Components | `Private/View/Components/ChapterAudio.php`, `StoryPlayer.php` (entries get `artistLabel`, `artistUrl`) |
| Views | `Private/Resources/views/components/chapter-audio.blade.php`, `story-player.blade.php` (`data-artist-link`) |
| Store | `Private/Resources/js/fanart-player.js` `current()` (+ `.test.js`) |
| Lang | `Private/Resources/lang/fr/ui.php` `player.artist` = « Par :name » |
| Tests | `Tests/Feature/ChapterPageFanartTest.php`, `StoryPlayerTest.php` |

## Decisions / counter-intuitive

- One batched `getPublicProfiles` inside the memoised `audioPlaylist`; never a
  profile lookup per row. Playlist consumers: `ChapterAudio`, `StoryPlayer`,
  `ChapterAudioIcon`.
- The label is built server-side (`artistLabel`) and passed in the entry to
  avoid JS i18n.
- URL = `route('profile.show', ['profile' => slug])`, same as the viewer.
- Code matches plan; no drift. No data, event, notification or deptrac change.

## Not done

- Non-goals: icon on profile-tab audio rows, viewer modal, moderation snapshot,
  audio miniatures; avatar/name text or popover; co-artists.
- e2e: the temporary feature spec (3 browser checks, all passed at VERIFY) was
  deleted, not promoted; its `artistLink` page-object accessor removed.
- No open questions, no new backlog rows.
