# Comment editor not displaying — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Comment** owns the fix. The blank editor is a Comment host that failed to
ensure Editor assets are on the full-page stack when reply/edit composers appear
only inside AJAX fragments (or when no root `<x-editor::rich-text>` is rendered).

Editor already documents the escape hatch (`editor::components._assets`) and
owns the Vite entrypoints; this task does not change Editor’s public contract
beyond consuming that documented partial from Comment’s list shell.

### 1.1 Changes in other domains

| Domain | Change |
|--------|--------|
| **Editor** | None required — reuse `editor::components._assets` (`@once` + `@push('scripts')` + `@vite`) |
| **Story** | None — chapter show already embeds `<x-comment::…>`; assets come from the list shell |
| **Shared** | None — layout already has `@stack('scripts')` |

## 2. Data model

N/A — no schema, models, or cascade changes.

## 3. PHP architecture

N/A for services, public API, policies, events, routes — behaviour and
authorization stay as today. Enforcement of “who may reply/edit” remains in
existing Comment policies; this fix only restores client assets + Quill init.

## 4. Frontend architecture

### 4.1 Asset loading (primary)

On the **full-page** comment list render (not the fragment), when the viewer is
authenticated and the list is allowed (`!$isGuest && !$error`), include:

```blade
@include('editor::components._assets')
```

Rationale:

- Fragment responses discard `@push`; reply/edit editors in `comment-item` cannot
  load Quill themselves when delivered via `page=0` / `loadMore`.
- Root composer only renders when `canCreateRoot` — often false for authors and
  for users who already posted a root — so relying on root `<x-editor::rich-text>`
  self-load is insufficient (Editor Risk R1).
- `_assets` is `@once`, so including it when the root form is also present does
  not double-load Vite.

### 4.2 Reply open init (secondary)

Alpine handlers on the list: **Éditer** already calls `window.initQuillEditor`
for the edit container; **Répondre** must mirror that for the reply container
when opening the composer (same contract as existing edit path and as
`loadMore`’s post-append init).

### 4.3 Unchanged

- Still use `<x-editor::rich-text>` for root / reply / edit.
- Comment’s own Vite draft module stays as-is.
- No hand-written `@vite` for Editor outside `_assets`.

## 5. Deptrac

**No new edge.** Comment already consumes Editor via Blade only; PHP rulesets do
not grant `EditorPublic` to Comment and need not for an `@include` of a Blade
partial (same stance as editor-domain WRAP: grant `EditorPublic` only where PHP
references Editor).

## 6. Testing strategy

| Layer | What |
|-------|------|
| **Feature (integration)** | Full-page render of the comment list with `canCreateRoot=false` (or equivalent), assert Editor Vite assets appear once in the scripts stack / HTML (pattern aligned with Editor’s asset tests). HTTP create/edit/reply tests stay; they do not catch this bug. |
| **Unit / vitest** | N/A |
| **E2E (Playwright)** | Permanent suite coverage: chapter comments — open **Répondre** and **Éditer**, assert `.ql-editor` (and toolbar) visible/interactive; include a session where the root form is absent. Keep in `e2e/`, not a disposable VERIFY script. |
| **VERIFY** | Drive the E2E / checklist against the visual QA rows; screenshots optional if checklist is fully automated. |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to push Editor assets | A) Comment list shell when auth + allowed · B) Only when `!canCreateRoot` · C) Story chapter show · D) Hand-written `@vite` in Comment | **A** | Documented escape hatch; covers all fragment-only editors; `@once` safe with root form; B is narrower and fragile; C couples Story to Editor; D bypasses Editor’s contract |
| 2 | Reply open re-init | A) Mirror edit’s `initQuillEditor` · B) Rely on loadMore / first paint only | **A** | Edit already does it; reply under `x-show` is the same failure mode |
| 3 | Deptrac Comment→EditorPublic | A) No edge (Blade-only) · B) Grant for “honesty” | **A** | Matches editor-domain decision; no PHP use |
| 4 | Regression net | A) Feature test + permanent E2E · B) E2E only · C) Feature only | **A** | Feature catches missing `@include` cheaply; E2E catches blank Quill / init (user-required) |

## 8. File layout

No new classes. Edits stay inside existing Comment Blade (list shell) and
`e2e/` + Comment feature tests. Structure already legal under
`docs/Domain_Structure.md`.

## 9. Risks acknowledged

| Risk | Trigger to revisit |
|------|--------------------|
| Another host embeds comments only via fragments without the list shell’s include | New Comment surface (e.g. news) ships without copying this pattern — document in Comment/Editor AGENTS at WRAP if missing |
| `initQuillEditor` idempotency | Double-init on reply open + loadMore causes broken toolbar — then harden the global init |
| Guest / `not_allowed` wrongly getting assets | If include condition is loosened past `!$isGuest && !$error` |
