# <Task title> — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Which domain owns the feature, and why. If a new domain is created, justify it
against adding to an existing one.

### 1.1 Changes in other domains

One sub-section per touched domain, stating the *minimum* change needed and
whether it is a new extension point or a direct call.

## 2. Data model

### 2.1 Tables

Column list with types, nullability, indexes. Foreign keys only inside the
owning domain.

### 2.2 Model

Eloquent model, casts, relations. PHP attribute syntax (`#[Table]`,
`#[Fillable]`, …) per the project standards.

### 2.3 Lifecycle rules

Cascade, soft-delete, nullify — mapped from §5 of the functional spec.

## 3. PHP architecture

### 3.1 Public API

The surface other domains may call: methods, DTOs, events.

### 3.2 Services

Business logic. Controllers never touch models directly.

### 3.3 Policy / authorization

The server-side enforcement of §3 of the functional spec.

### 3.4 Events and listeners

Emitted events; listened-to events (user deactivated/deleted, parent deleted).

### 3.5 Routes, controllers, form requests

No `PATCH` — the production WAF blocks it. Use `PUT`.

## 4. Frontend architecture

Blade components, Alpine stores, JS module layout, CSS. Which existing shared
components are reused rather than duplicated.

## 5. Deptrac

New edges required in `deptrac.yaml`, each with a one-line justification. If no
new edge is needed, say so.

## 6. Testing strategy

What is covered by integration (feature) tests, what by unit tests, what by
vitest, and what can only be checked visually in VERIFY. Integration tests are
the default; unit tests only where the logic is genuinely isolated.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | | | | |

## 8. File layout

Where the **new** classes land, as a tree, following `docs/Domain_Structure.md`
— enough to check the structure is legal. Existing files that need editing are
named where §1.1–§4 already discuss them; do not restate them as a change list,
that is `03-plan.md`'s job.

## 9. Risks acknowledged

Known weak points, with the trigger that would make us revisit them.
