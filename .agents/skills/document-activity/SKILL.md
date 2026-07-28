---
name: document-activity
description: Document a Calendar activity plugin (Jardino, SecretGift, …). Use when asked to "document the Jardino activity", "write the README for an activity", or after adding or changing an activity type under app/Domains/Calendar/Private/Activities/.
---

# Document a Calendar activity

Activities are plugins nested inside the Calendar domain
(`app/Domains/Calendar/Private/Activities/<Type>/`). They are domain-shaped —
own migrations, models, controllers, views, services, listeners — but they are
**not** domains: they live under Calendar's `Private/`, and other domains never
call them.

Follow [`document-domain`](../document-domain/SKILL.md) and its
[content guide](../document-domain/references/content-guide.md) for tone,
structure and the rules on what belongs where. This skill only states what
differs.

## Where the files go

Same three destinations as a domain, one level down — see
`docs/Domain_Structure.md` §"Where documentation lives":

| Destination | Holds |
|---|---|
| `Activities/<Type>/README.md` | The entry point. Almost always enough. |
| `Activities/<Type>/Docs/` | Only if something genuinely remains. Linked from the README. |

Do **not** write activity documentation into the Calendar README. Calendar's
README documents the base activity, the registry and how to *add* an activity —
not what any particular one does.

## What an activity README must answer

1. **What the activity is, for a player.** Two or three sentences. What the user
   sees and does.
2. **How it plugs in** — its registration class, the main component it exposes,
   and anything it declares on `ActivityRegistrationInterface`.
3. **Which domain events it listens to**, and what it does with each. This is
   the part that is genuinely hard to discover from the code, because the
   listeners are registered indirectly.
4. **Its own tables**, keyed by `activity_id`, one line each.
5. **The rules a reader would get wrong** — scoring, milestones, eligibility,
   any state machine, anything time-dependent. State the rule, not the
   implementation.
6. **What is not done**, if anything.

## What to leave out

- Anything a reader learns faster by opening the file. Class-by-class tours,
  method signatures, folder listings.
- The design rationale for choices the code now makes obvious.
- Anything already in the Calendar README (state derivation, role restrictions,
  the registry contract) — link to it instead.

Keep it short. An activity README that runs past ~120 lines is describing the
code rather than the behaviour.
