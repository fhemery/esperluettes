# Shared `image-upload` lang ownership — functional specification

## 1. Overview

Move the three Story-borrowed `shared::image-upload` strings into Story's cover
lang, then delete Shared's orphan `image-upload` lang file. No UI redesign.

## 2. Vocabulary

N/A — no new nouns.

## 3. Roles & visibility

Unchanged. Story cover tab copy only.

## 4. Functional requirements

1. Cover custom tab shows the same French strings for drop prompt, max size, and
   oversize error as today.
2. Those strings resolve from Story lang (`story::shared.cover.*`), not Shared.
3. Shared no longer ships `image-upload.php`.
4. `<x-shared::sound-upload>` lang untouched.

## 5. Lifecycle

N/A.

## 6. Cross-cutting

| Concern | Decision |
|---------|----------|
| i18n | Keys move to Story; unused Shared keys deleted with the file |
| Media | N/A — cover UI redesign out of scope |
| Other | N/A |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Where do the keys go? | Story cover lang; delete Shared file |

## 8. Out of scope

- Cover → `<x-media::image-field>`
- Sound lang / component
- Changing French wording

## 9. Open questions

None.
