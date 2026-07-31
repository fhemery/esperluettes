# Chapters — MultiEdit content — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Decisions log: [`DECISIONS.md`](./DECISIONS.md)

## 1. Domain placement

**Story owns the feature.** It owns `story_chapters`, the chapter edit flow, the
counts and the reading page; nothing here is reusable outside it. The two things
Story does not own — how a block document is shaped/rendered, and where image
files live — it reaches through the existing public APIs (`EditorPublicApi`,
`MediaPublicApi`), exactly as News does. No new domain, no new registry.

Story becomes the **second consumer** of MultiEdit. That is the event this design
turns on: a single-consumer Editor could hardcode its content policy, a
two-consumer Editor cannot. Both changes to Editor below exist for that reason
and for no other.

### 1.1 Changes in other domains

#### Editor — becomes profile-aware (a widened contract, not a new extension point)

`ContentBlocksRenderer` hardcodes the `multiedit-text` Purifier profile. That
profile is wrong for chapters in two directions at once, and both are provable
from `config/purifier.php` against the `links` toolbar chapters use:

- it forbids `p.class` and `span.class`, so `ql-align-*`, `ql-spoiler` and
  `ql-custom-emoji-*` — all of which the `links` toolbar produces and
  `strict-with-links` preserves today — would be **stripped on conversion**;
- it permits `a.href` with `target`/`rel`, so a converted chapter would start
  accepting **external links**, which simple-mode chapters deliberately strip.

So `render()` and `sanitizeText()` gain an optional profile argument, defaulting
to today's value. News and every existing caller are unaffected by construction.

```php
final class EditorPublicApi
{
    public function render(array $blocks, string $profile = 'multiedit-text'): string;
    public function sanitizeText(string $html, string $profile = 'multiedit-text'): string;
    public function plainTextLength(array $blocks): int;          // unchanged
    public function plainText(array $blocks): string;             // new — see below
}
```

A new `multiedit-narrative` Purifier profile is added alongside it: the element
set of `strict-with-links` (no `<img>` — image blocks are the only image source,
which is what keeps the used-path set enumerable), plus `p.class`/`span.class`
and the same `Attr.AllowedClasses` whitelist. Naming follows Editor's existing
convention for toolbar presets — **named after the capability, never after the
consumer**, so Story does not leak into Editor's vocabulary.

External-link stripping stays **Story-side**, applied to each text block's HTML
before it is stored. It is a Story content policy, not a sanitizer capability,
and `HtmlLinkUtils` already lives in Shared.

#### Editor — `plainText()` for counting

`plainTextLength()` exists but **cannot be reused**: it collapses whitespace runs
and trims, whereas `CharacterCounter` does neither. Using it would shift
`character_count` on conversion, violating §4.6.2. Rather than change it — News
depends on its min/max semantics — Editor exposes the raw material and lets each
consumer apply its own counter:

```php
/** Concatenated HTML of text blocks only, in order. Images excluded. */
public function plainText(array $blocks): string;
```

Story runs its existing `WordCounter`/`CharacterCounter` on the result. Editor
keeps ownership of *what a text block is*; Story keeps ownership of *how we
count*. Conversion is then count-stable by construction: a Simple chapter
becomes one text block whose `html` is the chapter's own HTML, so the counter
sees the identical string.

#### Editor — `<x-editor::multi>` prop parity with `<x-editor::rich-text>`

The chapter editor passes `:nbLines="15"` and `:indentParagraphs="true"` today.
`<x-editor::multi>` accepts neither, and `_text-block.blade.php` hardcodes
`data-nb-lines="5"`. Both must be threaded through to every text block, or
Advanced mode is a visibly worse writing surface than Simple mode in the same
form. This is prop plumbing on an existing component, not a new capability.

#### Media — no change

`MediaService::folderFor()` already accepts `chapters/{userId}`, and
`managedFolders()` already enumerates every `chapters/*` subfolder for the sweep.
Media was built anticipating this consumer. Story only has to **register a
provider** — see §3.4.

#### Shared — one read-side CSS rule

`.rich-content p:last-of-type { padding-bottom: 0 }` is scoped to the paragraph's
*parent*. Today every `<p>` shares the `<article>` as parent, so exactly one
paragraph in the chapter loses its bottom padding. Under Advanced mode each
`.ce-block--text` div is a parent, so the last paragraph of **every** text block
loses it — a visible spacing collapse at each block boundary, and a §4.5.2
violation. The rule must be re-scoped so it applies only to the last paragraph of
the last block. It belongs in `Shared/Resources/css/app.css` under the existing
read-side/chrome rule (a page that never loads the editor needs it).

Verified as **not** a problem: `.rich-content p`, `ul`, `ol`, `li`, `a` are all
descendant selectors, so nesting inside a div does not disturb them, and
`text-indent` is an inherited property, so `[text-indent:2rem]` on the `<article>`
still reaches paragraphs nested one level deeper. This closes open question 3 of
the spec analytically; VERIFY still checks it in a browser.

## 2. Data model

### 2.1 Tables

One migration on `story_chapters`, mirroring `news`:

| Column | Type | Null | Default | Index |
|--------|------|------|---------|-------|
| `content_blocks` | `json` | yes | `NULL` | none |

`NULL` is Simple mode and is the meaning of every existing row — which is what
makes decision 3's "no data migration" a fact about the schema rather than a
promise. No index: the column is never queried by value. The usage provider
filters on `whereNotNull('content_blocks')`, which no index would help.

`down()` drops the column.

### 2.2 Model

`Chapter` gains `content_blocks` in `#[Fillable]` and `'content_blocks' =>
'array'` in `$casts`. Nothing else — no accessor, no scope. `content_blocks !==
null` **is** the mode; storing a separate `mode` column would create a second
source of truth that could disagree with the first.

### 2.3 Lifecycle rules

| Event | Behaviour |
|-------|-----------|
| Chapter saved | `content` is recomputed from the blocks. **Single writer**: only the chapter content resolver (§3.2) may write `content`. This invariant is what makes decision 8's "store the rendered output" safe. |
| Chapter soft-deleted | Blocks are retained with the row. The usage provider **must** query `withTrashed()` — see §3.4. |
| Chapter force-deleted | Row and blocks go; images become GC-eligible after the grace window. |
| Story deleted | Same, via the existing chapter cascade. |
| Moderation empties content | Must set `content_blocks = null` **as well as** `content = ''`. Emptying only one leaves a chapter whose rendered content is blank but whose blocks still hold the moderated text, and the next ordinary save would restore it from the blocks. |
| Unpublish / schedule | No effect on blocks or image liveness. |
| Author deactivated/deleted | No effect. Paths are reported by path, not by uploader. |

## 3. PHP architecture

### 3.1 Public API

**Story's public API does not change.** `StoryPublicApi`, `StoryChapterDto` and
`ChapterSnapshot`'s *shape* are untouched — decision 3 kept `content` as the
rendered HTML precisely so that every existing reader keeps working against an
opaque HTML string.

One semantic change inside `ChapterSnapshot::fromModel()`: it currently
**recomputes** `wordCount`/`charCount` from `$chapter->content`. Recomputing from
rendered Advanced content would count image captions, which §4.6.1 forbids and
which would shift the four user-visible statistics that consume the snapshot. It
must instead read the persisted `word_count`/`character_count` columns — the
single source of truth the observer maintains.

Side effect, accepted: `charCount` currently uses `mb_strlen(strip_tags(…))`
without entity decoding, while the column uses `CharacterCounter` *with* it. For
a chapter containing HTML entities the emitted `charCount` shifts by a few
characters. Aligning the two is strictly a correction — the column is the value
already displayed to users — and it removes a duplicated counting rule rather
than adding one.

### 3.2 Services

A dedicated **`ChapterContentResolver`** (`Story/Private/Support/`) turns the
submitted payload into the two persisted fields:

```php
/** @return array{content: string, content_blocks: ?array<int,array<string,mixed>>} */
public function resolve(array $data, int $actingUserId): array;
```

It is the analogue of `NewsService::resolveContent()`, extracted rather than
inlined for two reasons: `ChapterService` is already ~500 lines and orchestrates
events, credits and notifications, none of which content resolution needs; and
the resolver is the only thing in Story that depends on `EditorPublicApi` and
`MediaPublicApi`, which keeps those two edges at one class.

Contract:

- Simple (`mode !== 'advanced'`) — `content` = the sanitized author HTML,
  `content_blocks` = `null`. Identical to today.
- Advanced — walk the submitted order, and per block:
  - **text**: sanitize with `multiedit-narrative`, then strip external links;
    drop the block if it is empty after sanitizing;
  - **image**: store a new upload under scope `chapters/{actingUserId}` or keep
    the reused path; drop if no path; **reject with a validation error if `alt`
    is blank**;
  - then `content` = `EditorPublicApi::render($blocks, 'multiedit-narrative')`
    and `content_blocks` = the normalized list.

The **acting** user's id scopes the upload, per §4.4.1 — so a co-authored chapter
may legitimately hold images from two authors' folders, and the reuse picker
(which lists one scope folder) shows an author only their own images with no
extra filtering.

`ChapterService::createChapter()` / `updateChapter()` call the resolver instead
of reading `content` off the request. `emptyContentBySlug()` clears both fields.

Counts stay in `ChapterObserver::saving()` — the existing single place — sourcing
from `EditorPublicApi::plainText($chapter->content_blocks)` when blocks are
present and from `$chapter->content` when they are not, then applying today's
`WordCounter`/`CharacterCounter` unchanged.

### 3.3 Policy / authorization

**Nothing new.** §3 of the spec is explicit that MultiEdit inherits the existing
gate: `role:user-confirmed` route middleware plus `ChapterPolicy` ownership,
co-authors included. No new policy method, no new ability, no new route.

The one authorization-adjacent detail is the upload scope: it is derived
**server-side** from the authenticated user id, never from the request. A client
cannot name someone else's folder.

### 3.4 Events and listeners

No new event and no new listener. `ChapterCreated` / `ChapterUpdated` /
`ChapterDeleted` fire as they do now, with the snapshot correction of §3.1.

One registration is added to `StoryServiceProvider::boot()`, beside the existing
observer and registry wiring:

```php
app(MediaUsageRegistry::class)->register(new ChapterMediaUsageProvider());
```

`ChapterMediaUsageProvider` (`Story/Private/Support/`) implements
`MediaUsageProvider::usedPaths(): iterable` and yields every `path` of every
image block of every chapter — **`withTrashed()`**, per §5 of the spec and open
question 1, which the code answers: `Chapter` uses `SoftDeletes` (migration
`2025_10_17_161100`). A soft-deleted chapter whose paths went unreported would
have its images swept, and restoring it would restore a chapter full of dead
images.

Under-reporting is the destructive failure mode, not over-reporting: registering
the provider is what makes `chapters/{userId}` a *claimed* folder, and the GC's
folder-level guard only protects folders with **zero** claimed paths. Once one
path in a folder is claimed, every unclaimed original in that same folder becomes
deletable after the grace window. Exhaustiveness is therefore a correctness
requirement, and §6 tests it directly.

### 3.5 Routes, controllers, form requests

No route change; no new verb, so the PATCH prohibition is not engaged.

`ChapterRequest` branches on `mode`, as `NewsRequest` does:

- `mode` — `nullable`, `in:simple,advanced`.
- Simple — `content` stays `required`, and `prepareForValidation()` keeps
  purifying it with `strict-with-links` + `stripExternalLinks` exactly as today.
- Advanced — `content` becomes `nullable` (it is derived output, not input);
  `blocks` is `required|array|min:1`, with per-block rules for `type`, `html`,
  `path`, `alt`, `caption`, `keep_original` and `file` mirroring `NewsRequest`.

`prepareForValidation()` must **not** purify block HTML — blocks are sanitized in
the resolver with the narrative profile. Purifying twice with two different
profiles is how the two policies would silently diverge.

Per §6 of the spec, new messages go in `story::validation` (French); the toggle
and block chrome reuse `editor::multi.*` and add no Story strings.

## 4. Frontend architecture

The chapter form's content field swaps `<x-editor::rich-text>` for
`<x-editor::multi>`:

```blade
<x-editor::multi
    scope="chapters/{{ auth()->id() }}"
    name="blocks" contentName="content"
    :contentValue="old('content', $chapter->content ?? '')"
    :blocks="old('blocks', $chapter->content_blocks ?? [])"
    toolbar="links" :nbLines="15" :indentParagraphs="true" />
```

`blocks` non-empty opens the component in Advanced mode, so mode is restored from
the stored data with no extra field. §4.3's "return to Simple" rule (one text
block, no images) is already enforced by the component, with its own French
tooltip — no server-side counterpart is added, because the server accepts either
shape and the constraint is an authoring affordance, not an invariant.

`author_note` is untouched (decision 5) and keeps `<x-editor::rich-text>` with
the `narrative` toolbar.

**The reading page does not change at all.** `<article data-quote-article class="prose
rich-content max-w-none [text-indent:2rem] text-xl">{!! $chapter->content !!}</article>`
stays exactly as it is: one quote root for the whole chapter (§4.5.1), printing
stored HTML. Per-block `[data-annotable]` regions remain out of scope (§8).

Assets need no attention: `<x-editor::multi>` pushes its own Vite entries via the
shared `@once`, and the chapter form is a full page render, not an AJAX fragment,
so the `@push` caveat in Editor's AGENTS.md does not apply.

The only CSS is the `p:last-of-type` re-scoping of §1.1.

## 5. Deptrac

| Edge | Justification |
|------|---------------|
| `StoryPrivate → EditorPublic` | `ChapterContentResolver` and `ChapterObserver` call `EditorPublicApi` for sanitizing, rendering and plain text. |
| `StoryPrivate → MediaPublic` | `ChapterContentResolver` calls `MediaPublicApi::store()`/`hasVariants()`; `ChapterMediaUsageProvider` implements `MediaUsageProvider`. |
| `StoryPublic → MediaPublic` | `StoryServiceProvider` resolves `MediaUsageRegistry` to register the provider. |

All three mirror edges News already has (`NewsPrivate → EditorPublic`,
`NewsPrivate → MediaPublic`, `NewsPublic → MediaPublic`). No Editor or Media
edge points back at Story, so no cycle is introduced.

## 6. Testing strategy

Integration (feature) tests are the default and cover:

- **Editor**: `render()`/`sanitizeText()` honour a passed profile and still
  default to `multiedit-text`; `multiedit-narrative` preserves `ql-align-*`,
  `ql-spoiler` and `ql-custom-emoji-*` and still strips `<img>`; `plainText()`
  returns text blocks only, in order; `<x-editor::multi>` emits `nbLines` and the
  indent flag onto every text block.
- **Story — conversion invariance (§4.6.2)**, the acceptance criterion with
  teeth: a chapter saved in Simple mode, then re-saved as one Advanced text block
  with byte-identical HTML, keeps the *same* `word_count` and `character_count`.
  Assert equality against the pre-conversion values, not against constants.
- **Story — captions and alt text do not count**: adding an image block with a
  long caption and alt text leaves both counts unchanged.
- **Story — round trip**: save Advanced, reload the edit form in Advanced mode,
  `content` renders the blocks, the reading page prints it inside the single
  `[data-quote-article]` root.
- **Story — sanitizing**: an external link in a text block is stripped; an
  internal link survives; alignment and emoji classes survive.
- **Story — validation**: an image block with blank `alt` fails; Advanced with
  zero blocks fails; Simple still requires `content`.
- **Story — usage provider exhaustiveness**: paths from published, draft,
  scheduled **and soft-deleted** chapters are all reported; a repeated image is
  reported per occurrence. Then the decisive one — run `media:gc` with a chapter
  image live and confirm it survives while an orphan in the same folder is swept.
  That test is the one standing between this feature and data loss.
- **Story — moderation**: `emptyContentBySlug()` clears blocks as well as
  content, and a subsequent save does not resurrect the text.
- **Story — snapshot**: `ChapterSnapshot::fromModel()` reports the persisted
  counts for both Simple and Advanced chapters.

Unit tests: none warranted. Every piece of logic here reaches a Purifier profile,
the filesystem or Eloquent.

VERIFY (browser only): the toggle and block controls under Alpine; reuse-picker
behaviour; and the §4.5.2 typography comparison — a converted and an unconverted
chapter side by side, checking paragraph indentation and inter-paragraph spacing
across a block boundary.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Chapter text blocks need a different sanitizer than News. How? | (a) Editor takes an optional profile argument + new `multiedit-narrative` profile; (b) widen `multiedit-text` itself; (c) accept the loss and reuse `multiedit-text` | (a) | (b) silently widens what News and future static pages accept, and still leaves external links to Story, so the profile stops being the whole policy. (c) makes conversion lossy — alignment, spoilers and emoji vanish and external links appear — a regression decision 4 never sanctioned, since it covered quote anchoring, not formatting. |
| 2 | Where does the plain text for counting come from? | (a) new `EditorPublicApi::plainText()`; (b) Story reads the block array itself; (c) reuse `plainTextLength()` | (a) | (c) is disqualified outright: it collapses whitespace and trims, so it would shift `character_count` on conversion and break §4.6.2. Between (a) and (b), (a) keeps "what is a text block" in Editor and "how we count" in Story, for one method. |
| 3 | Does `ChapterSnapshot` keep recomputing counts from `content`? | (a) read the persisted columns; (b) keep recomputing | (a) | Recomputing from rendered Advanced content counts image captions, breaking §4.6.1 and shifting four user-visible statistics. Decided without asking — §4.6 leaves no alternative. |
| 4 | Where does block resolution live? | (a) a `ChapterContentResolver` support class; (b) inline in `ChapterService` like News | (a) | `ChapterService` is already ~500 lines of event/credit/notification orchestration; the resolver is also the only class needing the Editor and Media edges. Layout call, not the user's. |
| 5 | Is mode a column, or is `content_blocks IS NULL`? | (a) derive from the column; (b) an explicit `mode` column | (a) | A second source of truth that can disagree with the first. Decided without asking. |

## 8. File layout

```
app/Domains/Story/
├── Database/Migrations/
│   └── YYYY_MM_DD_HHiiss_add_content_blocks_to_story_chapters_table.php
├── Private/Support/
│   ├── ChapterContentResolver.php        # payload → {content, content_blocks}
│   └── ChapterMediaUsageProvider.php     # MediaUsageProvider, withTrashed()
└── Tests/Feature/Chapters/
    ├── ChapterAdvancedModeTest.php
    ├── ChapterConversionCountsTest.php
    └── ChapterMediaUsageProviderTest.php

config/purifier.php                       # + 'multiedit-narrative'
```

Everything else is an edit to a file already named in §1.1–§4: `Chapter`,
`ChapterObserver`, `ChapterService`, `ChapterRequest`, `ChapterSnapshot`,
`StoryServiceProvider`, the chapter form partial, `EditorPublicApi`,
`ContentBlocksRenderer`, the two `<x-editor::multi>` templates, `app.css` and
`deptrac.yaml`.

## 9. Risks acknowledged

1. **The usage provider is the data-loss surface.** Registering it is what makes
   `chapters/*` sweepable; the GC's folder guard only saves a folder with zero
   claimed paths, so one live chapter image exposes every other original in that
   author's folder. *Trigger to revisit*: any new place that stores an image path
   outside `content_blocks` — it must be added to `usedPaths()` in the same
   commit, or the provider is silently under-reporting.

2. **`multiedit-narrative` and `strict-with-links` must stay in step.** They
   encode the same content policy for the same chapters in two modes. Drift means
   a chapter's permitted formatting changes when its author toggles the mode.
   *Trigger*: any edit to either profile — change both, or write down why not.

3. **Conversion invariance is only as good as its test.** §4.6.2 holds because
   the single text block's HTML is byte-identical to the old `content`. Anything
   that rewrites text-block HTML on the way in — a sanitizer change, a Quill
   upgrade, an autoformat rule — breaks it silently, and the damage is a shifted
   public word total. *Trigger*: a red `ChapterConversionCountsTest`; it must
   never be "adjusted" to the new numbers.

4. **Quote breakage is accepted but not measured.** Decision 4 accepts silent
   detachment on conversion. We ship with no visibility into how often it
   happens. *Trigger*: user reports of vanished quotes — the response is
   `quotes-moderation/` or a re-anchoring pass, both out of scope here.

5. **`p:last-of-type` is the visible-regression risk.** The CSS fix is one rule,
   but the failure mode is subtle spacing drift a test cannot see. VERIFY's
   side-by-side comparison is the only check, and open question 3 stays open
   until that screenshot exists.
