# Admin pages — flash message shown twice — functional specification

*Mode: `auto`. No interview; judgement calls are in `DECISIONS.md` → Assumptions.*

## 1. Overview

On admin pages, a status/success/error flash or a validation-error list shows
twice. That is because the admin layout and the page both render
`<x-shared::flash-block />`. After the fix, it shows exactly once, on every admin page.

## 2. Vocabulary

- **Flash block**: `<x-shared::flash-block />`
  (`app/Domains/Shared/Resources/views/components/flash-block.blade.php`). It is a
  fixed-position toast that renders `session('status')`, `session('success')`,
  `session('error')` and the `$errors` bag, then auto-hides after 10 s. It takes no props.
- **Admin layout**: `<x-admin::layout>`
  (`app/Domains/Administration/Private/Resources/views/layouts/layout.blade.php`).
  Every admin page uses it, and there is no other admin layout variant.

## 3. Roles & visibility

The roles are unchanged: anyone who can reach an admin page (admin, tech-admin,
moderator, depending on the page). Only the rendering changes.

## 4. Functional requirements

### 4.1 One flash per admin page

- The admin layout is the single owner of the flash block. It already renders
  it at `layout.blade.php:12`.
- These 23 page views drop their own `<x-shared::flash-block />`:
  - Auth
    - roles: index, create, edit
    - activation-codes: index, create
  - Calendar activities: index, create, edit
  - FAQ
    - faq-categories: index, create, edit
    - faq-questions: index, create, edit
  - Moderation
    - moderation-reasons: index, create, edit
    - moderation-reports: index, show
  - Events domain-events: index, show
  - Config feature-toggles: index, edit
- A redirect with `success`, `error` or `status`, or with validation errors, to
  any of those pages renders the message exactly once.
- Admin pages that never had their own block, such as the dashboard, logs and
  maintenance pages, keep showing the layout's flash. Their behaviour does not change.

## 5. Lifecycle

None. The flash is still session-scoped and auto-hides after 10 s.

## 6. Cross-cutting concerns

- Notifications, events, privacy, settings: none.
- Public (non-admin) layout `Shared/…/layouts/app.blade.php` keeps its single block —
  untouched.
- Inline `session('status')` on Auth login / forgot-password / verify-email /
  account form: different purpose, untouched.

## 7. Decisions confirmed

None were asked, because the mode is auto. See the `DECISIONS.md` assumptions.

## 8. Out of scope

- Redesigning the flash block, including its position and timing.
- Changing how non-admin pages handle flashes.

## 9. Open questions

None.
