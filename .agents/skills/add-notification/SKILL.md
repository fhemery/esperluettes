---
name: add-notification
description: Create a new user notification and make sure it is sent. Use when a feature needs to tell a user something happened — "notify the author when someone quotes their chapter", "send a notification on X" — or when a functional spec's notification section needs implementing.
---

# Add a notification

Read `app/Domains/Notification/README.md` first for the system's overview.

## 1. Settle the contract

- **Source user** and **target users** — who acted, who is told.
- The **fields** the notification stores, and the translation it renders.
- **A notification must never read the database when displayed.** Everything it
  needs is captured at creation time. It may generate route URLs dynamically;
  it may not look anything up.
- Does it leak something the feature calls private? If the spec says a note or a
  draft is private, it does not go in the notification body.

## 2. Decide where it is sent from

| Situation | Where |
|-----------|-------|
| The domain that notifies is the one performing the action | in the **service**, after the action succeeds |
| The action happens in another domain | in an **event listener** on that domain's event |

If you need the second and no suitable event exists, use the `add-event` skill
first.

## 3. Create and register the notification

- Register it in the owning domain's `ServiceProvider`.
- Translations are PHP-namespaced and **French only**; create a
  `notification.php` lang file if the domain has none.
- HTML is allowed in notification translations, and is the right way to render
  links.

## 4. Implement the send

Either in the service, or in the listener registered in the `ServiceProvider` —
per §2. Suppress self-notification: an actor is not told about their own action.

## 5. Test it

- Assert with the helpers in `app/Domains/Notification/Tests/helpers.php`.
  **Never assert against the notification table directly.**
- Drive the test through the real path: either call the full action, or publish
  the event the listener subscribes to using `dispatchEvent` from
  `app/Domains/Events/Tests/helpers.php`.
- Cover the negative cases too: the actor is not notified, and a user who should
  not receive it does not.

## 6. Document it

Add the new type to `docs/notification-types.md`, then `npm run gate`.
