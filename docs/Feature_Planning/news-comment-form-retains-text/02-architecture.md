# News comment form retains text after submit — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Comment** owns the fix. The compose form, localStorage draft module, and
`comment.draft_consumed` flash are already Comment machinery and are shared by
News and Story. News only surfaces the bug because `canCreateRoot` stays true
after a successful root.

### 1.1 Changes in other domains

None. No News policy, view, or route change.

## 2. Data model

No schema change. Drafts remain client-side (`localStorage` key
`comment-drafts:{userId}:{entityType}:{entityId}`). Server continues to flash
`comment.draft_consumed` on successful create only.

## 3. PHP architecture

Unchanged contracts:

- `CommentController::store` still redirects with `comment.draft_consumed`
  `{ scope, userId, entityType, entityId }` on success and does **not** flash it
  on validation failure.
- Existing feature tests that assert the flash stay green; no new PHP surface.

## 4. Frontend architecture

### Contract

On a page load that carries `comment.draft_consumed`:

1. The matching draft slot (root or reply) is cleared from `localStorage`
   **before** any draft restore into a compose form.
2. Compose forms for that entity therefore initialise empty (unless the server
   put content in the textarea via validation `old()` — which success does not).
3. Mid-compose drafts without a consumed flash still restore as today.

### Mechanism (shape)

- A **dependency-free** marker from the flash (inline, no wait on the Vite
  module) records the consumed payload on `window` before draft bootstrap runs.
- `comment-draft` bootstrap / form init **honours that marker first**: clear the
  matching slot, skip restore for that scope, then wire autosave as usual.
- The existing `clearRoot` / `clearReply` helpers remain the storage API; the
  flash script may still call them when `window.commentDrafts` is available, but
  correctness must not depend on that call winning a race against restore.

Do not change News policy to hide the form. Do not stop flushing drafts on
submit (that flush still backs validation-failure recovery when `old()` is
absent).

### Assets

Same Vite entry: `app/Domains/Comment/Resources/js/comment-draft/index.js`,
loaded from `comment-list.blade.php`.

## 5. Deptrac

No new edges.

## 6. Testing strategy

| Layer | What |
|-------|------|
| Vitest | Draft module: when a consumed marker matching the form's entity is present, storage is cleared and restore does not fill the editor; without the marker, restore still works; clear helpers unchanged |
| Feature (PHP) | Existing `comment.draft_consumed` flash assertions remain; no new PHP behaviour required |
| VERIFY / e2e | Optional browser check on a news article: submit root → form empty; keep mid-compose draft restore working. Prefer extending Comment/News e2e only if vitest cannot cover the race |

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where to fix | A Comment draft ordering · B News one-root policy · C Stop flushing draft on submit | A | B masks and contradicts `news-comments`; C breaks validation-failure recovery |
| 2 | How to order clear vs restore | A Pre-bootstrap consumed marker honouring in draft init · B Only empty Quill after late clear · C Always defer bootstrap to DOMContentLoaded | A | Makes success path independent of Vite/DOMContentLoaded race; B is a band-aid if restore already ran with stale storage; C is fragile with deferred modules |
| 3 | Scope of clear | Roots and replies | Both | Same race; flash already carries `scope` |

## 8. File layout

No new PHP classes. Touches stay under existing Comment frontend paths
(`Resources/js/comment-draft/`, comment-list Blade flash script, vitest next to
the draft module if the project already colocates Comment JS tests).

## 9. Risks acknowledged

- **Submit flush + success**: submit still writes the draft, then the next page
  must consume it. If the marker is missing (session flash lost), the bug
  returns — same dependency as today, made reliable when the flash is present.
- **Reply auto-open**: Alpine may open a reply form from `load()` of reply
  draft; after consume, reply slot must be null so it does not re-open with
  leftover body.
