# MultiEdit — advanced mode for static pages — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**StaticPage** owns body MultiEdit (column, request rules, service resolution,
admin form, usage-provider extension). **News** is touched only for admin form
field order — no News PHP/API change. **Editor** and **Media** are consumed via
existing public APIs; neither gains a new extension point in this task.

Rationale: StaticPage already stores long-form admin HTML and a header image
under Media scope `static-pages`. MultiEdit is content authorship owned by the
page’s domain, exactly as News owns its own `resolveContent`. A shared Editor
persistence helper was considered and rejected for this task (decision #5–#6).

### 1.1 Changes in other domains

#### News

- Admin create/edit Blade form only: reorder fields to
  **title → slug → header image → summary → body**.
- No change to `NewsRequest`, `NewsService`, model, routes, or usage provider.

#### Editor

- Direct call only: StaticPage injects `EditorPublicApi` for advanced-mode
  `sanitizeText` / `render` (defaults: profile `multiedit-text`), same as News.
- No new Editor public methods, components, or lang keys.

#### Media

- Direct call only: existing `MediaPublicApi::store` / `hasVariants` with scope
  `static-pages` for header and body image blocks.
- Extend the existing `StaticPageMediaUsageProvider` to also yield Advanced body
  image paths (same provider, both sources — no new registry entry).

## 2. Data model

### 2.1 Tables

`static_pages` — add one column (mirror `news.content_blocks`):

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| `content_blocks` | JSON | yes | `NULL` ⇒ Simple mode; non-null ⇒ Advanced SoT; `content` holds rendered HTML cache |

Placed after `content`. No index (not queried by block shape). No FK. No backfill —
existing rows stay `NULL` (Simple).

### 2.2 Model

`StaticPage`:

- Add `content_blocks` to `#[Fillable]`.
- Cast: `'content_blocks' => 'array'`.
- No mode column — mode is inferred: non-null `content_blocks` ⇒ Advanced on next edit.

### 2.3 Lifecycle rules

Unchanged from today / functional §5:

- Delete page → row gone → usage provider stops yielding paths → Media GC after grace.
- Publish / unpublish / author nullify — untouched; blocks travel with the row.
- No soft-delete; no cascade into Media on write (GC only).

## 3. PHP architecture

### 3.1 Public API

No new StaticPage public API. Public show keeps reading `content` (HTML cache)
and `header_image_path`. Other domains do not call into StaticPage for blocks.

### 3.2 Services

`StaticPageService` gains a News-shaped content branch (private, domain-local —
not extracted to Editor):

```php
// Conceptual contract (mirror NewsService)
private function resolveContent(array $data): array
// returns ['content' => string, 'content_blocks' => ?array]

// Simple (mode !== 'advanced'):
//   sanitize via existing sanitizeContent (Purifier admin-content + HtmlLinkUtils)
//   content_blocks = null

// Advanced:
//   walk blocks_order → blocks[uid]
//   text → EditorPublicApi::sanitizeText($html)  // default multiedit-text
//   image → MediaPublicApi::store('static-pages', $file, widths) or reuse path;
//           force keep_original when !hasVariants(path);
//           empty alt → ValidationException (same as News)
//   drop empty text blocks; require ≥1 remaining block
//   content = EditorPublicApi::render($blocks); content_blocks = $blocks
```

`create` / `update` call `resolveContent`, then existing `resolveHeaderImage`,
then unset transient keys (`blocks`, `blocks_order`, `mode`, `header_image`)
before fill/save — same sequencing as News.

Constructor adds `EditorPublicApi` beside the existing `MediaPublicApi`.
Media scope remains `private const SCOPE = 'static-pages'`.

### 3.3 Policy / authorization

Unchanged: admin / tech-admin middleware on admin routes. No new policies.

### 3.4 Events and listeners

None new. Existing StaticPage events (if any) keep current payloads; blocks are
row data, not a separate event surface.

### 3.5 Routes, controllers, form requests

- Routes/controllers: same CRUD; `store`/`update` still pass
  `$request->validated()` into the service. No new endpoints.
- Publish/unpublish `PATCH` routes: **out of scope** (pre-existing WAF debt).
- `StaticPageRequest::rules()` — literal News mirror:

```php
'mode' => ['nullable', Rule::in(['simple', 'advanced'])],
// advanced: content nullable; blocks required min:1; blocks.*.type in text|image;
//   html/path/alt/caption/keep_original/file as on NewsRequest (alt soft in request)
// simple: content required|string
// header_image[.file|.path] unchanged
```

Enforcement split matches News: FormRequest for shape; service for empty-blocks
and required image alt.

## 4. Frontend architecture

Reuse only — no new Blade/JS/CSS:

| Surface | Component |
|---------|-----------|
| Body | `<x-editor::multi name="blocks" content-name="content" … scope="static-pages" toolbar="editorial" />` |
| Header | existing `<x-media::image-field name="header_image" scope="static-pages" :show-alt="false" … />` |

Mode default on edit: `old('mode', $page?->content_blocks ? 'advanced' : 'simple')`.
Failed-validation rebuild of blocks from `old('blocks')` + `old('blocks_order')`
mirrors News.

**Field order** on StaticPage and News admin create/edit forms:

1. title  
2. slug  
3. header image  
4. summary  
5. body (multi / was rich-text on StaticPage)

StaticPage drops the standalone Media section once the header sits in that
order. Status / meta (and any News equivalents) stay outside this sequence —
not reordered relative to each other.

Public StaticPage show: unchanged — `<x-media::image>` for header +
`{!! $page->content !!}`.

## 5. Deptrac

| Edge | Justification |
|------|----------------|
| `StaticPagePrivate` → `EditorPublic` | Service calls `EditorPublicApi` for advanced sanitize/render (NewsPrivate already has this edge). |

No other new edges. `StaticPagePrivate` → `MediaPublic` and Blade `x-editor::*` /
`x-media::*` already exist.

## 6. Testing strategy

**Integration (default)** under `app/Domains/StaticPage/Tests/…`:

- Create/update Simple: `content` stored sanitized, `content_blocks` null.
- Create/update Advanced: blocks persisted, `content` equals rendered HTML,
  image path stored/reused, empty alt rejected, empty block list rejected.
- Mode memory: non-null blocks ⇒ advanced on edit form; switch back to Simple
  when a single text block (UI rule inherited; server accepts simple payload).
- Usage provider yields `header_image_path` **and** every Advanced image `path`.
- Admin form still authorizes admin/tech-admin only (smoke via existing patterns).

**News:** one feature/view assertion or existing admin form test updated so the
DOM/field order matches title → slug → header → summary → body (no behaviour
assertions beyond order).

**Unit / vitest:** none required — no new isolated pure logic or JS modules.

**VERIFY only:** admin MultiEdit chrome (toggle disabled tooltip, block
add/reorder) and visual field order on both forms — browser-only affordances
already covered for News/Chapters elsewhere; spot-check StaticPage + News reorder.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Shared Editor MultiEdit persistence vs domain-local copy of News | A: duplicate News pattern in StaticPage; B: extract `resolveBlocks` into Editor (optionally refactor News) | **A** | Locked scope is StaticPage parity + form reorder; extraction expands into Editor/News and is out of decision #4. |
| 2 | Backlog a cross-consumer persistence refactor now? | Add TODO for Editor helper used by News/Chapters/StaticPage/FAQ vs defer | **Defer / do not add** | Chapters diverge (narrative profile, link strip, user-scoped media, simple purify in request, alt site). FAQ has no blocks yet. A News+StaticPage-only helper is premature until a third News-like consumer appears. |
| 3 | Simple-mode sanitize | Keep StaticPage `admin-content` + HtmlLinkUtils vs switch simple to Editor profile | Keep existing simple path | Preserves current Simple pages bit-for-bit; matches News split. |
| 4 | Form sections beyond the five fields | Reorder status/meta too vs leave them | Leave status/meta where they are | Functional named only the five fields. |

## 8. File layout

New / structural only (edits to existing request/service/views/provider are
implied by §1–§4, not listed as a change checklist):

```
app/Domains/StaticPage/
  Database/Migrations/
    YYYY_MM_DD_HHiiss_add_content_blocks_to_static_pages_table.php
  Tests/Feature/
    … (MultiEdit save + usage-provider coverage)
```

No new domain folders. No new public API classes.

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|-------------------|
| Third copy of advanced resolve logic drifts from News | FAQ (or another admin long-form surface) adopts News-like MultiEdit — then consider a narrow Editor advanced helper with explicit options (profile, afterTextSanitize, scope), not a full `resolveContent`. |
| Literal News validation stays loose (no path-in-scope / xor / summed text) | Abuse or confused paths in `static-pages` scope show up in production — tighten once for News + StaticPage together. |
| News form reorder is Blade-only; accidental PHP coupling | None expected; if a News test asserts old DOM order, update the assertion, not the service. |
