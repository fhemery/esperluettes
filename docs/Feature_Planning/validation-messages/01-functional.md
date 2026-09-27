# Validation messages — file rules print raw keys — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

When a form is rejected, every error the user sees must be a readable French
sentence. Today, rules without a domain-specific message (`image`, and most of
Laravel's built-in rules) print their raw key (`validation.image`), and errors
on individual editor blocks (an image block's file) are not shown at all. The
fix is app-wide: any form relying on the framework's default messages gets
French text, and block-level errors on the block editor become visible.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Message par défaut | The French text shown for a built-in rule (`required`, `image`, `max`, `mimes`, …) when the form request defines no message of its own |
| Erreur de bloc | A validation error on one block of the block editor (`<x-editor::multi>`), e.g. the uploaded file of an image block |

## 3. Roles & visibility

N/A — validation feedback is shown to whoever submitted the form, whatever the
role. No data, no permission change.

## 4. Functional requirements

### 4.1 Default messages

1. Every Laravel built-in validation rule has a French default message.
2. A rule that a form request already overrides in its own `messages()` keeps
   the domain's message — defaults only fill the gaps.
3. No raw translation key (`validation.image`, `validation.max.file`, …) is ever
   displayed.
4. File size messages state the unit the rule actually uses (`max:2048` on a
   file is 2048 **Ko**, not Mo). The existing « :max Mo » default is wrong and is
   corrected.
5. Messages refer to the field by a readable French name, never by its technical
   path (`blocks.3f2a.file`, `header_image.file`). Where a field has no French
   label, the message is phrased so it still reads correctly.

### 4.2 Block errors on the block editor

1. An author saves a chapter (or an admin saves a news / static page) with an
   image block whose file is too large or not an image.
2. The form is re-displayed with the existing « blocks » error area showing the
   French message (e.g. « Le fichier doit être une image. », « Le fichier ne doit
   pas dépasser 2048 Ko. »).
3. Applies to the three block-editor forms: chapter (Story), news (News),
   static page (StaticPage).

## 5. Lifecycle

N/A — no stored data.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A — same feedback for every role |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | Only the error text for image uploads; upload behaviour unchanged |
| Search | N/A |
| i18n | The whole feature. French only (the app is French-only); tests keep running under the `zz` locale and keep asserting keys |
| Mobile | Error area is the existing one; no layout change |
| Accessibility | Block errors are rendered with the existing `input-error` component, like every other field error |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Request wording and mode | `00-request.md` accepted as drafted; `auto` mode |

Everything else is an assumption — see `DECISIONS.md` § Assumptions.

## 8. Out of scope

- Rewording messages domains already define in their own `messages()`.
- Client-side (JS) validation of file size/type before upload.
- Changing the rules themselves (the 2 Mo limit, accepted types).
- Per-block inline error placement inside the editor next to the faulty block —
  errors appear in the form's existing blocks error area.
- Any locale other than French.

## 9. Open questions

- **non-blocking** — Where the defaults live (Shared JSON vs a root `lang/fr/`
  file vs a translation package) is a DESIGN question.
