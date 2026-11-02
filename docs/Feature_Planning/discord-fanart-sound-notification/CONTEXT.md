# Notify Discord when audio fanart is published — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).

**Complexity:** 2 /5 · **Mode:** interactive

## 0. Handoff

### DESIGN+PLAN revision → BUILD (2026-11-02)

- The user reversed A1 (see §4 D1): audio uses the **same opt-in checkbox** as images, on the reader submission form and the author artwork upload. §1–§3 rewritten accordingly; still one phase, still rated 2 (a small UI change is now in scope, hence a visual QA checklist).
- Verified in the code: there is **one** form view for both flows (`pages/create.blade.php`, `$isArtwork` switch); the checkbox is hidden by `@if ($kind === 'image')`. `StoreFanartRequest` already validates `share_on_discord` for every kind; the image-only gating lives in `FanartService::submit()` (`$isImage && …`) and `uploadArtwork()` (`kind === Image && …`). No change needed in the request, the controller or `SubmitFanartData`.
- The edit page (`pages/edit.blade.php`, `UpdateArtworkRequest`) has no Discord checkbox today, for images either: unchanged.
- Trap: `isDiscordEligible()` also guards the signed **image** route; once the eligibility query admits audio it must still 404 for audio there (`streamVariant` on an mp3 path must never be reached).
- Trap: `SubmitFanartTest` "ignores share_on_discord for audio" asserts the old behaviour; it must be rewritten (honoured, not ignored), not deleted.
- The bot lives outside this repo: items gain `kind`, audio items omit `imageUrl`. Document it in `docs/Discord_Api_Usage.md`; deploy bot support first.

### REFINE+DESIGN+PLAN → BUILD (2026-11-02)

- 0 blocking questions asked. Fast path (rated 2): §1, §2, §3 written together. Superseded by the revision above (A1 reversed).

## 1. Functional spec

### 1.1 Overview

When a sound fanart whose fanartist chose to share it on Discord is published (accepted by a story author, or uploaded by an author as artwork), the Discord bot is told about it through the existing fanart feed, so the community can be pointed to the chapter and the story. Notification only: the sound itself is never exposed to Discord. The choice is the same « Partager sur Discord » checkbox images already have.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Fanart audio | A `Fanart` of kind `audio`, always attached to a chapter |
| Share on Discord | The opt-in checkbox (`share_on_discord`), now shown for images **and** sounds; for a sound it means "announce it", never "upload it" |
| Feed | `GET /api/discord/fanarts/pending` + `POST /api/discord/fanarts/mark-sent` |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Nothing new in the app (the Discord message links to public story/chapter pages) | — |
| Fanartist (`user-confirmed` with the audio role) | The « Partager sur Discord » checkbox on the sound submission form | Tick it or not; ticked = announced once an author accepts the sound |
| Story author (artwork upload) | The same checkbox on the sound artwork upload form | Tick it or not; ticked = announced right away (artworks publish on upload) |
| Story author (review) | Unchanged | Unchanged (accepting a ticked sound publishes it, hence announces it) |
| Discord bot (API key) | Audio items in the pending feed: contributor (id, slug, name, avatar), chapter (title, slug), story (title, URL, authors) | Mark as sent |

### 1.4 Functional requirements

1. The « Partager sur Discord » checkbox is shown on the sound submission form and the sound artwork upload form, with the same label, position and default (unchecked, restores `old()` after a validation error) as for images.
2. The submitted value is stored for sounds exactly as for images: ticked → `share_on_discord = true`, unticked → `false`.
3. A published audio fanart with `share_on_discord = true`, on a **public** story and a **published** chapter, not yet marked sent, appears in the pending feed, oldest first, mixed with images.
4. Each audio item carries: `id`, `kind: "audio"`, `title`, `artistName`, `artist {id, slug, avatarUrl}`, `authors`, `storyTitle`, `storyUrl`, `chapterTitle`, `chapterSlug`, `description` (plain text, when present), `approvedAt`. It carries **no** `imageUrl` and no link to the file.
5. Image items gain `kind: "image"`; otherwise unchanged.
6. Marking sent works for audio ids exactly as for images.
7. Edge: unticked sound, community/private story, unpublished chapter, pending fanart, already-sent, deleted fanart: not listed (same rules as images).
8. Edge: a sound replaced by a newer published sound on the same chapter is deleted; if it was not yet sent it drops out; the new one is announced only if it was ticked.

### 1.5 Lifecycle

Unchanged from images: deletion, deactivation (soft delete) and chapter/story deletion remove the item from the feed; nothing is retracted from Discord once sent. The choice cannot be changed after submission (no checkbox on the edit page, as for images). Existing sounds are not backfilled (app not live yet).

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — the form is already gated; bot endpoints are API-key guarded, unchanged |
| Visibility / privacy | Sounds stay private (confirmed users only). The feed exposes only metadata of public stories; no file URL |
| Settings | N/A |
| Notifications | N/A for in-app notifications. Discord feed only |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A — pending/rejected sounds never listed |
| Lifecycle / cascade | See 1.5 |
| Media | N/A — no media streamed; the signed image route must refuse audio |
| Search | N/A |
| i18n | Reuses the existing `fanart::ui.create.share_on_discord` string; no new copy |
| Mobile | Same checkbox row as images; nothing new |
| Accessibility | Same labelled native checkbox as images |

### 1.7 Out of scope

- Playing/downloading the sound on Discord (user-confirmed).
- Changing the choice after submission (not possible for images either).
- Retroactive announcement of already published sounds.
- The bot-side message formatting (separate repo).

### 1.8 Open questions

None.

## 2. Architecture

### 2.1 Domain placement

Fanart domain only (`FanartService` is the sole writer/reader of the feed). No other domain, no deptrac edge, no table or migration.

### 2.2 Data model

No change. Reuse `share_on_discord` and `discord_sent_at` for audio rows; the eligibility query only widens its `kind` filter.

### 2.3 PHP architecture

- Writers (`submit()`, `uploadArtwork()`): store `$data->shareOnDiscord` for every kind (drop the image-only gating). `StoreFanartRequest`, `SubmitFanartData`, `FanartController`: no change (the field is already validated and carried for every kind).
- `FanartService::discordEligible()`: `kind` in (Image, Audio) instead of Image only; `share_on_discord = true` still required.
- `FanartService::isDiscordEligible()` guards the signed image route: keep it image-only (kind restriction there) so audio 404s on `fanart.discord-image`.
- `discordPending()`: add `kind` (`$fanart->kind->value`) to each item; build `imageUrl` only for images.

### 2.4 Frontend

`pages/create.blade.php`: drop the `@if ($kind === 'image')` around the Discord checkbox, so it renders for both kinds and both flows (reader submission, author artwork). Same label key, same unchecked default. No JS, no new lang string.

### 2.5 Deptrac

No new edge.

### 2.6 Testing strategy

Integration. `SubmitFanartTest`: rewrite "ignores share_on_discord for audio" into "honours share_on_discord for audio" (ticked → true, unticked → false); the create page shows the checkbox for audio (reader and artwork). `DiscordFeedTest`: ticked published audio listed with the right shape and no `imageUrl`; excluded cases (unticked, pending, unpublished chapter, community story, already sent); signed image route 404s for audio; mark-sent works; artwork audio ticked is listed. The existing "excludes audio…" test is rewritten (audio no longer excluded per se). Browser check of the checkbox in VERIFY (§3.2).

### 2.7 Tradeoffs locked

See §4: D1 opt-in checkbox for audio, D2 same endpoint + `kind`, D3 no backfill.

### 2.8 Risks acknowledged

The bot (other repo) may crash on an item with no `imageUrl` before it is updated. Trigger: deploy order. Mitigation: deploy bot support first, and document in `docs/Discord_Api_Usage.md`.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Opt-in audio in the Discord fanart feed | S | — | TODO |

Working agreement: one phase = one commit; failing test first. One phase: the checkbox without the feed would store a choice nobody reads, and the feed without the checkbox would list nothing.

### Phase 1 — Opt-in audio in the Discord fanart feed

**Goal.** Fanartists can tick « Partager sur Discord » on a sound; ticked published sounds of public stories appear in the pending feed as link-only items. **Reads.** §2.3, §2.4, §2.6.

**Deliverables.**
- `app/Domains/Fanart/Private/Resources/views/pages/create.blade.php`: checkbox rendered for every kind.
- `app/Domains/Fanart/Private/Services/FanartService.php`: `share_on_discord` from input for every kind in `submit()` and `uploadArtwork()`; widen `discordEligible()`; image-only guard in `isDiscordEligible()`; `kind` + conditional `imageUrl` in `discordPending()`.
- `app/Domains/Fanart/Tests/Feature/SubmitFanartTest.php`, `app/Domains/Fanart/Tests/Feature/DiscordFeedTest.php` (+ `Tests/helpers.php` helper `sharedAudio()` next to `sharedImage()`).
- `docs/Discord_Api_Usage.md` fanarts section (audio items opt-in, `kind`, no `imageUrl`) and `app/Domains/Fanart/README.md` (Discord relay line and the "no audio on Discord" line).

**Tests.**
- `SubmitFanartTest`: `honours share_on_discord for audio` (replaces `ignores share_on_discord for audio`); `shows the discord checkbox on the audio submission form`; `shows the discord checkbox on the audio artwork form`.
- `DiscordFeedTest`: `lists ticked published audio on public stories without an image url`; `excludes unticked, pending, sent, community and unpublished-chapter audio`; `refuses the signed image route for audio`; `marks audio as sent`; `includes ticked audio artworks`; `images carry kind image`; updated `excludes pending, community and private stories, and already-sent ones` (audio line removed).

**Acceptance.**
- ✅ Checkbox visible and honoured for audio, unchecked by default, on both flows.
- ✅ Audio items match §1.4 shape; no file URL anywhere.
- ✅ Signed image route 404s for audio.
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

| # | Role | Page | Check |
|---|------|------|-------|
| 1 | Fanartist with audio role, on a public story | Sound submission form | « Partager sur Discord » checkbox shown, unchecked, same place as on the image form |
| 2 | Same | Sound submission form, invalid submit (e.g. no file) with box ticked | Box still ticked after the error |
| 3 | Story author | Sound artwork upload form | Checkbox shown, unchecked |
| 4 | Story author | Image forms (submission and artwork) | Unchanged |
| 5 | Mobile width | Sound submission form | Checkbox row wraps cleanly |

Feed behaviour is covered by the integration tests (no browser surface).

### 3.3 Open items

Bot-side support is outside this repo (phase 1 docs only).

## 4. Decisions

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| D1 | 2026-11-02 | DESIGN+PLAN | Is every published sound announced, or does the fanartist choose? | The fanartist chooses, exactly like images: same `share_on_discord` checkbox on the reader submission and the author artwork upload; feed requires `share_on_discord = true` | A1 |
| D2 | 2026-11-02 | DESIGN+PLAN | Feed shape for audio | Same endpoint; items gain `kind`; audio items omit `imageUrl` | A2 |
| D3 | 2026-11-02 | DESIGN+PLAN | Backfill existing published sounds? | No backfill; the app is not live in production yet | A3 |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A4 | Sounds replaced before being sent are simply never announced | REFINE | N/A |
| A5 | Same label for audio as for images, no extra hint that only the announcement (not the sound) goes to Discord | DESIGN+PLAN | Yes — one lang key and a `@if ($kind === 'audio')` hint line |

## 5. Build & verify log

_Not written yet._
