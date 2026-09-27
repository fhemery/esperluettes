# Validation messages — follow-ups — functional specification

> REFINE output (`auto` mode — written from `00-request.md` and the code; every
> judgement call is an assumption in `DECISIONS.md`).

## 1. Overview

Two leftovers from `validation-messages`: the global « Oups ! » error box
repeats an identical message once per failing field, and Shared's three custom
rules have no French default message, so a form using them without its own
message would print a raw key.

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Encadré « Oups » | The floating error box of the layout (`<x-shared::flash-block>`), listing every validation error of the submitted form |
| Règles personnalisées | Shared's `maxstripped`, `minstripped`, `required_trimmed` |

## 3. Roles & visibility

N/A — same for every role.

## 4. Functional requirements

### 4.1 « Oups » box without duplicates

1. A form is rejected with several fields failing with the **same** message
   (e.g. two image blocks that are not images).
2. The « Oups » box lists that message **once**. Distinct messages all appear,
   in their original order (first occurrence wins).

### 4.2 French defaults for the custom rules

1. A form using `maxstripped:N`, `minstripped:N` or `required_trimmed` without
   its own message shows a French sentence, never `validation.<rule>`.
2. Wording follows the existing defaults: no field name (`:attribute`),
   `:max` / `:min` replaced by the number. Proposed:
   - `maxstripped` — « Ce champ ne doit pas dépasser :max caractères. »
   - `minstripped` — « Ce champ doit contenir au moins :min caractères. »
   - `required_trimmed` — « Ce champ est obligatoire. »
3. Forms that already override these messages keep theirs.

## 5. Lifecycle

N/A.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | N/A |
| Visibility / privacy | N/A |
| Settings | N/A |
| Notifications | N/A |
| Domain events | N/A |
| Statistics | N/A |
| Moderation | N/A |
| Lifecycle / cascade | N/A |
| Media | N/A |
| Search | N/A |
| i18n | French only; tests stay in `zz` and switch to `fr` for text assertions |
| Mobile | No layout change |
| Accessibility | No markup change beyond fewer list items |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Branch and mode | Same branch as `validation-messages`, `auto` |

## 8. Out of scope

- Whether the « Oups » box should list field errors at all.
- Changing the completeness test against Laravel's English file (the custom
  keys are extras on top of it).
- Rewording existing `messages()` overrides of the custom rules.

## 9. Open questions

None.
