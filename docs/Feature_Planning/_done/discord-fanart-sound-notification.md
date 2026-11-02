# Notify Discord when audio fanart is published

**Status:** DONE — 2026-11-02 · **Domain(s):** `Fanart`

## What it does

Sound fanarts now use the same opt-in « Partager sur Discord » checkbox as images. A ticked, published sound on a public story and a published chapter appears in the Discord bot's pending fanart feed as a link-only item: the sound file is never exposed. Same endpoints (`GET /api/discord/fanarts/pending`, `POST /api/discord/fanarts/mark-sent`), no table, no migration, no deptrac edge, no new lang string.

## Key behaviour

- The checkbox is shown on the reader submission form and the author artwork upload form, for both kinds (`pages/create.blade.php`, one view for both flows). Unchecked by default, restores `old()` after a validation error. No checkbox on the edit page (as for images): the choice is fixed at submission.
- `share_on_discord` is stored for every kind. `StoreFanartRequest`, `SubmitFanartData` and `FanartController` already carried it and are unchanged.
- Feed eligibility: published, `share_on_discord = true`, not yet sent, public story, published chapter. Pending, rejected, unticked, community/private, already-sent and deleted items are not listed. Oldest first, images and sounds mixed.
- Items gain `kind` (`"image"` | `"audio"`). Audio items have no `imageUrl` and no file link, and always have a chapter.
- Deviation from the original design, added at the user's request: image and audio items also expose `storyId`, `storySlug`, `chapterId` and `artist.name` (additive; documented in `docs/Discord_Api_Usage.md`).
- Trap: `FanartService::isDiscordEligible()` guards the signed **image** route and stays image-only (`discordEligible($id, [FanartKind::Image])`), so the route 404s for audio. `discordEligible()` takes a `$kinds` list, defaulting to image + audio for the feed.
- A sound replaced by a newer one on the same chapter is deleted; if not yet sent it drops out, and the new one is announced only if ticked.
- Deletion, deactivation and chapter/story deletion remove the item from the feed; nothing is retracted from Discord once sent.
- No backfill: existing published sounds are never announced.

## Where the code lives

| Concern | Path |
|---------|------|
| Service (feed, eligibility, writers) | `app/Domains/Fanart/Private/Services/FanartService.php` (`submit`, `uploadArtwork`, `discordEligible`, `isDiscordEligible`, `discordPending`) |
| View | `app/Domains/Fanart/Private/Resources/views/pages/create.blade.php` |
| Tests | `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php`, `SubmitFanartTest.php`; helper `sharedAudio()` in `Tests/helpers.php` |
| Docs | `docs/Discord_Api_Usage.md` (Fanarts feed), `app/Domains/Fanart/README.md` (Discord relay, "Deliberately not built") |

## Extension points used

None new.

## Decisions worth remembering

- D1: the fanartist chooses (same checkbox as images), rather than announcing every published sound. Reverses the initial "announce all" assumption.
- D2: same endpoint, items gain `kind`, audio omits `imageUrl`.
- D3: no backfill, the app is not live in production yet.
- A5: same label for sounds as for images, with no hint that only the announcement (not the sound) goes to Discord. Reversible with one lang key and a `@if ($kind === 'audio')` line.

## Not done

- Non-goals: playing or downloading the sound on Discord; changing the choice after submission; retroactive announcement; bot-side message formatting (separate repo).
- Bot support lives outside this repo. Deploy order: bot support for items without `imageUrl` first, or it may crash on the first ticked sound.
- Temporary e2e spec `e2e/tests/features/discord-fanart-sound-notification.spec.ts` deleted (nothing worth promoting). The `kind` argument and `shareOnDiscord` locator added to `FanartCreatePage` had no other caller and were removed.
- The e2e worktree port mismatch (host 8081 mapped to container 8080 while `playwright.config.ts` serves on `E2E_PORT`) is already tracked as item 4 of `loop-improvements`; not re-filed.
- Visual QA (checkbox shown and unchecked on both sound forms, still ticked after an invalid submit, image forms unchanged, 375 px width) passed. Wrapping at mobile width was judged by bounds only, not by eye.
