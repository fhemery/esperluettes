# MultiEdit — advanced mode for static pages — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)
- Decisions (never reopen): [`DECISIONS.md`](./DECISIONS.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | `content_blocks` column, model cast, usage provider | S | — | DONE |
| 2 | `StaticPageService` advanced-mode resolution + deptrac edge | M | 1 | DONE |
| 3 | `StaticPageRequest` advanced rules (HTTP surface) | S | 2 | DONE |
| 4 | StaticPage admin form — MultiEdit + field reorder | M | 3 | DONE |
| 5 | News admin form — field reorder only | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/5)` resume correctly.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `npm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

### Standing constraints for every phase

- **This is a literal News mirror** (decision #1, #5). When in doubt about a
  detail, open the News file that does the same job and copy its shape rather
  than inventing a better one. Do **not** extract shared code into `Editor`;
  duplication in `StaticPage` is the decided design.
- Only two deliverables exist in this task (decision #4): StaticPage body →
  MultiEdit, and the admin field reorder on StaticPage **and** News. No new
  events, notifications, settings, search indexing or length limits; no backfill
  of existing rows; the StaticPage `PATCH` publish/unpublish routes are out of
  scope.
- StaticPage tests are Pest files with no class declaration, so they are
  invisible to deptrac — a test may reference a `Media\Private` class without
  needing a deptrac edge (`StaticPageImageTest` already does).

---

## Phase 1 — `content_blocks` column, model cast, usage provider

**Goal.** Give `static_pages` a nullable `content_blocks` JSON column, make the
model read/write it, and make the Media usage provider report the image paths
inside it — before anything can write one.

**Context.** Nothing of this feature exists yet. See `02-architecture.md` §2
(data model) and §1.1 → Media (usage provider stays a single provider covering
both header and body paths). Today `static_pages` stores only `content` (author
HTML) and `header_image_path`; `StaticPageMediaUsageProvider` yields only
`header_image_path`.

**Deliverables.**
- `app/Domains/StaticPage/Database/migrations/<YYYY_MM_DD_HHiiss>_add_content_blocks_to_static_pages_table.php`
  — `$table->json('content_blocks')->nullable()->after('content');` with a
  `down()` that drops the column. Copy
  `app/Domains/News/Database/migrations/2026_07_24_000000_add_content_blocks_to_news_table.php`
  verbatim, changing the table name. No index, no FK, no backfill.
- `app/Domains/StaticPage/Private/Models/StaticPage.php` — add
  `'content_blocks'` to the `#[Fillable]` attribute list and
  `'content_blocks' => 'array'` to `protected $casts`.
- `app/Domains/StaticPage/Private/Support/StaticPageMediaUsageProvider.php` —
  rewrite `usedPaths()` as a generator that yields (a) every non-null
  `header_image_path` and (b) every `path` of every block whose `type` is
  `image` inside every non-null `content_blocks`. Mirror
  `app/Domains/News/Private/Support/NewsMediaUsageProvider.php` exactly,
  including the duplicate-yield behaviour (the same path referenced twice is
  yielded twice). Update the class docblock: it now covers header **and** body
  block paths.

**Tests.**
- `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageImageTest.php` — extend
  the existing `describe('StaticPageMediaUsageProvider')` group:
  - `it('reports advanced body image paths alongside the header path')` — create
    one page via `StaticPage::factory()->create([...])` with
    `content_blocks` = `[['type' => 'text', 'html' => '<p>x</p>'], ['type' => 'image', 'path' => 'static-pages/body.jpg', 'alt' => 'A']]`
    and a `header_image_path`; assert both paths come out of `usedPaths()`.
  - `it('protects an advanced body image from GC')` — put
    `static-pages/body.jpg` and `static-pages/orphan.jpg` on the fake public
    disk, reference the first from a page's `content_blocks`, run
    `app(\App\Domains\Media\Private\Services\MediaService::class)->gc(-1)`, assert
    the referenced file survives and the orphan is gone. (Same shape as the
    existing header-image GC test right below it.)
  - The existing `it('reports every non-null header image path, grandfathered
    dated ones included')` asserts `toHaveCount(2)` — it must still pass, i.e.
    pages with `content_blocks = null` must contribute nothing.

**Acceptance.**
- ✅ `static_pages.content_blocks` exists, is nullable JSON, and every existing
  row has `NULL` after migrating (no backfill).
- ✅ `StaticPage::create([... 'content_blocks' => [...]])` persists the array and
  reads it back as a PHP array.
- ✅ `StaticPageMediaUsageProvider::usedPaths()` yields a body image path stored
  in `content_blocks`, and Media GC no longer collects that file.
- ✅ `migrate:rollback` of this migration drops the column cleanly.
- ✅ `npm run gate` green.

---

## Phase 2 — `StaticPageService` advanced-mode resolution + deptrac edge

**Goal.** Teach `StaticPageService` to branch on `mode`: keep today's Simple
behaviour untouched, and in Advanced build the normalized block list, store body
images through Media, render the HTML cache into `content`, and persist the
blocks.

**Context.** Phase 1 landed the nullable `static_pages.content_blocks` JSON
column, added it to the model's `#[Fillable]` and cast it to `array`, so the
service can now assign it. Nothing yet writes it. See `02-architecture.md` §3.2
(the `resolveContent` contract and the create/update sequencing) and §5 (the one
new deptrac edge). The reference implementation to mirror line for line is
`app/Domains/News/Private/Services/NewsService.php` — `resolveContent()` plus
the first few lines of `create()` / `update()`.

**Deliverables.**
- `app/Domains/StaticPage/Private/Services/StaticPageService.php`
  - Add `private readonly EditorPublicApi $editor` to the constructor, beside
    the existing `EventBus` and `MediaPublicApi`
    (`App\Domains\Editor\Public\Api\EditorPublicApi`).
  - Add `private function resolveContent(array $data): array` returning
    `['content' => string, 'content_blocks' => ?array]`, copied from
    `NewsService::resolveContent()` with two substitutions: the existing
    `$this->sanitizeContent()` (Purifier `admin-content` + `HtmlLinkUtils`) stays
    the Simple branch — do **not** switch Simple to an Editor profile (tradeoff
    #3) — and the two `ValidationException` messages become
    `static::admin.validation.blocks_required` and
    `static::admin.validation.image_alt_required`.
  - Advanced branch behaviour, unchanged from News: split `blocks_order` on
    commas; walk uids in that order into `blocks[uid]`; `text` →
    `EditorPublicApi::sanitizeText()` (default `multiedit-text` profile), drop
    the block when `trim(strip_tags(...)) === ''`; `image` → new
    `UploadedFile` stored via `$this->media->store(self::SCOPE, $file, $keep ? [] : [400, 800])`,
    otherwise the reused `path`, forcing `keep_original` when
    `!$this->media->hasVariants($path)`, dropping the block when there is no
    path, and throwing when `alt` is empty; throw when no block survives; finally
    `content = $this->editor->render($blocks)`. `self::SCOPE` is already
    `'static-pages'` — do not add a second scope.
  - In `create()` and `update()`, replace the
    `$data['content'] = $this->sanitizeContent($data['content'] ?? '')` line with
    the News sequence: call `resolveContent($data)`, assign `content` and
    `content_blocks`, `unset($data['blocks'], $data['blocks_order'], $data['mode'])`,
    then the existing `resolveHeaderImage` / `unset($data['header_image'])`
    block, unchanged and still after the content step.
- `app/Domains/StaticPage/Private/Resources/lang/fr/admin.php` — add to
  `validation`: `'blocks_required' => 'Le contenu doit comporter au moins un bloc.'`
  and `'image_alt_required' => 'Chaque image doit avoir un texte alternatif.'`
  (the exact French strings News uses).
- `deptrac.yaml` — add `EditorPublic` to the `StaticPagePrivate` ruleset (it
  currently lists `Shared`, `AuthPublic`, `EventsPublic`, `StaticPagePublic`,
  `MediaPublic`). This is the only new edge; `NewsPrivate` already has it.

**Tests.**
- New `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageAdvancedModeTest.php`,
  Pest, modelled on
  `app/Domains/News/Tests/Feature/Admin/NewsAdvancedModeTest.php`, calling
  `app(StaticPageService::class)` directly with `Storage::fake('public')`:
  - `it('persists blocks and renders the content cache')` — one text + one
    reused image block that has variants on disk; assert two stored blocks,
    `content` contains the text, the `-800w.webp` variant URL and the caption.
  - `it('stores uploaded image blocks through Media')` — path starts with
    `static-pages/` and the file exists on the fake disk.
  - `it('stores a keep-original image without variants and flags the block')`.
  - `it('forces raw rendering when reusing an image that has no variants')`.
  - `it('drops empty blocks on save')`.
  - `it('respects the submitted block order')`.
  - `it('rejects an image block without alt text')` → `ValidationException`.
  - `it('rejects advanced content with no surviving block')` → `ValidationException`.
  - `it('keeps simple mode unchanged (no blocks, sanitized content)')` —
    `content_blocks` null, `content` still Purifier-`admin-content` output with
    external links given `target="_blank"` (this is the regression guard for
    existing pages).
  - `it('switches an advanced page back to simple')` — update with
    `mode => 'simple'`; `content_blocks` becomes null and `content` is the new
    HTML.
- Existing `StaticPageControllerTest` and `StaticPageImageTest` must still pass
  untouched — they submit no `mode`, so they exercise the Simple branch.

**Acceptance.**
- ✅ `StaticPageService::create()` with `mode => 'advanced'` stores a normalized
  block array in `content_blocks` and the rendered HTML in `content`.
- ✅ An advanced save with an image block whose `alt` is blank throws
  `ValidationException` on the `blocks` key with the French message.
- ✅ An advanced save whose blocks all turn out empty throws
  `ValidationException` on the `blocks` key.
- ✅ A save with no `mode` (or `mode => 'simple'`) produces byte-identical
  `content` to what today's code produces, and `content_blocks` null.
- ✅ Updating an advanced page with `mode => 'simple'` nulls `content_blocks`.
- ✅ `./vendor/bin/sail composer deptrac` reports zero violations with the single
  added `StaticPagePrivate → EditorPublic` edge.
- ✅ `npm run gate` green.

---

## Phase 3 — `StaticPageRequest` advanced rules (HTTP surface)

**Goal.** Let an advanced payload through validation over HTTP, with the same
shape rules News uses, so the admin endpoints can actually save blocks.

**Context.** Phase 2 taught `StaticPageService` to branch on `mode`, but nothing
reaches it over HTTP yet: `StaticPageRequest` still requires `content` and
strips `mode` / `blocks` / `blocks_order` out of `validated()`. See
`02-architecture.md` §3.5 (the rules, and the enforcement split: FormRequest for
shape, service for empty-blocks and required alt). The mirror is
`app/Domains/News/Private/Requests/NewsRequest.php::rules()`.

**Deliverables.**
- `app/Domains/StaticPage/Private/Requests/StaticPageRequest.php`
  - `$isAdvanced = $this->input('mode') === 'advanced';`
  - Always: `'mode' => ['nullable', Rule::in(['simple', 'advanced'])]`.
  - Advanced branch: `content` → `['nullable', 'string']`; `blocks_order` →
    `['nullable', 'string']`; `blocks` → `['required', 'array', 'min:1']`;
    `blocks.*.type` → `['required', Rule::in(['text', 'image'])]`;
    `blocks.*.html` → `['nullable', 'string']`; `blocks.*.path` →
    `['nullable', 'string', 'max:1024']`; `blocks.*.alt` →
    `['nullable', 'string', 'max:255']`; `blocks.*.caption` →
    `['nullable', 'string', 'max:255']`; `blocks.*.keep_original` →
    `['nullable']`; `blocks.*.file` → `['nullable', 'image', 'max:2048']`.
  - Simple branch: `content` → `['required', 'string']`, exactly as today.
  - Everything else (title, slug uniqueness ignoring the route model,
    summary, `header_image[.file|.path]`, status, meta_description,
    `authorize()`, `messages()`) is untouched. Alt stays soft here — the service
    is what rejects a blank alt (decision #1: no stricter validation than News).
- No controller change: `store()` / `update()` already pass
  `$request->validated()` straight into the service.

**Tests.**
- New `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageAdvancedRequestTest.php`
  (Pest, `uses(TestCase::class, RefreshDatabase::class)`, `Storage::fake('public')`),
  hitting the real routes as `admin($this)`:
  - `it('saves an advanced page through the admin store endpoint')` — POST
    `route('static.admin.store')` with `mode => 'advanced'`,
    `blocks_order => 'b0,b1'` and a text + an uploaded image block with alt;
    assert a redirect to `route('static.admin.index')`, no session errors, and
    the persisted row has two blocks and a non-empty `content`.
  - `it('saves an advanced page through the admin update endpoint')` — same over
    PUT `route('static.admin.update', $page)`.
  - `it('rejects an advanced payload with no blocks')` — POST with
    `mode => 'advanced'` and no `blocks`; `assertSessionHasErrors(['blocks'])`.
  - `it('still requires content in simple mode')` — POST without `content` and
    without `mode`; `assertSessionHasErrors(['content'])`.
  - `it('surfaces the missing-alt error from the service')` — advanced POST with
    an image block and a blank alt; `assertSessionHasErrors(['blocks'])` and no
    row created.
  - `it('denies a non-admin posting an advanced payload')` — as
    `alice($this, [], true, [Roles::USER_CONFIRMED])`, POST an advanced payload;
    assert redirect to `route('dashboard')` and that no page and no uploaded
    file were created. **This is the security test for this phase — it lands
    here, with the endpoint that first accepts blocks, not later.**

**Acceptance.**
- ✅ An admin can create and update a static page in advanced mode over HTTP,
  and the blocks are persisted.
- ✅ `mode => 'advanced'` with zero blocks is rejected by the FormRequest.
- ✅ Simple mode still requires `content` (existing behaviour unchanged).
- ✅ A `user-confirmed` account posting an advanced payload gets redirected to
  the dashboard, creates no page, and writes no file to the Media disk.
- ✅ `npm run gate` green.

---

## Phase 4 — StaticPage admin form — MultiEdit + field reorder

**Goal.** Replace the StaticPage admin body field with `<x-editor::multi>` and
put the five fields in the decided order, header image included.

**Context.** Phases 1–3 landed the column, the service branch and the request
rules, so a form that submits `mode`, `blocks[…]` and `blocks_order` will now be
validated and saved end to end. The form still renders `<x-editor::rich-text>`
and keeps the header image in a separate "Média" section below the content. See
`02-architecture.md` §4 (component props, mode default, failed-validation
rebuild, field order) and functional §4.1–§4.3 and §4.6. The mirror is
`app/Domains/News/Private/Resources/views/pages/admin/news/_form.blade.php`.

**Deliverables.**
- `app/Domains/StaticPage/Private/Resources/views/pages/admin/_form.blade.php`
  - Reorder the fields inside the "Contenu" section to **title → slug → header
    image → summary → body**. Title and slug stay in their existing two-column
    grid; the header image, summary and body follow as full-width blocks in that
    order.
  - Move the `<x-media::image-field name="header_image" scope="static-pages" :show-alt="false" :show-caption="false" …>`
    call (and its `header_image.file` error line) up into that position,
    unchanged otherwise, and **delete the now-empty standalone "Média" section
    wrapper** (assumption #3).
  - `app/Domains/StaticPage/Private/Resources/lang/fr/admin.php` — remove
    `form.media_section`. That heading is its only reference in the codebase
    (verified during PLAN), so the key becomes dead with the section.
  - Replace `<x-editor::rich-text name="content" …>` with:

    ```blade
    <x-editor::multi
        name="blocks"
        content-name="content"
        :content-value="old('content', $page?->content ?? '')"
        :blocks="$meBlocks"
        :mode="$meMode"
        scope="static-pages"
        toolbar="editorial"
        :nbLines="15"
    />
    ```

    `:nbLines="15"` keeps the writing surface at the height the current
    rich-text field has; everything else mirrors News.
  - Add the News `@php` preamble above it, adapted to `$page`: `$meBlocks =
    $page?->content_blocks ?? []`, rebuilt from `old('blocks')` +
    `old('blocks_order')` when `old('mode') === 'advanced'` (uploaded files are
    not repopulated by the browser and must be re-picked), and
    `$meMode = old('mode', ($page?->content_blocks ? 'advanced' : 'simple'))`.
  - Render both error lines below the editor:
    `$errors->get('content')` and `$errors->get('blocks')`.
  - The "Paramètres" section (status, meta description) and the actions row stay
    exactly where they are (assumption #4 / tradeoff #4).
- The public view (`pages/show.blade.php`) is **not** touched: it already prints
  `{!! $page->content !!}` inside `.static-content`, which is what the rendered
  block cache feeds, exactly as News does with `.news-content`.

**Tests.**
- New `app/Domains/StaticPage/Tests/Feature/Admin/StaticPageFormRendersMultiEditorTest.php`,
  modelled on
  `app/Domains/News/Tests/Feature/Admin/NewsFormRendersMultiEditorTest.php`:
  - `it('renders the create form with the multi-editor wired')` — GET
    `route('static.admin.create')` as `admin($this)`; `assertSee('multiEditor(', false)`,
    `assertSee('name="mode"', false)`, `assertSee('name="blocks_order"', false)`.
  - `it('opens an advanced page in advanced mode')` — a page created with
    `content_blocks => [['type' => 'text', 'html' => '<p>Body</p>']]`; GET
    `route('static.admin.edit', $page)`; `assertSee("mode: 'advanced'", false)`
    and `assertSee('name="blocks[b0][html]"', false)`.
  - `it('opens a legacy simple page in simple mode with its content intact')` —
    a factory page with `content_blocks` null; assert `mode: 'simple'` and that
    the stored HTML is present.
  - `it('orders the fields title, slug, header image, summary, body')` — on both
    the create and the edit form, `assertSeeInOrder(['name="title"',
    'name="slug"', 'name="header_image[path]"', 'name="summary"',
    'multiEditor('], false)`.
  - `it('no longer renders a standalone media section heading')` —
    `assertDontSee(__('static::admin.form.media_section'))`.

**Acceptance.**
- ✅ The StaticPage create form renders the MultiEdit component in Simple mode
  with the `editorial` toolbar and the `static-pages` media scope.
- ✅ A page with non-null `content_blocks` opens in Advanced with its blocks
  pre-filled; a page with null `content_blocks` opens in Simple with its HTML.
- ✅ Both create and edit forms emit the fields in the order title → slug →
  header image → summary → body, and no "Média" section heading remains.
- ✅ A failed validation on an advanced submit re-renders the form still in
  Advanced with the submitted blocks rebuilt from old input.
- ✅ `npm run gate` green.

---

## Phase 5 — News admin form — field reorder only

**Goal.** Put the News admin form's five fields in the same order as StaticPage:
title → slug → header image → summary → body.

**Context.** This phase is independent of phases 1–4 — it touches no PHP and no
other domain. Today
`app/Domains/News/Private/Resources/views/pages/admin/news/_form.blade.php`
renders title → slug → summary → body (already `<x-editor::multi>`) → `<hr>` →
header image → `<hr>` → status/pinned → meta description. See
`02-architecture.md` §1.1 → News and §4 (field order), decisions #2 and #3.

**Deliverables.**
- `app/Domains/News/Private/Resources/views/pages/admin/news/_form.blade.php` —
  move the header-image `<div>` (the `<x-media::image-field name="header_image" scope="news" …>`
  call plus its `header_image.file` error line) so it sits directly after the
  slug block and before the summary block. Keep its markup byte-identical; only
  its position changes. Re-place the surrounding `<hr class="border-border" />`
  separators so the form still reads as: the five fields, then a rule, then
  status/pinned, then meta description, then submit. Nothing else on this form
  moves (tradeoff #4).
- No change to `NewsRequest`, `NewsService`, the News model, routes, the usage
  provider, or any News lang file. If a News test asserts the old DOM order,
  update the assertion, not the service (risk table, `02-architecture.md` §9).

**Tests.**
- `app/Domains/News/Tests/Feature/Admin/NewsFormRendersMultiEditorTest.php` —
  add `it('orders the fields title, slug, header image, summary, body')`: as
  `admin($this)`, GET `route('news.admin.create')` and
  `route('news.admin.edit', $news)`, then `assertSeeInOrder(['name="title"',
  'name="slug"', 'name="header_image[path]"', 'name="summary"', 'multiEditor('], false)`.
- Run the whole News suite: `./vendor/bin/sail artisan test app/Domains/News`.
  No behavioural assertion may change.

**Acceptance.**
- ✅ Both News admin forms emit the fields in the order title → slug → header
  image → summary → body.
- ✅ Creating and editing a News article (simple and advanced) behaves exactly as
  before — the existing News feature tests pass unmodified except for any purely
  order-based assertion.
- ✅ `npm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| StaticPage admin — create form, admin | Fields appear in the order title → slug → header image → summary → body; no standalone "Média" section; body starts in Simple with the editorial toolbar | |
| StaticPage admin — create form, empty state | A brand-new page shows an empty Simple body, no blocks, and saves with `content_blocks` null | |
| StaticPage admin — switch to Avancé | Existing body HTML becomes exactly one text block, nothing lost | |
| StaticPage admin — add an image block | Upload works, the reuse picker lists `static-pages` images, alt is required, caption renders | |
| StaticPage admin — reorder / delete blocks | Move up/down and delete behave as on News; order survives save | |
| StaticPage admin — Simple control disabled | With 2+ blocks or any image block, the "Simple" control is disabled and shows the existing French tooltip | |
| StaticPage admin — back to Simple | With exactly one text block, switching to Simple and saving clears `content_blocks` and keeps the text | |
| StaticPage admin — validation error re-render | Advanced submit with a blank alt: French error shown, form still Advanced, blocks rebuilt (image file must be re-picked) | |
| StaticPage admin — legacy page (pre-feature row) | A page created before this feature opens in Simple with its content intact and saves unchanged | |
| StaticPage admin — tech-admin | A `tech-admin` account sees and can use the same form as `admin` | |
| StaticPage admin — non-admin | A `user-confirmed` account hitting `/admin/...` static page URLs is redirected to the dashboard | |
| StaticPage public — advanced page, published | Header image plus interleaved text and images render inside `.static-content`; images are responsive (or raw for keep-original) | |
| StaticPage public — simple page, guest | An untouched pre-existing page renders exactly as before | |
| StaticPage public — draft preview | Admin sees a draft advanced page with the draft badge; a guest gets the usual not-found | |
| StaticPage — unpublish / republish | Blocks and mode survive the round trip; the public page disappears and comes back | |
| StaticPage — delete | Page gone from the public site; body images are still on disk (GC reclaims later) | |
| StaticPage admin — mobile (narrow viewport) | Block controls, the mode toggle and the image picker are usable on a phone-width screen | |
| News admin — create/edit form | New field order; an existing advanced article still opens in Advanced and saves unchanged | |
| News public — article | An advanced article renders exactly as it did before the reorder | |

## Open items

Verified during PLAN, so **not** open: `static_pages.content` is `longText`
(wide enough for the rendered block cache); the admin layout flushes
`@stack('scripts')`, so `<x-editor::multi>` boots on the StaticPage admin page
the same way it does on News; `static::admin.form.media_section` has exactly one
reference (the section heading phase 4 deletes); and no News test or `e2e/`
spec asserts the admin form's field order, so phase 5 breaks nothing.

- **Phase 4 — mode toggle affordances are browser-only.** The Simple control's
  disabled state and its French tooltip (functional §4.3) come from the shared
  Alpine `multiEditor` component; no PHP test can see them. Phase 4's tests
  cover the server-rendered shape only — the toggle behaviour is checked at
  VERIFY (see the checklist rows). If it turns out the shared component gates
  Simple on something News-specific, that is a finding to surface, not to patch
  inside StaticPage.
- **Phase 2 — Purifier `admin-content` profile vs `multiedit-text`.** Simple
  mode keeps `admin-content` and Advanced text blocks use Editor's
  `multiedit-text` (tradeoff #3), so a page switched from Simple to Advanced may
  lose tags `admin-content` allows but `multiedit-text` does not. News has the
  same seam and it was accepted there; confirm the loss is limited to the same
  tag set rather than assuming it, and report anything surprising instead of
  widening a profile.
