# Discourage downloading of fanart sounds — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → BUILD (2026-10-25)

Zero blocking questions asked. Design and plan are written in this same pass (rated 2).
- Likeliest to be reversed: A2 (no exemption for the artist / story authors, unlike the chapter text which exempts authors) and A4 (`no-store` cache on sounds).
- Honest limit, to repeat in WRAP: nothing client-side stops a determined user (devtools network tab, screen capture). This is friction, not DRM. Do not promise more in the UI.
- Four `<audio>` surfaces exist (story-player, chapter-audio, viewer fragment, profile-group); all must go through the one new component, otherwise one of them stays leaky.
- Precedent for text: `x-on:copy.prevent x-on:cut.prevent x-on:dragstart.prevent` on `<article data-quote-article>` in Story `chapters/show.blade.php` (authors exempt). Fragments are fetched lazily, so use inline `oncontextmenu`, not Alpine, on the audio.

### BUILD phase 1 → VERIFY/WRAP (2026-10-25)

As planned. Left alone on purpose: the raw `<audio>` in `moderation/snapshot.blade.php` (moderator-only copy via `_media`, not one of the four surfaces). Browser-only checks (§3.2) remain for VERIFY.

## 1. Functional spec

### 1.1 Overview

Make it harder for readers to save or share the raw mp3 of a fanart sound: remove the browser's download and right-click affordances on every sound player, and make the file URL useless when opened directly or shared. For the sound artists (and story authors who upload artworks). Friction only, same spirit as the text copy protection.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Son / fanart audio | A fanart of kind audio (mp3), streamed by `fanart.file` |
| Lecteur | Any `<audio>` element playing a fanart sound |

### 1.3 Roles & visibility

Unchanged: only confirmed users (and staff) reach the sounds. The protection applies to every role, including the artist, authors, moderators and admins (A2).

### 1.4 Functional requirements

1. **Players.** Every fanart sound player (story-page player, chapter page, viewer modal, profile tab): no download entry in the native controls; right click on the player does nothing; dragging the player/link does nothing.
2. **Direct URL.** Opening `/fanarts/{id}/file` of a sound by navigating to it (address bar, pasted link, new tab, "open in new tab") is refused (403) even for a logged-in confirmed user, so a copied URL cannot be shared or used to save the file. Playback inside the player keeps working, including seeking.
3. **Cache.** The sound response is not stored by the browser cache (A4), so it does not linger as a reusable file.
4. Images are untouched.
5. Edge: a browser that does not send the `Sec-Fetch-Dest` header is let through (fail open) — playback must never break for legitimate listeners.

### 1.5 Lifecycle

N/A — no data stored. Deleted/unpublished fanarts behave as today (404/403).

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Same for all (A2); non-confirmed still get 403 as today |
| Visibility / privacy | Authorization unchanged; adds a request-origin check on top |
| Settings | N/A — no toggle (A1) |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — staff review sounds through the same players |
| Lifecycle / cascade | N/A |
| Media | Streaming stays in `MediaPublicApi::stream`; only headers passed in change |
| Search | N/A |
| i18n | No new visible text |
| Mobile | Long-press menu suppressed via the same contextmenu handler; iOS/Android native download UI removed by `controlsList` where supported |
| Accessibility | Keep `aria-label` on every `<audio>`; native controls stay |

### 1.7 Out of scope

Encryption / DRM / blob or MediaSource streaming, watermarking, rate-limiting downloads, signed expiring URLs, blocking screen capture or devtools, any exemption or per-user setting, protecting images, a visible warning message.

### 1.8 Open questions

None blocking. Non-blocking: should the artist be allowed to download their own sound back (A2)?

## 2. Architecture

### 2.1 Domain placement

Fanart only. No Media change (headers already a parameter of `stream()`).

### 2.2 Data model

None.

### 2.3 PHP architecture

`FanartFileController::original`: for `FanartKind::Audio`, after authorization, if the request has `Sec-Fetch-Dest` and its value is not `audio` (or `media`-less variants: accept exactly `audio` and `video`), `abort(403)`. Absent header passes. Audio responses use `Cache-Control: private, no-store` instead of `CACHE_HEADERS` (Range answers still work, `BinaryFileResponse`). Images keep the current headers and no header check.

### 2.4 Frontend

New anonymous Blade component `fanart::protected-audio` (`Private/Resources/views/components/protected-audio.blade.php`) rendering `<audio controls controlsList="nodownload noplaybackrate" oncontextmenu="return false" ondragstart="return false" {{ $attributes }}>`. Replace the four raw `<audio>` tags with it (story-player keeps `x-ref`, `x-on:ended`, no `src`). Inline handlers on purpose: the viewer and profile groups are lazy fragments.

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Pest feature tests in `FanartFileRoutesTest.php`: audio with `Sec-Fetch-Dest: document` is 403; with `audio` is 200 and `Cache-Control` contains `no-store`; without the header is 200; image with `document` stays 200. Render tests (existing `StoryPlayerTest`, `ChapterPageFanartTest`, `FanartFragmentsTest`) assert `controlsList="nodownload` and `oncontextmenu` on each surface. VERIFY only: native menu in a real browser, seeking still works.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Block direct URL | cookie/token URL, signed URL, `Sec-Fetch-Dest` | `Sec-Fetch-Dest` check | Zero state, no schema; flip if bypass by curl-with-cookie matters (then signed short-lived URLs) |
| 2 | Missing header | fail closed, fail open | fail open | Never break playback on old browsers |

### 2.8 Risks acknowledged

A logged-in user can still replay the request with curl and their session cookie or save from the devtools network tab. Revisit with short-lived signed URLs only if abuse is reported. Safari/iOS may ignore `controlsList`.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Server check + protected-audio component + tests | S | — | DONE |

### Phase 1 — Protect sound playback

**Goal.** Sounds cannot be opened directly nor saved from the native controls. **Reads.** §2.3, §2.4.

**Deliverables.** `Private/Controllers/FanartFileController.php` (header check, no-store for audio); new `Private/Resources/views/components/protected-audio.blade.php`; replace `<audio>` in `components/story-player.blade.php`, `components/chapter-audio.blade.php`, `fragments/viewer.blade.php`, `fragments/profile-group.blade.php`; update `app/Domains/Fanart/AGENTS.md` and `README.md` (one line each on the protection and its limits).

**Tests.** As §2.6, failing first.

**Acceptance.**
- ✅ Direct navigation to a sound URL answers 403; in-player playback and seeking still answer 200/206.
- ✅ All four surfaces render `controlsList="nodownload` and `oncontextmenu`.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page | Right click on player: no menu; three-dot menu has no Download; plays and seeks | |
| Story page player | Same | |
| Viewer modal (sound) | Same | |
| Profile tab Sons | Same | |
| Address bar | Pasting the sound URL gives 403 | |
| Image viewer | Still works | |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Blocking questions | None asked; user request taken as scope | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | No setting or toggle; protection always on | REFINE | Yes |
| A2 | No exemption for the artist or story authors (text protection exempts authors) | REFINE | Yes |
| A3 | Direct-URL block uses `Sec-Fetch-Dest`, fail open when absent | REFINE | Yes |
| A4 | Sound responses use `private, no-store` | REFINE | Yes |
| A5 | No visible warning text, no new French strings | REFINE | Yes |

## 5. Build & verify log

- Phase 1 DONE (2026-10-25): `Sec-Fetch-Dest` check + `no-store` on sounds in `FanartFileController`, `<x-fanart::protected-audio>` replacing the four `<audio>` tags, Pest tests on file route and the four surfaces, Fanart AGENTS/README updated.
