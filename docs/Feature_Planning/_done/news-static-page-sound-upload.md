# Sound upload for news and static pages

**Status:** DONE — 2026-10-25 · **Domain(s):** `Media`, `Editor`, `News`, `StaticPage` (Story: guard test only)

## What it does

In advanced mode (`<x-editor::multi>`) of a news article or static page, the editor can add a "Son" block: one uploaded MP3 (max 10 MB) plus an optional caption. It renders as a native `<audio controls>` player with a visible `<figcaption>`. Sounds are public (plain URL, no auth) and stored raw in the consumer's existing scope folder on the public disk. The chapter editor never offers it and never renders it.

## Key behaviour

- Visibility: published pages with their sounds are visible to everyone, guests included. Draft pages' file URLs are public by nature (random 40-char hash names, not listed anywhere).
- Who can add: whoever can already edit — news: moderator, admin, tech-admin; static pages: admin, tech-admin. No new permission. A plain `user` or a moderator on the static-page admin routes is **redirected to the dashboard** by route middleware, not 403.
- Lifecycle: delete a page / remove a block / replace a file only changes `content_blocks`; the file becomes unclaimed and `media:gc` deletes it after the 7-day grace window.
- Empty sound block (no file, or current sound removed with no replacement) is a **validation error** (`ValidationException` on `blocks`), unlike an empty image block which is dropped silently.
- Switching back to simple mode is refused while a sound block exists (`NewsService::update()` / `StaticPageService::update()`, key `audio_mode_switch` on `mode`); the UI also disables "Simple".
- Block shape: `['type'=>'audio','path'=>'news/<hash>.mp3','caption'=>'…']`. No migration.
- The submitted `path` of a kept sound must start with the consumer's scope folder and end in `.mp3`; otherwise rejected (a crafted path would 500 in `originalUrl` or play nothing).
- Replace = delete the current sound in the field, then pick another. Browsers never repopulate file inputs, so after a validation error a newly picked file must be re-picked; path and caption survive.
- Sounds use the form key `blocks.*.sound` (not `file`, which is the image rule) and `blocks.*.sound_remove`.

## Where the code lives

| Concern | Path |
|---------|------|
| Public file storage | `Media/Public/Api/MediaPublicApi::storePublicFile()` → `MediaService::storePublicFile()` |
| GC of public mp3 | `MediaService::gc()` with `PUBLIC_GC_PATTERN` (images + mp3); `listByScope()` uses `PUBLIC_IMAGE_PATTERN` |
| Block type | `Editor/Private/Blocks/AudioBlockType.php` (built-in, registered third in `EditorServiceProvider`) |
| Editor partial | `Editor/Private/Resources/views/components/multi/_audio-block.blade.php` |
| Sound field | `Media/.../components/sound-field.blade.php` (`removeName` prop) |
| News | `NewsRequest`, `NewsService::resolveContent()` audio branch, `NewsMediaUsageProvider`, `_form.blade.php` (`:block-types`) |
| StaticPage | `StaticPageRequest`, `StaticPageService::resolveContent()`, `StaticPageMediaUsageProvider`, `_form.blade.php` |
| Tests | `Media/Tests/Feature/{MediaServiceTest,MediaLibraryEndpointTest,MediaSoundFieldComponentTest}`, `Editor/Tests/Feature/{ContentBlocksRenderer,MultiEditorComponent,EditorBlockRegistry}Test`, `News/.../NewsAdvancedModeTest`, `StaticPage/.../StaticPageAdvancedModeTest`, `Story/.../ChapterAdvancedModeTest` (forged audio block rejected) |
| Migrations | none |

## Extension points used

- Editor block registry: `audio` built-in, **opt-in** per consumer via `blockTypes` (default stays `['text','image']`).
- Media usage provider: News and StaticPage providers also yield `audio` block paths (one per occurrence).

## Decisions worth remembering

- `audio` is an Editor built-in, not registered by News: two consumers, generic like `image`. It emits its own escaped HTML because no Purifier profile allows `<audio>`.
- No render-time filter of block types: `ContentBlocksRenderer` renders every registered type. Chapters are guarded on the **write** side (Story's `Rule::in(['text','image','chapter-choice'])` and `ChapterContentResolver`); a test pins it. Revisit if a consumer ever renders blocks it did not normalise.
- Sounds live in the same scope folder as images (`news/`, `static-pages/`); the picker is image-only by extension filter. `originalsIn()` has no default pattern on purpose: every caller picks images-only or images+mp3.
- `<x-media::sound-field>` gained `removeName`: PHP drops chars after `]`, so `blocks[b0][sound]_remove` would overwrite the file field with `"false"`. The audio block passes `{name}[{uid}][sound_remove]`.
- Moderators may add sounds to news (user-approved at DESIGN).
- GC now deletes public `.mp3`: any future consumer storing a public sound must claim it in its usage provider (recorded in Media's AGENTS invariants).

## Traps

- Rendering an audio block in a test via `$this->blade()` needs `view()->share('errors', new ViewErrorBag())` (sound-field reads `$errors`).
- A static page created through the service is not in the slug-map cache until `publish()`; guest-view tests must publish.
- The form prop is `:block-types` (kebab) on `<x-editor::multi>`.
- The StaticPage public `<article>` needed `w-full` (News has it) or the player shrink-wrapped to ~220px.

## Plan vs code

- Plan said a plain `user` / moderator on the store route gets 403 (News, StaticPage); the code redirects to the dashboard (route middleware). Tests assert the redirect.
- Mode-switch refusal lives in the services' `update()`, a place the plan did not name. Path check also rejects `..`.
- Phase 4 added a fix commit (`w-full` on the StaticPage show article) found by VERIFY.

## Not done

- Non-goals: sounds in the chapter editor, in simple (Quill) mode or inside text blocks; formats other than MP3, transcoding, waveform, download link, autoplay, playlists; a sound reuse picker (the image picker stays image-only); private sounds; sound for FAQ/comments/stories.
- Unverified in production, pushed to the backlog: [`news-sound-production-check`](../news-sound-production-check/00-request.md) — HTTP Range/seeking on public mp3s behind nginx (the PHP dev server has no Range, so seeking was never asserted on public pages), and upload limits (413) for a save carrying several 10 MB sounds (dev `post_max_size` is 100M).
- Cosmetic, not fixed: in the editor on mobile the sound-field cancel/bin icon sits tight against the block's delete column; a missing-file error shows only in the top banner, no disabled-state hint.
- e2e: the temporary spec `e2e/tests/features/news-static-page-sound-upload.spec.ts` (10 tests, green at VERIFY) and its page object `SoundBlocksFormPage` were **deleted** — a feature-local flow, covered by feature tests. It uploaded real mp3s into `storage/app/public`.
- Not driven in VERIFY: Secret Gift / Fanart sound fields after the Media change (checkpoint 1v covered them: PASS).
