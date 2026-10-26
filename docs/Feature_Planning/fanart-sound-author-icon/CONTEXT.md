# Fanart sound author icon — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** auto

## 0. Handoff

### REFINE → BUILD (2026-10-26)

- Zero questions asked (auto mode, nothing blocking). Spec, design and plan written in one pass (fast path).
- "The sound component" is read as the two **listening players** of the story flow: the chapter page player (`chapter-audio`) and the story page's single player (`story-player`). Most likely to be reversed: also adding the icon to the profile tab audio rows (excluded: they sit on the artist's own profile) or to the viewer (already shows « Par <artiste> »).
- Trap: on the story page one `<audio>` serves all chapters, so the icon must follow the *currently playing* chapter (store state), not be static.
- Trap: `audioPlaylist()` is memoised per request; enrich it there (one batched profile lookup), never call the profile API per row.
- The report button of `chapter-audio` is the nearest sibling pattern (icon next to the player, `flex items-center gap-2`).

### BUILD phase 1 → VERIFY (2026-10-26)

- As planned. Story-player entries carry `artistLabel` (already « Par Nom ») and `artistUrl`; icon markers: `data-artist-link`. Alpine follow-playback behaviour untested by automation (VERIFY).

### VERIFY → WRAP (2026-10-26)

- All 3 QA rows pass in a real browser: `e2e/tests/features/fanart-sound-author-icon.spec.ts` (3 tests; temporary, delete at WRAP, nothing worth promoting to `core/`). Screenshot at 375px opened: icon sits on the player row, no overflow. No defect, no flaky or manual rows.

## 1. Functional spec

### 1.1 Overview

Next to each fanart sound player a small person icon lets a listener find out who made the sound: it links to the artist's profile and shows « Par <nom> » as tooltip. For confirmed users who can already hear the sound.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Icône d'artiste | Small `account_circle` link beside a sound player, to the artist's profile |
| Artiste | The fanart's submitter (`fanarts.user_id`), as shown in the viewer (« Par ») |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest / `user` | Nothing (players are not rendered for them today) | — |
| `user-confirmed` | Icon wherever the player renders for them | Follow the link to the public profile |
| Author / co-author, moderator, admin | Same as confirmed | Same |

The icon is visible exactly when the player is: no new visibility rule.

### 1.4 Functional requirements

**Chapter page.** Next to the audio player (before the report button), an icon link to the artist's profile, title/aria-label « Par <nom> ». Shown on the artist's own sound too.

**Story page.** The single player has the same icon beside it. It reflects the chapter being played: the link and label change when the queue moves to the next chapter. Hidden while the player itself is hidden (nothing playing).

**Edge.** If the artist's public profile cannot be resolved (account deleted/deactivated), the icon is not rendered; the player is unaffected.

### 1.5 Lifecycle

Pure display, no data. Fanart deletion/unpublish already removes the player. Artist deleted/deactivated: no icon (see above).

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | See 1.3; `user` vs `user-confirmed` unchanged (players already confirmed-only) |
| Visibility / privacy | Only the public display name and profile link, same data the viewer already shows |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — report button untouched |
| Lifecycle / cascade | See 1.5 |
| Media | N/A |
| Search | N/A |
| i18n | New French string `player.artist` « Par :name » |
| Mobile | Icon at least 24px, tappable, sits on the same row as the player |
| Accessibility | `<a>` with `aria-label` and `title` « Par <nom> », decorative icon `aria-hidden` |

### 1.7 Out of scope

- Icon on profile-tab audio rows, the viewer modal, the moderation snapshot, the audio miniature thumbnails.
- Showing the artist's avatar or name as text; popover/card.
- Co-artists (a fanart has one submitter).

### 1.8 Open questions

None blocking. Non-blocking: icon glyph (`account_circle` chosen).

## 2. Architecture

### 2.1 Domain placement

`Fanart` only. Uses `ProfilePublicApi` (already injected as `$this->profiles` in `FanartService`).

### 2.2 Data model

None.

### 2.3 PHP architecture

`FanartService::audioPlaylist()` items gain `artistName: ?string` and `artistUrl: ?string`: collect `user_id` of the audios, one `getPublicProfiles($ids)` call, `artistUrl = route('profile.show', ['profile' => $dto->slug])` (same as the viewer), both `null` when the profile is missing. The per-request memoisation is kept.

`ChapterAudio` exposes `?string $artistName, $artistUrl` from the matching item. `StoryPlayer` adds both keys to each entry of `$entries`.

### 2.4 Frontend

- `chapter-audio.blade.php`: when `$artistUrl`, an `<a>` icon (`material-symbols-outlined account_circle`) between player and report button.
- `fanart-player.js` store: `current()` returns the queue entry for `currentId` or `null`.
- `story-player.blade.php`: wrap the audio and an `<a>` in a flex row; `<a x-show="$store.fanartPlayer.current()?.artistUrl" :href=... :title/:aria-label=...>`. Label built client-side from a translated template passed in the entry (`artistLabel`, already « Par Nom ») to avoid JS i18n.
- Lang: `fanart::ui.player.artist` => `Par :name`.

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Pest feature tests on both components (icon + link + label present; absent when artist profile missing; story-player entries carry `artistLabel`/`artistUrl`). Vitest for `current()`. Alpine reactivity (label follows playback) only VERIFY can see.

### 2.7 Tradeoffs locked

| # | Question | Options | Chosen | Why |
|---|----------|---------|--------|-----|
| 1 | Where does the artist data come from | extra query in each component / enrich playlist | enrich `audioPlaylist` | One batched lookup, shared by both components, memoised |
| 2 | Story page icon | static per row / follows playback | follows playback | One shared `<audio>`; rows have no player |

### 2.8 Risks acknowledged

Playlist shape change: only `ChapterAudio`, `StoryPlayer`, `ChapterAudioIcon` consume it (revisit if more consumers appear).

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Artist icon on chapter and story players | S | — | DONE |

Working agreement: one phase = one commit; failing test first.

### Phase 1 — Artist icon on chapter and story players

**Goal.** Show the artist icon beside both players. **Reads.** §2.3, §2.4.

**Deliverables.**
- `app/Domains/Fanart/Private/Services/FanartService.php` (playlist enrichment)
- `app/Domains/Fanart/Private/View/Components/ChapterAudio.php`, `StoryPlayer.php`
- `app/Domains/Fanart/Private/Resources/views/components/chapter-audio.blade.php`, `story-player.blade.php`
- `app/Domains/Fanart/Private/Resources/js/fanart-player.js` (+ `.test.js`)
- `app/Domains/Fanart/Private/Resources/lang/fr/ui.php`
- Tests in `app/Domains/Fanart/Tests/Feature/ChapterPageFanartTest.php` and `StoryPlayerTest.php`

**Tests.** `it shows the artist icon next to the chapter audio player`, `it omits the artist icon when the artist profile is gone`, `it gives each story player entry its artist label and url`, vitest `current returns the playing entry`.

**Acceptance.**
- ✅ Chapter page: link to `profile.show` of the artist with « Par <nom> ».
- ✅ Story page: icon appears when playing and updates on next chapter.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| Surface | Check | OK? |
|---------|-------|-----|
| Chapter page | Icon beside player and report button, links to profile | ✅ |
| Story page | Icon hidden until play; changes artist when queue advances | ✅ |
| Mobile 375px | Row does not overflow | ✅ |

### 3.3 Open items

None.

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| | | | | | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| 1 | "Sound component" = chapter-page player and story-page player only | REFINE | Yes |
| 2 | Icon links to the artist's profile, tooltip « Par <nom> » | REFINE | Yes |
| 3 | Shown on the user's own sound too | REFINE | Yes |
| 4 | No icon when the artist profile cannot be resolved | REFINE | Yes |
| 5 | Glyph `account_circle` | REFINE | Yes |

## 5. Build & verify log

- BUILD phase 1 (2026-10-26): artist icon on chapter and story players; `audioPlaylist` enriched (batched profile lookup), store `current()`; gate green.
- VERIFY (2026-10-26): PASS, 3/3 browser checks via the feature spec above (chapter link + « Par » tooltip, story icon hidden then follows queue, 375px no overflow).
