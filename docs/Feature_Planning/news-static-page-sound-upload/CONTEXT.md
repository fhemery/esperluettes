# Sound upload for news and static pages — context

> The task's single working file. Every agent reads **§0 Handoff first**, then
> only the sections it needs, and writes its own section plus one Handoff entry
> before it finishes. WRAP compacts the whole thing into `README.md` and the
> folder is archived (see `.agents/loop/README.md`).
>
> A section still reading `_Not written yet._` has not been done — that is how
> `/continue-task` knows where the task stands.

**Complexity:** 4 /5 · **Mode:** interactive

## 0. Handoff

### REFINE → DESIGN+PLAN (2026-10-25)

- 0 blocking questions asked (no way to ask in this run; none were blocking).
- Likeliest reversals: A1 (new `audio` block type rather than a sound inside the text block), A3 (MP3 only, 10 MB), A5 (visible to guests, no auth).
- Code facts for DESIGN: advanced mode is `<x-editor::multi>`; block types are registered in `EditorBlockRegistry` (built-ins `text`, `image`); per-consumer enablement is the `blockTypes` prop (default `['text','image']`, chapters pass `['text','image','chapter-choice']`). So an `audio` type listed only in News/StaticPage `blockTypes` keeps the chapter editor clean. Check whether `ContentBlocksRenderer` filters by enabled list at render time (chapter content must not render a forged `audio` block).
- Media has NO public non-image file storage: `store()` is image-only, private scope roots are hardcoded (`MediaService::PRIVATE_SCOPE_ROOTS`). Needs a new public-file method on `MediaPublicApi` (validation by caller), plus GC awareness (`media:gc` is image-oriented; News/StaticPage usage providers must yield audio paths).
- `<audio>` is stripped by the `multiedit-*` Purifier profiles, so the block type must emit and escape its own HTML. Read `Editor/AGENTS.md` and `Media/AGENTS.md` invariants first.
- Not fast-pathed: spans Editor + Media + News + StaticPage (+ Story guard), new block type and new Media extension.

### DESIGN+PLAN → BUILD (2026-10-25)

- 0 questions asked: no tradeoff was both balanced and costly to reverse. §1 fixed in place (A9): moderators edit news so they get sounds there; mode switch is refused, not dropping blocks.
- Riskiest phase: **1** (Media GC now deletes public `.mp3`; the GC/picker split shares `originalsIn()` today — keep the picker image-only).
- Verified in code, no need to re-check: `ContentBlocksRenderer` does **not** filter by enabled types (renders every registered type), but Story's `ChapterRequest` (`Rule::in`) and `ChapterContentResolver` whitelist block types on write, so a forged chapter `audio` block is a 422 — phase 2 pins it with a test. News/StaticPage resolvers also whitelist (they ignore `chapter-choice`).
- Trap: `<x-media::sound-field>` posts `{name}_remove`; with a bracketed name PHP folds it onto `{name}` and the string `"false"` breaks `file` validation — hence the `removeName` prop (A14). Use the separate `sound` key for audio uploads; `blocks.*.file` stays the image rule.
- Likeliest reversals: A10/A11 (built-in type, same scope folder), A15 strictness, A9 moderator access.

### BUILD phase 1 → next (2026-10-25)

- No deviation from the plan. `originalsIn()` has no default for its pattern argument on purpose: every caller must choose images-only (picker) or images+mp3 (GC); do not add a default.
- The Editor `_audio-block` partial (phase 2) must pass `remove-name="{name}[{uid}][sound_remove]"` to `<x-media::sound-field>`; without it the remove flag clobbers the file field.
- The planning docs were committed separately just before the phase commit.

### BUILD phase 2 → next (2026-10-25)

- No deviation from the plan. The Story guard needed no code: the chapter POST/PUT with an `audio` block already fails `Rule::in`.
- Trap for tests: `$this->blade()` renders have no `$errors`; `<x-media::sound-field>` reads `$errors->get($name)`, so a test rendering an audio block must `view()->share('errors', new ViewErrorBag())` (real pages share it).
- Chapter/News/StaticPage forms offer no `audio` yet (`blockTypes` default unchanged).

### BUILD phase 3 → next (2026-10-25)

- Shipped as planned; StaticPage should mirror `NewsService::resolveContent()` audio branch. Additions beyond the plan: path check also rejects `..`; mode-switch refusal lives in `NewsService::update()` (ValidationException on `mode`, key `audio_mode_switch`) since the plan named no place.
- The form prop is `:block-types` (kebab) on `<x-editor::multi>`. A plain `user` hitting the admin store route is redirected to the dashboard (route middleware), not 403 — tests assert the redirect. Tests helpers: `alice($this, [], true, [Roles::USER])` for a non-confirmed user; there is no `user()` helper.

## 1. Functional spec

### 1.1 Overview

Admins and editors writing a news article or a static page in advanced mode can add a sound (an audio block) uploaded from their computer. The sound is public: anyone who can see the page can play it. The chapter editor does not offer it.

### 1.2 Vocabulary

| Term | Meaning |
|------|---------|
| Bloc son | Advanced-mode block holding one uploaded audio file, an optional title/caption, rendered as an audio player |
| Mode avancé | Block-based editing mode of `<x-editor::multi>` |

### 1.3 Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Published news / static pages with their sounds, can play | Nothing |
| `user` / `user-confirmed` | Same as guest | Nothing |
| Author / co-author | N/A — no author concept for news/static pages | N/A |
| Moderator | Same as guest | Add, replace, remove sound blocks in **news** (moderators already edit news: `NewsRequest` allows moderator/admin/tech-admin); not static pages |
| Admin / tech-admin | Everything | Add, replace, remove sound blocks in news and static pages (`StaticPageRequest` allows admin/tech-admin only) |

### 1.4 Functional requirements

**Add a sound.**
1. In advanced mode of a news or static page form, the block palette / "+" menu offers "Son".
2. The editor picks an MP3 file; a native player previews it.
3. Optionally fills a caption (legend).
4. Saving stores the file publicly and renders an audio player in the public page.

**Replace / remove.** The editor can replace the file (delete the current sound in the block's sound field, then pick another) or delete the whole block; the previous file is no longer used by the page.

**Errors.** Non-MP3 or file over the size limit: form validation error in French, block content kept as far as possible (block order, caption and already-stored path survive; a newly picked file must be re-picked — browsers never repopulate file inputs, same as image blocks today). Saving a sound block with no file (never picked, or current sound deleted without a replacement): validation error (a sound block needs a file) — unlike an empty image block, which is silently dropped today.

**Chapter editor.** The "Son" block is not offered in the chapter editor and a chapter never renders an audio block.

### 1.5 Lifecycle

- News / static page deleted: its sound files become unused and are collected by the existing media GC.
- Block removed or file replaced: same, via GC.
- Unpublished / draft page: sound is not reachable through the page; the file URL itself is public by nature (see A5).
- Switching a page back to simple mode: refused while any non-text block exists (existing behaviour of `<x-editor::multi>`), so a sound block must be deleted first.
- Deactivated/deleted editor account: no effect on the sound.

### 1.6 Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Only those who can already edit news (moderator, admin, tech-admin) / static pages (admin, tech-admin) |
| Visibility / privacy | Public, no authentication on playback |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A — no new event (A7) |
| Statistics | N/A |
| Moderation | N/A — admin-authored content |
| Lifecycle / cascade | Media GC via usage providers of News and StaticPage |
| Media | New public audio storage in Media; limits MP3, 10 MB |
| Search | N/A — audio not indexed |
| i18n | French labels, Shared/domain translations |
| Mobile | Native `<audio controls>`, full width |
| Accessibility | Native controls, caption as visible text, label on the player |

### 1.7 Out of scope

- Sound upload in the chapter editor (explicit).
- Audio in the simple (Quill) mode, or inside text blocks.
- Other formats (ogg, wav), transcoding, waveform, download button, autoplay, playlists.
- Reusing an already-uploaded sound (no sound library / picker; the image reuse picker keeps listing images only).
- Private sounds, access control; changes to Secret Gift / Fanart sound handling.
- Sound for other consumers (FAQ, comments, stories).

### 1.8 Open questions

- Non-blocking: should the sound carry a downloadable link? Assumed no (A6).

## 2. Architecture

### 2.1 Domain placement

No new domain. Four touched domains, one guarded:

- **Media** — gains *public raw-file storage* (the public twin of `storePrivateFile`) and learns that `.mp3` originals on the public disk are GC-managed. The sound-field component gains one backward-compatible prop. Extension of the existing public API; no new registry.
- **Editor** — gains a third **built-in** block type, `audio`, beside `text` and `image`. Built-in (not registered by News or StaticPage) because two consumers need it and it is a generic media block like `image`; **opt-in** because the default `blockTypes` stays `['text', 'image']`.
- **News**, **StaticPage** — opt into `audio` in their `<x-editor::multi>` call, validate and normalise audio blocks in their existing request + `resolveContent()`, store uploads through Media, and claim audio paths in their existing `MediaUsageProvider`.
- **Story** — no code change. Its write path already whitelists block types (`ChapterRequest` `Rule::in(['text','image','chapter-choice'])` and `ChapterContentResolver` only builds those three), so a forged `audio` block is rejected with a 422 and never reaches `content_blocks`. A test pins that (§2.6).

### 2.2 Data model

No migration. `content_blocks` (JSON, News and StaticPage) gains one block shape, documented in Editor's README block schema:

```php
['type' => 'audio',
 'path' => 'news/<40-char-hash>.mp3',   // Media public path, under the consumer's scope folder
 'caption' => '…']                       // optional, ≤255, plain text
```

Files live on the **public disk** in the consumer's existing flat scope folder (`news/`, `static-pages/`), named by `UploadedFile::hashName()` (extension from the guessed MIME, never from the client name). No variants.

Lifecycle (§1.5): deleting a page, removing a block or replacing a file only changes `content_blocks`; the file becomes unclaimed and `media:gc` deletes it after the 7-day grace window, exactly like images. The rendered `content` cache (with the file URL baked in, as image URLs already are) is rewritten on every save.

### 2.3 PHP architecture

**Media**

- `MediaPublicApi::storePublicFile(string $scope, UploadedFile $file): string` → `MediaService` equivalent. Raw bytes to the public disk under `folderFor($scope)`; throws `InvalidArgumentException` for a private scope. No content validation — the caller validates (same contract as `storePrivateFile`). The URL is the existing `originalUrl($path)`.
- GC: the public-disk original filter used by `gc()` accepts `jpg|jpeg|png|webp|mp3`; `listByScope()` (the image reuse picker) keeps listing **images only**. These two currently share `originalsIn()`, so the split must be explicit. `deleteWithVariants()` already handles a non-image file (it is used for private sounds). The zero-claim folder guard is unchanged.
- `<x-media::sound-field>` gets `removeName` (default `"{name}_remove"`, i.e. today's behaviour). Needed because PHP ignores characters after a closing bracket: `blocks[b0][sound]_remove` would be parsed as `blocks[b0][sound]` and overwrite the file field with the string `"false"`, failing `file` validation on every save. The audio block passes `blocks[uid][sound_remove]`.

**Editor**

- `Private/Blocks/AudioBlockType` implements `EditorBlockType`: key `audio`, label `editor::multi.add_audio`, a Material Symbols audio icon, editor partial `editor::components.multi._audio-block`. Constructor-injects `MediaPublicApi` (for `originalUrl`), so `EditorServiceProvider` builds it through the container inside the registry factory, after `image`.
- `render()`: `''` when `path` is empty; otherwise a `<figure class="ce-block ce-block--audio">` holding `<audio controls preload="metadata" src="{originalUrl}">` (fallback text inside, accessible label = caption or a generic "Lecteur audio") and an optional `<figcaption>`. Everything escaped by the type (Blade `{{ }}` through `Blade::render`, like `ImageBlockType`) — Editor does not re-sanitize and no Purifier profile allows `<audio>`, which is why the HTML is emitted by the type and never passed through a text profile.
- No change to `ContentBlocksRenderer` or `EditorPublicApi`: the renderer keeps rendering every registered type. Enablement is enforced on the **write side** by each consumer's whitelist (§2.7 #3).

**News / StaticPage** (identical shape in both)

- Request: `blocks.*.type` adds `audio`; new rules `blocks.*.sound` → `nullable|file|mimes:mp3|max:10240`, `blocks.*.sound_remove` → `nullable`; French messages for `sound.mimes` / `sound.max`. The existing `blocks.*.file` (`image`) rule is untouched — sounds use the separate `sound` key so the two wildcard rules never collide.
- `resolveContent()` audio branch: an `UploadedFile` under `sound` → `storePublicFile(SCOPE, …)`; else, unless `sound_remove` is truthy, keep the submitted `path` **only if** it starts with `folderFor(SCOPE) . '/'` and ends in `.mp3` (a crafted path to another scope, a private file or an image would otherwise render a broken player or make `originalUrl` throw); no path → `ValidationException` on `blocks` ("un bloc son doit contenir un fichier"). Caption trimmed, kept only when non-empty.
- `MediaUsageProvider`: yields each `audio` block's `path`, one per occurrence.
- Authorization unchanged: the existing form requests' `authorize()` (§1.3).

No events, listeners, routes or controllers.

### 2.4 Frontend

- `_audio-block.blade.php` (Editor) uses the plugin-partial contract: wrapped in `<x-editor::multi.block type="audio">`, so move/delete/insert chrome and the hidden `type` input come for free and `multi.blade.php` needs no new `elseif` (it already `@include`s `editorView()` for non-built-in-branch types, in both stored blocks and the hidden `<template data-block-template>`). Fields: hidden `path`; `<x-media::sound-field>` named `{name}[{uid}][sound]` with `removeName`, `accept="audio/mpeg"`, `maxSize` 10240, `previewUrl` = `originalUrl(path)` for a stored sound; a caption text input (`maxlength=255`).
- The sound-field's Alpine factory is pushed with `@once @push('scripts')` from inside the hidden template at page render, so cloned blocks find `mediaSoundField` registered; Alpine's mutation observer initialises the clone (same mechanism image-field relies on). Only VERIFY can confirm this.
- The forms' existing old-input rebuild already carries `type`, `path`, `caption`, so a failed validation re-renders audio blocks with no form change beyond adding `'audio'` to `blockTypes`.
- Read side: native player, `w-full`, caption under it. Shows inside `.rich-content` on the public pages via the existing `{!! $x->content !!}`.

### 2.5 Deptrac

No new edge. `EditorPrivate → MediaPublic` exists (image block); `NewsPrivate/StaticPagePrivate → MediaPublic, EditorPublic` exist.

### 2.6 Testing strategy

- Media (feature): `storePublicFile` stores on the public disk with an `.mp3` name and rejects a private scope; `gc` deletes an unclaimed old public `.mp3` and keeps a claimed one; `listByScope` never lists an `.mp3`; sound-field honours `removeName` and keeps the default.
- Editor (feature): renderer audio cases (URL, escaped caption, empty path skipped); multi component offers `audio` only when enabled and renders a stored audio block.
- Story (feature): a chapter POST carrying an `audio` block is rejected and stores nothing — the guard that chapters never render one.
- News / StaticPage (feature, real admin/moderator over HTTP with `Storage::fake('public')`): upload, caption, wrong type, too big, missing file, keep on round trip, remove-without-replacement, replace, forged path, usage provider, guest sees `<audio` on the public page, form shows the "Son" palette.
- VERIFY only: clone-and-init of the sound field inside a new block, preview playback, mobile width, the real public page player.

### 2.7 Tradeoffs locked

| # | Question | Options considered | Chosen | Why / what would flip it |
|---|----------|--------------------|--------|--------------------------|
| 1 | Where does the `audio` type live | Editor built-in · registered by News (and StaticPage) · new shared plugin | Editor built-in, opt-in via `blockTypes` | Two consumers, generic like `image`; Editor already depends on Media. Flip if a consumer needs a per-domain variant |
| 2 | Storage location | Same scope folder (`news/`) · dedicated folder/scope (`news-sounds/`) | Same scope folder | No new scope, provider and zero-claim guard reused; picker stays image-only by extension filter. Flip if more audio formats make the extension filter brittle |
| 3 | Guarding chapters against a forged audio block | Render-time filter (`render()` takes enabled types) · write-side whitelist (existing) | Write-side whitelist + a pinning test | Already in place in Story; a render filter changes the public API for all consumers. Flip if a consumer ever renders blocks it did not normalise itself |
| 4 | Sound-field reuse vs a bespoke input | Reuse with a `removeName` prop · new Editor-local field | Reuse | Same drag-drop/preview UX as Secret Gift/Fanart; one additive prop |
| 5 | Empty audio block | Silently drop (like image) · validation error | Validation error | §1.4 asks for it; a forgotten upload must not vanish silently |
| 6 | Submitted `path` trust | Trust (like image `path` today) · prefix + extension check | Check | A bad path renders a 500 (`originalUrl` throws on private) or a broken player; check is two lines |

### 2.8 Risks acknowledged

- **GC now deletes public `.mp3` files.** Limited to Media's managed public folders, 7-day grace, zero-claim guard. A consumer that later stores public sounds without claiming them in its provider would lose them — the Media AGENTS invariant is extended to say so.
- **Production upload limits unverified.** Dev `post_max_size` is 100M; several 10 MB sounds in one news POST could exceed the production nginx/PHP limit. Revisit if VERIFY or production shows a 413.
- **Public URL of draft content** (A5): file names are random 40-char hashes, not listed anywhere public. Revisit if a draft sound must stay secret.
- **Baked URLs in `content`** follow `APP_URL`, as images already do.

## 3. Plan

### 3.1 Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Media — public file storage, GC of public `.mp3`, sound-field `removeName` — **risky** | M | — | DONE |
| 1v | Checkpoint — Secret Gift and Fanart sound fields, image picker | S | 1 | DONE |
| 2 | Editor — built-in opt-in `audio` block type (+ Story guard test) | M | 1 | DONE |
| 3 | News — enable sound blocks | M | 2 | DONE |
| 4 | StaticPage — enable sound blocks | S | 3 | TODO |

5 rows, 4 BUILD phases + 1 checkpoint. Working agreement: one phase = one commit; failing test first; no phase N+1 before N's acceptance holds; re-ordering is surfaced, not taken silently.

### Phase 1 — Media: public file storage, GC of public `.mp3`, sound-field `removeName` (risky)

**Goal.** Media can store a raw public file, sweeps unclaimed public `.mp3` originals, keeps the image picker image-only, and the sound field can post its remove flag under a custom name. **Reads.** §2.3 (Media), §2.8.

**Deliverables.**
- `app/Domains/Media/Private/Services/MediaService.php` — `storePublicFile(string $scope, UploadedFile $file): string` (public disk, `putFileAs(folderFor($scope), $file, $file->hashName())`, `InvalidArgumentException` on a private scope); split the public original filter so `gc()` accepts `jpg|jpeg|png|webp|mp3` while `listByScope()` keeps images only (e.g. an `$includeFiles` flag on `originalsIn()` or a `GC_PUBLIC_EXTENSIONS` constant — implementer's choice, documented in the docblock).
- `app/Domains/Media/Public/Api/MediaPublicApi.php` — `storePublicFile()` pass-through with docblock ("caller validates; URL via `originalUrl`").
- `app/Domains/Media/Private/Resources/views/components/sound-field.blade.php` — prop `removeName` (default `null` → `"{$name}_remove"`), used for the hidden input's `name`; header comment updated.
- `app/Domains/Media/README.md`, `app/Domains/Media/AGENTS.md` — API list gains `storePublicFile`; invariant: public raw files have no variants, `.mp3` is the only non-image extension GC sweeps on the public disk, a consumer storing one must claim it in its provider; sound-field contract `{name}` + `removeName`.

**Tests.**
- `Media/Tests/Feature/MediaServiceTest.php`: `describe('storePublicFile')` — `it stores an mp3 on the public disk under the scope folder`, `it names the file from its MIME type, not the client name`, `it rejects a private scope`; in `describe('gc')` — `it deletes an unclaimed public mp3 older than the grace window`, `it keeps a claimed public mp3`, `it keeps an unclaimed public mp3 inside the grace window`; in `describe('listByScope')` — `it never lists an mp3`.
- `Media/Tests/Feature/MediaSoundFieldComponentTest.php`: `it posts the remove flag as {name}_remove by default`, `it posts the remove flag under removeName when given`.

**Acceptance.**
- ✅ `storePublicFile('news', $mp3)` returns `news/<hash>.mp3` present on the fake public disk; `storePublicFile('fanart/1', …)` throws.
- ✅ `gc()` deletes an unclaimed 8-day-old `news/x.mp3` and keeps a claimed one; existing image GC tests unchanged and green.
- ✅ `listByScope('news')` with an image and an mp3 present returns only the image.
- ✅ Fanart and Secret Gift call sites unchanged and their tests green.
- ✅ `pnpm run gate` green.

### Checkpoint 1v — Secret Gift and Fanart sound fields, image picker

Existing consumers of what phase 1 reshaped. In the running app: Fanart create and edit (`fanartiste` user) — pick, preview, cancel, delete an existing sound, submit; Secret Gift gift preparation (participant of a running activity) — upload and remove a sound; News admin edit — image reuse picker shows images only, nothing blank. Run `pnpm run e2e` specs touching fanart / secret gift if any exist. Screenshots under `shots/checkpoint-1v/`; append `**Result (<date>, HEAD <sha>) — PASS|FAIL**`.

**Result (2026-10-25, HEAD ea414359) — PASS**
- e2e `fanart-artwork-module` + `confirm-modal` specs: 13/13 green (no existing spec drives the sound field itself).
- Fanart create (reader, audio, as `confirmed`): drop zone, pick shows player + file, cancel resets input, submit with chapter shows "Votre fanart a été envoyé." Shots 01-04.
- Fanart edit (own audio artwork, as `author`; edit exists only for artworks): pick, cancel, replace and submit OK, no error banner. Shots 20-24. There is no preview URL on edit, so "delete an existing sound" is exercised on the gift field.
- Secret Gift (`cadeau-surprise-actif`, as `confirmed`, Son tab): pick shows 0:20 player, cancel resets, save shows saved sound + bin, bin sets `gift_sound_remove=true`, saving removes it and the flag is back to false. Shots 10-14.
- News admin create: image picker lists the image only; with a temp `news/zz-temp-1v.mp3` (removed afterwards) the `/media/library?scope=news` JSON has no mp3 and the modal has no blank tile. Shot 30.
- Note: the two fanart/gift uploads write into the e2e database only.

### Phase 2 — Editor: built-in opt-in `audio` block type (+ Story guard test)

**Goal.** `<x-editor::multi>` can offer a "Son" block to consumers that list `audio` in `blockTypes`, and `EditorPublicApi::render()` renders a stored audio block as an escaped native player; chapters still reject it. **Reads.** §2.2, §2.3 (Editor), §2.4, §2.7 #1/#3. Builds on phase 1: `MediaPublicApi::originalUrl()` (already existed) and `<x-media::sound-field removeName>`.

**Deliverables.**
- `app/Domains/Editor/Private/Blocks/AudioBlockType.php` — implements `EditorBlockType`; constructor `MediaPublicApi $media`; `render()` per §2.3 via `Blade::render` with escaped values, `''` when `path` empty.
- `app/Domains/Editor/Public/Providers/EditorServiceProvider.php` — register `$app->make(AudioBlockType::class)` after `ImageBlockType` in the singleton factory. Default `blockTypes` stays `['text','image']` (no change in `multi.blade.php` / `block.blade.php` props).
- `app/Domains/Editor/Private/Resources/views/components/multi/_audio-block.blade.php` — per §2.4: `<x-editor::multi.block type="audio" :name :uid>`, hidden `{name}[{uid}][path]`, `<x-media::sound-field>` (`name="{name}[{uid}][sound]"`, `remove-name="{name}[{uid}][sound_remove]"`, `accept="audio/mpeg"`, `:max-size="10240"`, `:removable="true"`, preview URL from `@inject` of `MediaPublicApi` when `$block['path']` set), caption input `{name}[{uid}][caption]` maxlength 255.
- `app/Domains/Editor/Private/Resources/lang/fr/multi.php` — `add_audio` ("Ajouter un son"), `audio_caption` ("Légende (facultative)"), `audio_player_label` ("Lecteur audio"), `audio_no_support`.
- `app/Domains/Editor/README.md` (block schema, built-ins list, `blockTypes` note that `audio` is opt-in), `app/Domains/Editor/AGENTS.md` (invariant: `audio` is a built-in emitting its own `<audio>` HTML because no Purifier profile allows it; renderer renders every registered type, so the guard against forged blocks is each consumer's write-side whitelist).

**Tests.**
- `Editor/Tests/Feature/ContentBlocksRendererTest.php`: `it renders an audio block as a native player with the public URL`, `it escapes the audio caption`, `it skips an audio block without a path`.
- `Editor/Tests/Feature/MultiEditorComponentTest.php`: `it does not offer the audio block by default`, `it offers the audio block when enabled`, `it renders a stored audio block with its path and preview`.
- `Editor/Tests/Feature/EditorBlockRegistryTest.php`: `it registers audio as the third built-in`.
- `Story/Tests/Feature/Chapters/ChapterAdvancedModeTest.php`: `it rejects an audio block in a chapter and stores nothing`.

**Acceptance.**
- ✅ `render([['type'=>'audio','path'=>'news/a.mp3','caption'=>'<b>x</b>']])` contains `<audio controls` with `/storage/news/a.mp3` and `&lt;b&gt;x&lt;/b&gt;`.
- ✅ The chapter form, News form and StaticPage form render no `data-block-template="audio"` (none enables it yet).
- ✅ A confirmed author's chapter update with an `audio` block gets a validation error; `content_blocks` unchanged.
- ✅ `pnpm run gate` green.

### Phase 3 — News: enable sound blocks

**Goal.** Moderators and admins can add, replace and remove sound blocks in a news article; guests hear them on the public page. **Reads.** §2.2, §2.3 (News / StaticPage), §2.4, §2.7 #5/#6. Builds on phases 1–2: `MediaPublicApi::storePublicFile()`, Media GC sweeping public `.mp3`, and Editor's opt-in `audio` block (partial posts `blocks[uid][path|sound|sound_remove|caption]`).

**Deliverables.**
- `app/Domains/News/Private/Resources/views/pages/admin/news/_form.blade.php` — `:blockTypes="['text', 'image', 'audio']"` on `<x-editor::multi>`.
- `app/Domains/News/Private/Requests/NewsRequest.php` — `blocks.*.type` in `text,image,audio`; `blocks.*.sound` `nullable|file|mimes:mp3|max:10240`; `blocks.*.sound_remove` `nullable`; messages `blocks.*.sound.mimes` / `.max`.
- `app/Domains/News/Private/Services/NewsService.php` — `audio` branch in `resolveContent()` per §2.3 (upload → `storePublicFile(self::SCOPE)`; else kept path guarded by `news/` prefix + `.mp3`, ignored when `sound_remove` is truthy via `filter_var(FILTER_VALIDATE_BOOLEAN)`; none → `ValidationException` on `blocks` with `news::admin.validation.audio_required`; caption trimmed, ≤255).
- `app/Domains/News/Private/Support/NewsMediaUsageProvider.php` — yield `audio` block paths.
- `app/Domains/News/Private/Resources/lang/fr/admin.php` — `validation.audio_required`, `validation.audio_type`, `validation.audio_max`, `validation.audio_path_invalid`.
- `app/Domains/News/README.md` — advanced mode lists sound blocks; provider claims them.

**Tests.** In `News/Tests/Feature/Admin/NewsAdvancedModeTest.php` (with `Storage::fake('public')`):
- `it stores an uploaded sound on the public disk and renders a player`
- `it keeps the sound caption`
- `it lets a moderator add a sound block`
- `it rejects a non-mp3 sound`, `it rejects a sound over 10 MB`
- `it rejects a sound block without a file`
- `it keeps the stored sound on a round trip without re-upload`
- `it rejects a sound removed without a replacement`
- `it replaces the sound with a new upload`
- `it rejects a sound path outside the news folder`
- in `describe('NewsMediaUsageProvider')`: `it claims sound block paths`
- `News/Tests/Feature/Admin/NewsFormRendersMultiEditorTest.php`: `it offers the sound block in advanced mode`
- `News/Tests/Feature/…` (public show test file, or the advanced mode file): `it shows the sound player to a guest on a published news`

**Acceptance.**
- ✅ An admin POST with a `blocks[b0][sound]` mp3 creates a news whose `content_blocks[0]` is `['type'=>'audio','path'=>'news/<hash>.mp3', …]`, file present on the public disk, `content` contains `<audio`.
- ✅ A `.wav`, an 11 MB mp3, or an audio block with neither file nor path returns a French validation error and creates nothing.
- ✅ A `path` of `fanart/1/x.mp3` or `news/x.jpg` on an audio block is rejected.
- ✅ A plain `user` gets 403 on the store route (existing rule, still true).
- ✅ `pnpm run gate` green.

### Phase 4 — StaticPage: enable sound blocks

**Goal.** Admins can add, replace and remove sound blocks in a static page; guests hear them on the published page. **Reads.** §2.2, §2.3 (News / StaticPage), §2.4, §2.7 #5/#6. Builds on phases 1–2 (as phase 3) and mirrors phase 3's News implementation (`NewsService::resolveContent()` audio branch is the reference).

**Deliverables.**
- `app/Domains/StaticPage/Private/Resources/views/pages/admin/_form.blade.php` — `:blockTypes="['text', 'image', 'audio']"`.
- `app/Domains/StaticPage/Private/Requests/StaticPageRequest.php` — same rules/messages as News.
- `app/Domains/StaticPage/Private/Services/StaticPageService.php` — same `audio` branch, scope `static-pages`, prefix `static-pages/`.
- `app/Domains/StaticPage/Private/Support/StaticPageMediaUsageProvider.php` — yield `audio` block paths.
- `app/Domains/StaticPage/Private/Resources/lang/fr/*.php` (the file holding the existing `blocks_required` message) — audio validation keys.
- `app/Domains/StaticPage/README.md`.

**Tests.** `StaticPage/Tests/Feature/Admin/StaticPageAdvancedModeTest.php`: `it stores an uploaded sound on the public disk and renders a player`, `it rejects a non-mp3 sound`, `it rejects a sound block without a file`, `it keeps the stored sound on a round trip without re-upload`, `it rejects a sound path outside the static-pages folder`, `it shows the sound player to a guest on a published page`; `StaticPage/Tests/Feature/Admin/StaticPageImageTest.php` (provider tests live there): `it claims sound block paths`; `StaticPageFormRendersMultiEditorTest.php`: `it offers the sound block in advanced mode`; `StaticPageAdvancedRequestTest.php`: `it forbids a moderator from saving a page with a sound`.

**Acceptance.**
- ✅ Admin creates a page with a sound; file under `static-pages/` on the public disk; guest sees `<audio` on the published page.
- ✅ A moderator gets 403 (unchanged rule).
- ✅ `pnpm run gate` green.

### 3.2 Visual QA checklist

Filled by VERIFY.

| Surface | Check | OK? |
|---------|-------|-----|
| News edit (admin), advanced mode | "Son" in palette and "+" insert menu; adding one shows the dropzone; picking an mp3 previews and plays | |
| News edit, new sound block cloned twice | Both blocks' sound fields work independently (Alpine init on clones) | |
| News save → public page (guest, logged out) | Native player full width, plays, caption under it | |
| News edit, existing sound | Preview of stored sound plays; delete → dropzone; pick another → save replaces | |
| News edit, delete current sound and save | French error "un bloc son doit contenir un fichier"; other blocks kept | |
| News edit, upload a .wav / > 10 MB | French validation error, form re-rendered with blocks | |
| News edit (moderator) | Can add a sound | |
| Static page edit (admin) | Same add / save / public player flow | |
| Chapter editor (author) | No "Son" in palette or insert menu | |
| Mode switch | "Simple" disabled while a sound block exists | |
| Mobile (375px) | Editor sound block and public player fit, no overflow | |
| Fanart create/edit, Secret Gift gift | Sound field unchanged (also covered by checkpoint 1v) | |

### 3.3 Open items

- Production `post_max_size` / nginx `client_max_body_size` for multi-sound posts — unverified (dev is 100M). Not blocking any phase; VERIFY notes a 413 if seen.
- Phase 3: the public news show test file name — the implementer picks the existing public News show test if there is one, else adds the case to `NewsAdvancedModeTest`.
- Phase 4: the exact StaticPage lang file holding `blocks_required` — read before adding keys.

## 4. Decisions

Append-only. Every question the user arbitrated, as given. BUILD reads this
before asking anything already settled. Never edit a row — add one that
supersedes it.

| # | Date | Step | Question | Decision | Supersedes |
|---|------|------|----------|----------|------------|
| 1 | 2026-10-25 | REFINE | Sound public; not in chapter editor | As in request (user-stated) | |

### Assumptions made without asking

| # | Assumption | Made at | Reversible? |
|---|------------|---------|-------------|
| A1 | Implemented as a new `audio` block type (one file + optional caption per block), enabled via `blockTypes` for News and StaticPage only | REFINE | Yes |
| A2 | Anyone who can edit news / static pages in advanced mode can add sounds; no new permission | REFINE | Yes |
| A3 | MP3 only, max 10 MB (same as Secret Gift) | REFINE | Yes |
| A4 | Optional caption, shown under the player | REFINE | Yes |
| A5 | Public means a public URL, served directly, usable by guests; draft pages' files are not protected | REFINE | Yes |
| A6 | No download link beyond the native player | REFINE | Yes |
| A7 | No domain event, notification, stats or search impact | REFINE | Yes |
| A8 | Orphan files cleaned by existing `media:gc` through News/StaticPage usage providers | REFINE | Yes |
| A9 | §1 corrected at DESIGN: moderators can add sounds to news (they already edit news; static pages stay admin/tech-admin); mode switch to simple is refused while a sound block exists (not "dropped"); replace = delete current sound in the field then pick another | DESIGN+PLAN | Yes |
| A10 | `audio` is an Editor **built-in**, opt-in via `blockTypes` (§2.7 #1) | DESIGN+PLAN | Yes |
| A11 | Sounds stored raw in the consumer's existing scope folder (`news/`, `static-pages/`), URL via `originalUrl`; new `MediaPublicApi::storePublicFile` (§2.7 #2) | DESIGN+PLAN | Yes |
| A12 | `media:gc` extended to public `.mp3` originals; image picker stays image-only | DESIGN+PLAN | Yes |
| A13 | No render-time block-type filter; chapters are guarded by Story's existing write-side whitelist, pinned by a test (§2.7 #3) | DESIGN+PLAN | Yes |
| A14 | `<x-media::sound-field>` gains `removeName` (PHP drops chars after `]`, so `blocks[x][sound]_remove` would clobber the file field) | DESIGN+PLAN | Yes |
| A15 | Submitted audio `path` must start with the consumer scope folder and end in `.mp3` (§2.7 #6) | DESIGN+PLAN | Yes |
| A16 | No reuse picker for sounds (added to §1.7) | DESIGN+PLAN | Yes |

## 5. Build & verify log

- **Phase 1 — DONE (2026-10-25).** `storePublicFile` on service + API; `originalsIn()` now takes its public filter explicitly (`PUBLIC_IMAGE_PATTERN` for `listByScope`, `PUBLIC_GC_PATTERN` = images + mp3 for `gc`); sound-field `removeName`. Tests: `MediaServiceTest` (storePublicFile ×3, gc public mp3 ×3, listByScope never lists an mp3 while gc deletes it), `MediaLibraryEndpointTest` (GET `/media/library` never lists an mp3), `MediaSoundFieldComponentTest` (default / custom remove name). Gate green.
- **Phase 2 — DONE (2026-10-25).** `AudioBlockType` built-in registered third, `_audio-block` partial (sound-field with `remove-name`), French labels, Editor README/AGENTS. Tests: renderer ×3, multi component ×3, registry, Story `ChapterAdvancedModeTest` guard. Gate green.
