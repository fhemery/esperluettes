# Discourage downloading of fanart sounds

**Status:** DONE — 2026-10-25 · **Domain(s):** `Fanart`

## What it does

Makes it harder to save or share the raw mp3 of a fanart sound. All four sound
players go through one component that hides the native download entry, the
context menu and drag. The file route refuses sounds opened by navigation
(403) and sends them `no-store`. **Friction only, not protection.**

## Key behaviour

- Applies to every role, including the artist, story authors, moderators, admins (no exemption, unlike the chapter text copy protection). No setting or toggle.
- Authorization is unchanged (confirmed users and staff only); the check is added after it.
- `FanartFileController::original`, audio only: `Sec-Fetch-Dest` present and not `audio`/`video` gives `abort(403)`. Header absent passes (fail open, so old browsers keep playing).
- Audio responses use `Cache-Control: private, no-store` (`AUDIO_HEADERS`); images keep `private, max-age=3600` and no header check. Streaming (Range, 206) still goes through `MediaPublicApi::stream`.
- Counter-intuitive: any page-JS `fetch()` of a sound sends `Sec-Fetch-Dest: empty` and is also 403. Only `<audio>` loads work; do not fetch sounds into blobs.
- The raw `<audio>` in `moderation/snapshot.blade.php` was left alone on purpose (moderator-only, not one of the four surfaces).

## Limits (state them, do not promise more in the UI)

- Friction, not DRM. Devtools network tab, curl/wget with the session cookie, and screen/audio capture all still work.
- Safari/iOS may ignore `controlsList`.
- The real native Chrome three-dot menu was not eyeballed (headless Chromium cannot show it); the spec asserted `controlsList` contains `nodownload`, `contextmenu` is `defaultPrevented`, and a real right click does nothing. Left for a human on desktop Chrome.
- Revisit with short-lived signed URLs only if abuse is reported.

## Where the code lives

| Concern | Path |
|---------|------|
| Header check + no-store | `app/Domains/Fanart/Private/Controllers/FanartFileController.php` |
| Component | `app/Domains/Fanart/Private/Resources/views/components/protected-audio.blade.php` (`<x-fanart::protected-audio>`) |
| Surfaces using it | `components/story-player.blade.php` (keeps `x-ref`, `x-on:ended`, no `src`), `components/chapter-audio.blade.php`, `fragments/viewer.blade.php`, `fragments/profile-group.blade.php` |
| Tests | `Fanart/Tests/Feature/FanartFileRoutesTest.php`, `StoryPlayerTest`, `ChapterPageFanartTest`, `FanartFragmentsTest`, `FanartProfileTabTest` |
| Domain docs | `app/Domains/Fanart/README.md` ("Deliberately not built"), `AGENTS.md` |

The component renders `<audio controls controlsList="nodownload noplaybackrate" oncontextmenu="return false" ondragstart="return false">`.
Inline handlers on purpose: viewer and profile groups are lazy fragments, Alpine would not be bound.
Any new sound player must use the component, or it stays leaky.

## Extension points used

None. No deptrac edge, no migration, no new strings, no Media change.

## Decisions worth remembering

- `Sec-Fetch-Dest` rather than signed/cookie URLs: zero state, no schema. Flip to signed URLs if curl-with-cookie matters.
- Fail open on a missing header: never break playback.
- No exemption for the artist or authors (A2); `no-store` on sounds (A4); no visible warning text (A5). All reversible.

## Not done

- Non-goals: encryption/DRM, blob/MediaSource streaming, watermarking, rate limiting, signed expiring URLs, blocking capture/devtools, per-user exemption or setting, protecting images, visible warning.
- Open (non-blocking): should the artist be able to download their own sound back? Not pushed to the backlog.
- E2E: the feature spec (6 tests, green) was deleted at WRAP, not promoted to `core/`.
- No backlog rows created.
