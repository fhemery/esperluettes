# Fix 500 on 404 page when a model-bound route misses — functional specification

## 1. Overview

A mistyped or deleted URL on a model-bound route (`/fanarts/9999`, `/profile/nobody-xx`) shows a 500 error (« Undefined variable $theme ») instead of the 404 page. All pages that render the shared layout in an error context must display normally, with the right theme.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Thème | the seasonal or user-chosen visual theme the layout and error illustrations depend on |

## 3. Roles & visibility

Every visitor (guest, `user`, `user-confirmed`, staff) gets the normal 404 page, with the same buttons as for any other unknown URL.

## 4. Functional requirements

### 4.1 Missing model-bound resource
1. A visitor requests a URL whose route-bound model does not exist.
2. The 404 page is shown (HTTP 404), same content as for an unknown URL.
3. The theme shown is the visitor's own when it can be resolved (signed-in user preference), the seasonal theme otherwise.

### 4.2 Other error pages
The 419 and 500 pages render the same shared layout, and hit the same weakness when the error occurs before the theme is resolved: they must not crash either.

## 5. Lifecycle

N/A.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | all roles, same page |
| i18n | no new text |
| All others | N/A |

## 7. Decisions confirmed

None beyond the request.

## 8. Out of scope

- The 500 raised by a deactivated user's profile page for other reasons, if it persists after this fix (separate cause, to be reported).
- Redesigning the error pages.

## 9. Open questions

None blocking.
