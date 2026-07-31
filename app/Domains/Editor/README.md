# Editor Domain

## Purpose and scope

The Editor domain owns **rich-text and block-based content authoring**: the two
Blade components that produce content, their translations and their editing
assets (Quill and the editor chrome stylesheet), and the rendering of block
documents to sanitized HTML.

It owns **no database table**. Content belongs to the domain that stores it
(News, Story, FAQ…); Editor only knows how a block document is shaped and how it
becomes HTML.

Other domains reach it two ways only: the `EditorPublicApi` class in PHP, and
the `<x-editor::…>` components in Blade.

## Block schema

A block document is an **ordered array of plain arrays** — deliberately untyped,
so a new block type costs one branch and no DTO. Two types exist:

```php
['type' => 'text',  'html' => '<p>…</p>']

['type'  => 'image',
 'path'  => 'news/x.jpg',   // Media storage path
 'alt'   => '…',
 'caption'       => '…',    // optional
 'keep_original' => bool]   // optional; true = no responsive variants
```

Rules:

- Order in the array is order on the page.
- Text HTML is sanitized with a Purifier profile the consumer chooses,
  `multiedit-text` by default (see [Sanitizing profiles](#sanitizing-profiles)).
  Every MultiEdit profile strips `<img>`, so images only ever come from image
  blocks.
- A text block that sanitizes to an empty string is skipped; an image block
  without a `path` is skipped.
- `path` is a **Media** path. Editor never uploads or deletes files — the storing
  domain calls `MediaPublicApi` and registers its `MediaUsageProvider`.

Rendered output wraps each block in `<div class="ce-block ce-block--text">` or,
for images, a `<x-media::image>` carrying `class="ce-block ce-block--image"`.

## Public API

`EditorPublicApi` is the only entry point other domains use:

| Method | Purpose |
|--------|---------|
| `render(array $blocks, string $profile = 'multiedit-text'): string` | Block document → sanitized HTML |
| `sanitizeText(string $html, string $profile = 'multiedit-text'): string` | One text block's HTML through the given profile |
| `plainTextLength(array $blocks): int` | Character count across **text** blocks only, for min/max validation |
| `plainText(array $blocks): string` | Concatenated `html` of **text** blocks only, in order, **unmodified** |

It is a concrete class resolved from the container (no interface, no binding) —
inject it by type, as `NewsService` does.

`plainText()` and `plainTextLength()` are not two spellings of the same thing.
`plainTextLength()` strips tags, decodes entities, collapses whitespace runs and
trims — the right normalisation for a min/max bound, the wrong one for a count
that must not move. `plainText()` returns the stored strings byte-identically, so
a consumer can run its own counter and get the same number before and after a
document is converted into a single text block.

### Sanitizing profiles

Profiles live in `config/purifier.php` — framework configuration, not domain
code — and are **named after the capability, never after the consumer**, exactly
like the toolbar presets. Editor does not encode who its consumers are.

| Profile | Allows | Use |
|---------|--------|-----|
| `multiedit-text` | headings, `a` with `rel`/`target` (so external links pass), `span.style`; **no** `class` attributes | The default: admin-ish content (News) |
| `multiedit-narrative` | `p.class` / `span.class` with the `ql-align-*`, `ql-spoiler`, `ql-custom-emoji-*` whitelist; `a.href` **without** `rel`/`target` | Prose written with the `links` / `narrative` toolbars, where alignment, spoilers and emoji must survive |

Neither allows `<img>`. `multiedit-narrative` is `strict-with-links` minus
`<img>`; its class whitelist is copied from it, not a hand-picked subset.
Stripping *external* links is a content policy a consumer applies to the HTML
before storing it, not something a profile does.

## Blade components

Two anonymous components, registered by `EditorServiceProvider` under the
`editor` namespace. **Only the prefixed form exists** — there is deliberately no
unprefixed `<x-editor>` alias.

### `<x-editor::rich-text>`

A single Quill field: a hidden `textarea[name]` the form submits, plus a counter.

| Prop | Default | Purpose |
|------|---------|---------|
| `name` | *required* | Submitted field name |
| `id` | *required* | DOM id; also keys `quill-editor-area-{id}` and `quill-counter-{id}` |
| `defaultValue` | `''` | Initial HTML (`old()` wins) |
| `min` / `max` | `null` | Character bounds, shown in the counter and enforced client-side |
| `nbLines` | `5` | Height in lines |
| `placeholder` | `''` | Placeholder text |
| `isMandatory` | `false` | Marks the field required client-side |
| `indentParagraphs` | `false` | Adds `ql-indent` to the surface |
| `resizable` | `true` | Vertically resizable |
| `toolbar` | `'default'` | Preset name (see below) or an explicit token array |

### Toolbar presets

Both components take `toolbar` as a **preset name** resolved by
`Private/Support/ToolbarPresets`:

| Preset | Tokens |
|--------|--------|
| `default` | `bold, italic, underline, strike, blockquote, align, list, custom-emoji` |
| `links` | `default` + `link` |
| `editorial` | `bold, italic, underline, strike, header, blockquote, align, list, custom-emoji, link` |
| `narrative` | `default` + `link` + `spoiler` |

Presets are named after the **capability** they add, never after the domain that
uses them — Editor does not encode who its consumers are. An unknown name falls
back to `default`. `:toolbar="['bold', …]"` still passes an explicit token list
and bypasses presets entirely.

The `link` and `spoiler` tokens are not decoration: they switch on extra
component wiring (`data-link-*`, `data-spoiler-label`).

### `<x-editor::multi>`

The opt-in block editor: a mode toggle between one `rich-text` field (*simple*)
and an ordered stack of text/image blocks (*advanced*) serialized as
`name[uid][…]` alongside `mode` and `name_order` (the visual order of uids). The
server branches on `mode` (see `NewsService`). Image blocks compose
`<x-media::image-field>`, so the storing domain — not Editor — owns the upload
and the `MediaUsageProvider` that keeps the file alive.

| Prop | Default | Purpose |
|------|---------|---------|
| `scope` | *required* | Media scope for image uploads and the reuse picker |
| `name` | `'blocks'` | Base field name for the advanced blocks |
| `contentName` | `'content'` | Field name of the simple-mode editor |
| `contentValue` | `''` | Current simple-mode HTML |
| `blocks` | `[]` | Stored blocks; a non-empty array opens in advanced mode |
| `mode` | `'simple'` | Initial mode when `blocks` is empty |
| `blockTypes` | `['text', 'image']` | Types the insert affordance offers |
| `toolbar` | `'default'` | Preset name or token array, resolved once and shared by both panes and every text block |
| `min` / `max` | `null` | Bounds on the **summed** text length |
| `placeholder` | `''` | Placeholder text |

Translations live in `Private/Resources/lang/fr/` under the `editor::` namespace:
`editor::rich-text.*` and `editor::multi.*`.

## Assets

The components load their own Vite entries — **consumer pages never write an
`@vite` line for the editor**. `Private/Resources/views/components/_assets.blade.php`
pushes both, CSS first, inside a single `@once` shared by the two components:

| Entry | Holds |
|-------|-------|
| `Private/Resources/css/editor.css` | Editor **chrome**: `.ql-toolbar` rules, the `.ql-tooltip[data-label-*]` translations, the `.ql-editor` writing surface, the editing-side spoiler styling |
| `Private/Resources/js/editor-bundle.js` | Quill, the custom emoji blot and picker, the spoiler format; it pulls Quill's own `snow` stylesheet |

The chrome/read-side boundary: **a rule stays in `Shared/Resources/css/app.css`
if a page that never loads the editor needs it.** That leaves Shared with the
`.ql-align-*` classes, `.rich-content` and its descendants, the
`.ql-spoiler:not(.ql-editor …)` read-only variants and the `.ql-custom-emoji*`
family — all of them present in *stored* HTML. A misfiled rule silently degrades
a read-only page, so add a comment stating which side a new Quill rule belongs to.

Both entries are declared in `vite.config.js`. Caveat: a `@push` executed while
rendering an AJAX fragment is discarded, so a page that renders an editor *only*
inside a fragment must push the assets itself (see
[AGENTS.md](AGENTS.md)).

## What this domain does not do

- No routes, no controllers, no policies, no events.
- No image storage, upload or deletion — that is Media's.
- No persistence of documents — the consuming domain owns the column, and the
  rendered HTML it may cache alongside it.
- No block-type registry and no typed block DTOs: a third block type is the day
  to introduce the registry, not before.
- No read-side rendering of stored HTML beyond `render()` — the `.rich-content`
  typography that displays it belongs to `Shared`.
