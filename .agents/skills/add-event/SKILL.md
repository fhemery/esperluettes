---
name: add-event
description: Create a domain event and wire its consumers through the Events bus. Use when a domain needs to tell other domains something happened — "emit an event when a chapter is published", "react to user deactivation", "add a listener for X" — or when a feature's architecture calls for a new event.
---

# Add a domain event

Events are the cross-domain backbone: they let a domain announce a fact without
knowing who cares. Reference: `app/Domains/Events`.

## 1. Define it

If the event comes from a feature's design, start from §3.4 of its
`docs/Feature_Planning/<slug>/02-architecture.md`. Otherwise settle, and write
down:

- the **emitting domain** — the one that owns the fact;
- the **event name** and class name;
- the **payload** — everything a consumer could need, because a consumer must
  never go back to the database to enrich it;
- the **consumers**, and what each does with it.

## 2. Create the event class

- Under the emitting domain's `Public/Events/`. **Events are always public** —
  that is the whole point of them.
- Translations for the event summary are PHP-namespaced and **French only**;
  create an `events.php` lang file if the domain has none.

## 3. Register it

`EventBus::registerEvent()` in the emitting domain's `ServiceProvider::boot()`.

## 4. Emit it

From the relevant domain **service**, and only **once the operation has
succeeded** — never optimistically, never from a controller.

## 5. Test the emission

Cover every path: emitted on success, not emitted on failure, payload correct.
Use the helpers in `app/Domains/Events/Tests/helpers.php`.

## 6. Create the consumers

- Listener in the consuming domain, subscribed in **its** `ServiceProvider`.
- A listener must be idempotent-safe about failure: it runs after the fact and
  must not break the emitting operation.

## 7. Test each consumer

Dispatch the event with `dispatchEvent` from
`app/Domains/Events/Tests/helpers.php` and assert the consumer's effect —
cover every case, including the ones where it should do nothing.

## Gate

`npm run gate` must be green: deptrac in particular, since a consumer importing
the emitting domain's `Public/Events/` may need an edge that does not exist yet.
