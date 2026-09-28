# Admin pages — flash message shown twice — architecture

*Mode: `auto`. Recommended option taken everywhere; see `DECISIONS.md` → Assumptions.*

## 1. Domain placement

The fix is Blade-only. It removes one line, `<x-shared::flash-block />`, from
each of the 23 admin page views listed in `01-functional.md` §4.1. The admin
layout (`Administration/Private/Resources/views/layouts/layout.blade.php:12`) stays unchanged and
remains the only place the block is rendered.

### 1.1 Changes in other domains

These domains only lose that line in their admin views: Auth, Calendar, FAQ,
Moderation, Events, Config. No PHP changes.

## 2. Data model

None.

## 3. PHP architecture

None: no public API, services, policies, events, routes or controllers.

## 4. Frontend architecture

- Delete the page-level `<x-shared::flash-block />` lines, along with any blank
  line or wrapper left empty by the deletion.
- Do not move the block and do not change the component. It is `position: fixed`,
  so where it sits in the DOM does not affect layout.

## 5. Deptrac

No impact.

## 6. Testing strategy

1. **Rendering regression**: add one feature test in Config, which is where the
   bug was seen. It loads the admin feature-toggles index with
   `session(['success' => '<unique text>'])`, or after a real redirect, and asserts
   that `substr_count` of the text in the response is exactly 1. It must fail
   before the fix.
2. **Guard**: add one test in `Administration/Tests`. It scans
   `app/Domains/*/Private/Resources/views/pages/admin/**/*.blade.php` and fails if
   any file contains `x-shared::flash-block`, with a message saying the admin
   layout owns it. This covers all 23 pages and any future admin page, and it does
   so without a rendering test per page. It reads files and imports no code, so
   there is no deptrac impact.
3. The existing `Shared/Tests/Feature/View/Components/FlashBlockTest.php` stays unchanged.

## 7. Tradeoffs locked

| Tradeoff | Choice | Why |
|---|---|---|
| Owner of the flash | Admin layout | Already there, covers the ~40 admin pages that never had their own block |
| Regression coverage | 1 render test + 1 file-scan guard | Guard catches all current and future pages cheaply; render test proves the real symptom |

## 8. File layout

- 23 edited Blade views, listed in `01-functional.md` §4.1.
- New `app/Domains/Config/Tests/Feature/...` render test. Put it next to the
  existing feature-toggle admin tests.
- New `app/Domains/Administration/Tests/...` guard test.

## 9. Risks acknowledged

- The file-scan guard hard-codes the `pages/admin` path convention. An admin view
  stored elsewhere would not be scanned. This is accepted, because it is the
  project's convention.
