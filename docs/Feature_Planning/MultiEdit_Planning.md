# MultiEdit — Delivery Planning

Sequencing for the MultiEdit feature. Reads on top of `MultiEdit.md` (functional) and `MultiEdit_Architecture.md` (technical). Each phase is independently shippable and testable; later phases depend only on earlier ones.

## Guiding principles

- **Foundation before feature.** The `Media` domain and its display component land first — MultiEdit is just one consumer.
- **Pilot before fleet.** Migrate **FAQ only** to prove the `asset_id` pattern, then plan the remaining consumers from real experience.
- **News is the only v1 editing surface.** Static pages and Chapters are later phases, off the v1 critical path.
- **Simple mode never changes.** Every phase preserves existing behavior; no data migration of existing content.

## Phase overview

| Phase | Deliverable | Depends on |
|-------|-------------|-----------|
| 1 | `Media` domain (headless) | — |
| 2 | **FAQ pilot** migration → `asset_id` | 1 |
| 2.5 | Checkpoint: plan remaining consumer migrations | 2 |
| 3 | Shared multi-editor + renderer + `multiedit-text` profile | 1 |
| 4 | News advanced mode | 1, 3 |
| 5 | Later surfaces: Static pages, then Chapters | 4 |
| — | Remaining consumer migrations (News header, Static, Calendar, Profile) | 2.5 |

---

## Phase 1 — The `Media` domain (foundation)

**Goal:** a headless asset domain with a Public API, absorbing today's `ImageService`, plus the shared display component and the GC command.

**Work:**
1. Scaffold `app/Domains/Media` (Private/Public per `Domain_Structure.md`), `MediaServiceProvider`.
2. Migrations: `media_assets` (incl. `orphaned_at`, `alt_default`, `scope`) and `media_references` (unique `asset_id`+`owner_type`+`owner_id`; indexes per architecture §4.1).
3. Move `ImageService` → `Media/Private/Services` (behavior unchanged); make it internal.
4. `MediaPublicApi` + DTOs (`MediaAssetDto`, `MediaAssetPageDto`): `storeUpload`, `getById`, `listByScope`, `syncReferences`, `releaseAll`, `variantUrl`. `scope → folder` mapping.
5. `syncReferences` semantics: transactional; refs→0 sets `orphaned_at`; re-use clears it; **never deletes files**.
6. `media:gc` artisan command: delete assets `orphaned_at < now()−7d` still at 0 refs (`deleteWithVariants` + row removal); optional rowless-disk-debris sweep. Register in the scheduler.
7. `<x-shared::media-image>` display component (Shared) resolving variants via `MediaPublicApi` (§8.1).
8. Deptrac: add `MediaPublic` to the `Shared` layer allowlist; wire `Media` layers.

**Tests:** `storeUpload` (row + variants), `syncReferences` (add/remove, orphan-on-zero, clear-on-reuse, **no delete on save**), `releaseAll`, `listByScope` scoping (orphans excluded), `variantUrl`, `media:gc` (respects 7-day window, spares re-referenced, sweeps debris), `media-image` rendering. Deptrac green.

**Done when:** Media is usable by a consumer, fully tested, and nothing else has changed yet (no consumer migrated).

---

## Phase 2 — FAQ pilot migration (`asset_id`)

**Goal:** migrate one real consumer end-to-end to validate the pattern.

**Work (architecture §9):**
1. Migration: add `faq_questions.image_asset_id` (nullable int, no FK). Backfill `media_assets` + `media_references` (`owner_type='faq-question'`) from existing `image_path`/`image_alt_text`; set `image_asset_id`; **then drop `image_path`**. Keep `image_alt_text`. Provide `down()`.
2. `FaqQuestionController`: inject `MediaPublicApi` (drop `ImageService`).
   - create/replace → `storeUpload('faq', …)` + `syncReferences('faq-question', $id, $assetIds)`.
   - remove → `syncReferences(…, [])`.
   - delete → `releaseAll('faq-question', $id)`.
3. Views (`index.blade.php`, admin `index`/`_form`): render via `<x-shared::media-image :asset-id :alt>`.

**Tests:** FAQ create-with-image, replace, remove, delete-question (asset released → orphaned, GC-eligible after window). Backfill migration test (existing FAQ image → asset + reference, `image_path` gone, still displays). Deptrac: FAQ depends on `MediaPublic`.

**Done when:** FAQ images fully flow through Media by `asset_id`; no `ImageService` reference remains in FAQ.

---

## Phase 2.5 — Checkpoint (plan remaining consumers)

Short planning step, **not** code. Using the FAQ experience, decide per-consumer specifics for News header, StaticPage header, Calendar, Profile picture (each single-image, same recipe). Capture surprises (backfill edge cases, alt handling, view churn) and schedule them. These migrations are **not** on the MultiEdit critical path and can proceed in parallel with Phases 3–4.

---

## Phase 3 — Shared multi-editor + renderer

**Goal:** the reusable advanced-editing UI and its server-side renderer, surface-agnostic.

**Work:**
1. `config/purifier.php`: add **`multiedit-text`** profile = `admin-content` minus `img`.
2. `ContentBlocksRenderer` (Shared): blocks → sanitized HTML; text via `multiedit-text`; image via `<x-shared::media-image>` + optional `<figcaption>`.
3. `<x-shared::multi-editor>` (Blade + Alpine): props `name`, `blocks`, `mode`, `blockTypes`, `scope`, `toolbar`, `min`, `max`. Block array state; add/insert/reorder/delete; text block reuses `editor.blade.php` + `initQuillEditor` on add; image block reuses `image-upload.blade.php` + alt/caption + "Choose existing".
4. Media picker modal (Shared) → `GET /media/library?scope=&page=` backed by `listByScope` (auth per scope).
5. Multipart serialization (`blocks[i][…]`, `mode`).
6. Mode toggle rules (Simple↔Advanced constraints, functional §3).

**Tests (component/JS + renderer):** renderer text-passthrough sanitization (strips `img`), image figure/srcset output; serialization order; picker listing; mode-toggle enable/disable rules. (Surface-level persistence covered in Phase 4.)

**Done when:** the component renders, edits, and serializes; the renderer produces correct HTML — provable without a surface wired.

---

## Phase 4 — News advanced mode (v1 feature)

**Goal:** News authors can build illustrated articles; everything persists, re-renders, and reads back.

**Work (architecture §5, §7):**
1. Migration: add `news.content_blocks` JSON NULL. (`content` reused as render cache; no backfill.) `down()` drops the column.
2. `NewsRequest`: conditional advanced rules (blocks array, per-block type/alt/asset_id-xor-file, summed-text min/max).
3. `NewsService.create/update`: advanced branch — resolve image blocks (`storeUpload` for files, reuse `asset_id`), normalize+sanitize blocks (`multiedit-text`), persist `content_blocks`, write `content = ContentBlocksRenderer::render(...)`, `syncReferences('news', id, assetIds)`. `delete` → `releaseAll('news', id)`. Simple branch unchanged.
4. Form (`admin/news/_form.blade.php`): mode toggle + `<x-shared::multi-editor scope="news">`.
5. Public view unchanged (`{!! $content !!}`).

**Tests:** mode round-trip (simple↔advanced), block CRUD/reorder persistence, summed min/max, empty-block dropping, `content` cache equals rendered blocks, upload-vs-reuse, two News sharing one asset (reference count), delete releases references. Existing News tests stay green.

**Done when:** News advanced mode works, is tested, and simple News is untouched. **v1 ships here.**

---

## Phase 5 — Later surfaces

Off the v1 critical path; each mirrors Phase 4 on its model/form/service.

- **5a — Static pages:** `content_blocks` on `static_pages`, `scope='static-pages'`, same service/form/view pattern. (Also migrate its header image to Media if not already done via Phase 2.5.)
- **5b — Chapters:** `content_blocks` on `story_chapters`, `scope='chapters/{userId}'`. Additional work: render each text block as its own `[data-annotable]` region so annotation canonical-text builds per block (architecture §10); confirm word/character counts and minimum-length sum across text blocks; keep image annotation out of scope (tracked in `Chapter_Annotations.md`).

---

## Cross-cutting checklist (every phase)

- Deptrac green (`./vendor/bin/sail composer deptrac`).
- Tests via `./vendor/bin/sail artisan test:parallel`.
- i18n: FR translation keys for any new UI (project convention — no en/ per recent Quote work).
- No code outside `app/Domains/<domain>`.

## Risks & sequencing notes

- **Phase 1 is a hard prerequisite** for everything; keep it strictly headless (no editor coupling) so it can't stall on UI work.
- **Phase 2 validates the migration recipe cheaply** before committing to the other 4 consumers — if the `asset_id` shape hurts, we learn it on FAQ, not News.
- **Phases 3 and 2 are independent** (both need only Phase 1) and can run in parallel.
- **GC lives from Phase 1** but only matters once a consumer releases references (Phase 2 onward) — schedule `media:gc` from the start so orphans never accumulate.
