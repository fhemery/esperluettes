# MultiEdit — advanced mode for static pages

> WRAP output — the compact record of the finished feature.

**Status:** DONE — 2026-08-01 · **Domain(s):** `StaticPage`, `News`

## What it does

Static pages get the same Simple / Avancé body editor News already has:
`<x-editor::multi>` on the admin form, with Advanced blocks stored in
`static_pages.content_blocks` and rendered HTML in `content`. Existing rows stay
Simple (`content_blocks` null) until an admin toggles them — no backfill. The
public show view was not touched; it still prints `{!! $page->content !!}`.

Separately, both StaticPage and News admin create/edit forms now order fields
**title → slug → header image → summary → body**. StaticPage dropped its
standalone "Média" section once the header sat mid-form.

## Key behaviour

- **Roles unchanged:** only `admin` / `tech-admin` edit; readers see no mode chrome.
- **Mode is derived, not stored:** non-null `content_blocks` ⇒ Advanced on next
  edit; null ⇒ Simple. The form posts `mode`; the DB has no `mode` column.
- **Literal News mirror** for validation, block resolution, alt-required, empty
  drops, and Simple↔Avancé gates — not a shared Editor helper (decision #5).
- **Simple sanitizer stays `admin-content` + `HtmlLinkUtils`.** Advanced text
  blocks use Editor's default `multiedit-text`. Switching Simple → Advanced can
  drop tags the old profile allowed (same seam as News).
- **One Media scope** `static-pages` for header and body images.
  `StaticPageMediaUsageProvider` yields both, or GC will delete live body images.
- **Delete / unpublish:** unchanged; blocks travel with the row; GC reclaims
  unused paths after the provider stops yielding them.
- Admin field order on News is layout-only — no News PHP or MultiEdit behaviour
  change beyond the header-image move.

## Where the code lives

| Concern | Path |
|---------|------|
| Model / column | `app/Domains/StaticPage/Private/Models/StaticPage.php` (`content_blocks`) |
| Migration | `…/Database/migrations/2026_08_01_000000_add_content_blocks_to_static_pages_table.php` |
| Service | `…/Private/Services/StaticPageService.php` (`resolveContent`) |
| Request | `…/Private/Requests/StaticPageRequest.php` (rules branch on `mode`) |
| Media GC | `…/Private/Support/StaticPageMediaUsageProvider.php` |
| Admin form | `…/Private/Resources/views/pages/admin/_form.blade.php` |
| News form reorder | `app/Domains/News/…/views/pages/admin/news/_form.blade.php` |
| Component | `<x-editor::multi>` (`Editor` domain) |
| Deptrac | `StaticPagePrivate → EditorPublic` |
| Tests | `StaticPage{AdvancedMode,AdvancedRequest,FormRendersMultiEditor,Image}Test.php`; News form order assertion in `NewsFormRendersMultiEditorTest.php` |

## Extension points used

- **MediaUsageRegistry** — same `StaticPageMediaUsageProvider`, now also walks
  image blocks in `content_blocks` (duplicate yields allowed, as News).
- **EditorPublicApi** — `sanitizeText` / `render` (default `multiedit-text`).
- No new events, notifications, settings, or search indexing.

## Decisions worth remembering

- **#1 / #5** Copy News's service / request / form / usage pattern into
  StaticPage; do not extract shared MultiEdit persistence into Editor.
- **#3 / #4** Field order title → slug → header → summary → body on StaticPage
  **and** News; only those two deliverables in this task.
- **#6** No backlog row for a shared `resolveContent` until a third News-like
  consumer (e.g. FAQ adopts blocks).
- **#7** Phase 4 "no Média heading" test asserts the raw lang key string
  (`APP_LOCALE=zz`), not `__('static::admin.form.media_section')` after the key
  was deleted — `TranslationKeysExistTest` rejects dead `__()` references.

## Not done

Deliberate non-goals: Chapters/FAQ MultiEdit; stricter
validation than News; public show/summary layout changes; new events /
notifications / settings / search / length limits; backfill of existing pages;
any News behaviour beyond field reorder.

Cut mid-build / open: none. No leftover backlog rows.

**e2e specs retired (deleted):** `e2e/tests/features/multiedit-static-pages.spec.ts`
plus its VERIFY-only scaffolding (`StaticPageAdminPage`, `E2eStaticPageSeeder`,
`STATIC_PAGE` fixture, unused `MultiEditor` image helpers). Shared Alpine
add/reorder/delete stays in `e2e/tests/core/multi-editor.spec.ts`. Persistence,
auth, field order and public HTML stay in StaticPage/News PHP tests — nothing
here earned a permanent core slot beyond what chapters already promoted.
