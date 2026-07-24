# MultiEdit — Delivery Planning

Sequencing for the MultiEdit feature. Reads on top of `MultiEdit.md` (functional) and `MultiEdit_Architecture.md` (technical). Each phase is independently shippable and testable; later phases depend only on earlier ones.

## Guiding principles

- **Foundation before feature.** The `Media` domain (path-addressed, tableless) and its components land first — MultiEdit is just one consumer.
- **Pilot before fleet.** Migrate **FAQ only** to prove the component + usage-provider pattern, then plan the remaining consumers from real experience.
- **News is the only v1 editing surface.** Static pages and Chapters are later phases, off the v1 critical path.
- **Simple mode never changes.** Every phase preserves existing behavior; no data migration of existing content, no schema change to consumers' `image_path` columns.

## Phase overview

| Phase | Deliverable | Depends on |
|-------|-------------|-----------|
| 1 | `Media` domain (tableless: API, usage registry, GC, components) | — |
| 2 | **FAQ pilot** → adopt Media (component + provider) | 1 |
| 2.5 | Checkpoint: plan remaining consumer adoptions | 2 |
| 3 | Shared multi-editor + renderer + `multiedit-text` profile | 1 |
| 4 | News advanced mode | 1, 3 |
| 5 | Later surfaces: Static pages, then Chapters | 4 |
| — | Remaining consumer adoptions (News header, Static, Calendar, Profile) | 2.5 |

---

## Phase 1 — The `Media` domain (foundation)

**Goal:** a path-addressed asset domain — no tables — absorbing today's `ImageService`, exposing a Public API + usage registry, the two reusable components, the picker route, and the GC command.

**Work:**
1. Scaffold `app/Domains/Media` (Private/Public per `Domain_Structure.md`), `MediaServiceProvider`.
2. **No migrations.** Media owns no tables (architecture §4.1).
3. `MediaService` delegates to the existing `Shared\ImageService` (kept in Shared for now, since unmigrated consumers still reference it; physically relocated into `Media/Private` only once the last consumer is off it).
4. `MediaPublicApi` + `MediaPathPageDto`/`MediaPathDto`: `store(scope,file,widths)→path`, `listByScope`, `variantUrl(path,width,format)`, `folderFor(scope)`, `countUsages(path)`. `scope → folder` mapping (§4.3).
5. `MediaUsageRegistry` + `MediaUsageProvider` interface (`usedPaths(): iterable<string>`, one entry per occurrence). `countUsages` sums occurrences across providers.
6. `media:gc` artisan command: live set = union of providers' `usedPaths`; delete on-disk originals under managed scopes not in the live set and older than the 7-day grace window (`deleteWithVariants`); sweeps debris the same way. Schedule **daily at 03:30** (off midnight).
7. `<x-media::image>` (readonly `<picture>` by path) and `<x-media::image-field>` (editable: current image, upload, remove, "choose existing", alt/caption, "used in N places") — both owned by Media (§8.2).
8. `GET /media/library?scope=&page=` route/controller (Media), auth per scope, backed by `listByScope`.
9. Deptrac: add `MediaPublic` to the `Shared` layer allowlist; wire `Media` layers.

**Tests:** `store` (original + variants, returns path), `listByScope` (originals only, excludes `-Nw` variants, paginates, per-author isolation), `variantUrl`, `folderFor`, `MediaUsageRegistry`/`countUsages` (exact occurrence counts, no substring over-count), `media:gc` (deletes only unclaimed originals past 7 days; spares claimed and recently-modified; idempotent; sweeps debris), `<x-media::image>` + `<x-media::image-field>` rendering. Deptrac green.

**Done when:** Media is usable by a consumer, fully tested, and nothing else has changed yet (no consumer adopted).

---

## Phase 2 — FAQ pilot adoption

**Goal:** migrate one real consumer end-to-end to validate the pattern — **no schema change, no backfill**.

**Work (architecture §9):**
1. **No migration.** Keep `faq_questions.image_path` and `image_alt_text` as-is.
2. `FaqQuestionController`: inject `MediaPublicApi` (drop direct `ImageService`).
   - create/replace → `store('faq', $file)` → set `image_path`.
   - remove → clear `image_path`.
   - delete question → row goes away; GC reclaims the file if unclaimed.
3. Register a FAQ `MediaUsageProvider` yielding every non-null `faq_questions.image_path`.
4. Views (`index.blade.php`, admin `index`/`_form`): display via `<x-media::image :path :alt>`; edit via `<x-media::image-field scope="faq">`.

**Tests:** FAQ create-with-image, replace, remove, delete-question (file becomes unclaimed → GC-eligible after window; still present before it). FAQ provider yields exactly the stored `image_path`s. Existing FAQ display still works. Deptrac: FAQ depends on `MediaPublic`.

**Done when:** FAQ images flow through Media by path; no direct `ImageService` reference remains in FAQ; FAQ files are covered by GC via its provider.

---

## Phase 2.5 — Checkpoint (plan remaining consumers)

Short planning step, **not** code. Using the FAQ experience, decide per-consumer specifics for News header, StaticPage header, Calendar, Profile picture (each single-image, same recipe: swap `process`→`store`, adopt components, register a provider). Note the second News `ImageService` consumer — `NewsObserver` — explicitly so it isn't missed. Capture surprises (alt handling, view churn) and schedule them. These adoptions are **not** on the MultiEdit critical path and can proceed in parallel with Phases 3–4.

---

## Phase 3 — Shared multi-editor + renderer

**Goal:** the reusable advanced-editing UI and its server-side renderer, surface-agnostic.

**Work:**
1. `config/purifier.php`: add **`multiedit-text`** profile = `admin-content` minus `img` (keeps text blocks image-free so image paths live only in image blocks — architecture §4.6).
2. `ContentBlocksRenderer` (Shared): blocks → sanitized HTML; text via `multiedit-text`; image via `<x-media::image>` + optional `<figcaption>`.
3. `<x-shared::multi-editor>` (Blade + Alpine): props `name`, `blocks`, `mode`, `blockTypes`, `scope`, `toolbar`, `min`, `max`. Block array state; add/insert/reorder/delete; text block reuses `editor.blade.php` + `initQuillEditor` on add; image block composes `<x-media::image-field>` (holds a pending `File` or an existing `path`).
4. Media picker modal wiring in `<x-media::image-field>` → `GET /media/library` (route from Phase 1).
5. Multipart serialization (`blocks[i][…]` with `path` xor `file`, `mode`).
6. Mode toggle rules (Simple↔Advanced constraints, functional §3); warn if switching HTML contains `<img>`.

**Tests (component/JS + renderer):** renderer text-passthrough sanitization (strips `img`), image figure/srcset output; serialization order and `path`/`file` fields; mode-toggle enable/disable rules. (Surface-level persistence covered in Phase 4.)

**Done when:** the component renders, edits, and serializes; the renderer produces correct HTML — provable without a surface wired.

---

## Phase 4 — News advanced mode (v1 feature)

**Goal:** News authors can build illustrated articles; everything persists, re-renders, and reads back.

**Work (architecture §5, §7):**
1. Migration: add `news.content_blocks` JSON NULL. (`content` reused as render cache; no backfill.) `down()` drops the column.
2. `NewsRequest`: conditional advanced rules (blocks array, per-block type/alt/`path`-xor-`file`, `path` within scope, summed-text min/max).
3. `NewsService.create/update`: advanced branch — resolve image blocks (`store` for files, reuse `path`), normalize+sanitize blocks (`multiedit-text`), persist `content_blocks`, write `content = ContentBlocksRenderer::render(...)`. `delete` needs no special release. Simple branch unchanged.
4. Register a News `MediaUsageProvider` yielding header `image_path` + all `content_blocks` image `path`s.
5. Form (`admin/news/_form.blade.php`): mode toggle + `<x-shared::multi-editor scope="news">`.
6. Public view unchanged (`{!! $content !!}`).

**Tests:** mode round-trip (simple↔advanced), block CRUD/reorder persistence, summed min/max, empty-block dropping, `content` cache equals rendered blocks, upload-vs-reuse, **same path repeated in one article**, two News sharing one path, delete leaves file until GC then reclaims, News provider yields header + block paths. Existing News tests stay green.

**Done when:** News advanced mode works, is tested, and simple News is untouched. **v1 ships here.**

---

## Phase 5 — Later surfaces

Off the v1 critical path; each mirrors Phase 4 on its model/form/service, plus a usage provider.

- **5a — Static pages:** `content_blocks` on `static_pages`, `scope='static-pages'`, same service/form/view pattern + provider. (Also adopt Media for its header image if not already done via Phase 2.5.)
- **5b — Chapters:** `content_blocks` on `story_chapters`, `scope='chapters/{userId}'` + provider. Additional work: render each text block as its own `[data-annotable]` region so annotation canonical-text builds per block (architecture §10); confirm word/character counts and minimum-length sum across text blocks; repeated separator images within one chapter are supported natively; keep image annotation out of scope (tracked in `Chapter_Annotations.md`).

---

## Cross-cutting checklist (every phase)

- Deptrac green (`./vendor/bin/sail composer deptrac`).
- Tests via `./vendor/bin/sail artisan test:parallel`.
- i18n: FR translation keys for any new UI (project convention — no en/ per recent Quote work).
- No code outside `app/Domains/<domain>`.
- Any domain that stores image paths registers a `MediaUsageProvider` (else its files are invisible to GC — guard with a test).

## Risks & sequencing notes

- **Phase 1 is a hard prerequisite** for everything; keep it strictly path-based and provider-driven (no editor coupling) so it can't stall on UI work.
- **Phase 2 validates the adoption recipe cheaply** before committing the other consumers — if the component/provider shape hurts, we learn it on FAQ, not News.
- **Phases 3 and 2 are independent** (both need only Phase 1) and can run in parallel.
- **GC lives from Phase 1** but only reclaims once a consumer's provider is registered (Phase 2 onward). Schedule `media:gc` from the start with the 7-day grace window so orphans never accumulate yet a missing provider is recoverable.
- **Forgotten provider = invisible-to-GC files** (accumulate) or, if a scope is swept before its provider exists, premature deletion — the grace window + the registration guard test mitigate both.
