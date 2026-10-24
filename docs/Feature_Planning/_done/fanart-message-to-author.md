# Fanart submission: private message to the author

**Status:** DONE — 2026-10-23 · **Domain(s):** `Fanart`

## What it does

On the « Proposer un fanart » form (image and sound), a confirmed non-author
can type an optional plain-text message (≤ 500 chars) for the story's authors.
It is never stored on `fanarts`: it travels only in the `comment` property of
the existing `fanart.submitted` notification payload and is rendered as the
escaped ` — « … »` suffix, exactly like the accept/decline reply. One phase,
no migration, no new class. Code matches the plan; no divergence found.

## Key behaviour

- Visibility: only the recipients of `fanart.submitted` — every author of the
  story except the submitter (`FanartNotifier` drops the actor).
- Authors uploading their own artwork never see the field (`@unless
  ($isArtwork)`); a `message` posted anyway is ignored (`uploadArtwork` never
  reads it, and sends no notification).
- Blank or whitespace-only → `null` (`trim(...) ?: null` in
  `StoreFanartRequest::toData()`); the notification text is then byte-identical
  to before (`:comment` replaced by `''`).
- Lifecycle: the message is a snapshot in the notification. It survives
  decline, withdrawal, story deletion; it is lost if the author deletes the
  notification. Not shown in the review viewer.
- HTML in the message is escaped by `FanartNotificationContent::display()`.
- Validation: `nullable|string|max:500`, French error
  `fanart::ui.validation.message_max`; input kept via `old('message')`.

## Where the code lives

| Concern | Path |
|---------|------|
| DTO field `?string $message = null` | `app/Domains/Fanart/Private/Support/SubmitFanartData.php` |
| Rule, error message, trim → null | `app/Domains/Fanart/Private/Requests/StoreFanartRequest.php` |
| Forwarding to notifier | `FanartService::submit()` → `FanartNotifier::fanartSubmitted($fanart, $actorId, ?$message)` → `forFanart(..., $comment)` |
| Notification string (`:comment` appended) | `app/Domains/Fanart/Private/Resources/lang/fr/notification.php` (`submitted.display`) |
| UI strings | `lang/fr/ui.php`: `create.message_label`, `create.message_help`, `validation.message_max` |
| Form field (`#fanart-message`, between chapter and attestation) | `app/Domains/Fanart/Private/Resources/views/pages/create.blade.php` |
| Tests | `Tests/Feature/SubmitFanartTest.php` (7 cases), `ArtworkTest.php` (author upload ignores message), `FanartNotificationsTest.php` (escaping), `FanartViewsI18nTest.php` |
| E2E fixture | `app/Domains/Fanart/Database/Seeders/E2eFanartSeeder.php` sets `fanart-images-mode` / `fanart-audio-mode` to `yes` for e2e `author` and `confirmed` |
| E2E page object kept | `e2e/pages/NotificationsPage.ts` (generic `/notifications` list) |

## Extension points used

- Notification: no new type; reuses the `comment` property already carried by
  `FanartNotificationContent` for `fanart.accepted` / `fanart.declined`.

## Decisions worth remembering

- Notification only — not stored, not in the review viewer (D1).
- Optional, 500 chars, plain text (D2).
- Submissions only, not author artworks (D3).
- All co-authors receive it; no moderation/report path (assumptions 1–2, not
  explicitly confirmed by the user).
- Inline `:comment` suffix rather than a separate block (assumption 4).

## Counter-intuitive bits

- Default fanart mode is `designated`, so in e2e every seeded reader got 403 on
  the create page until `E2eFanartSeeder` opened the modes; the kept
  `fanart-artwork-module` spec was failing for that reason before this task.

## Not done

- Non-goals (spec §8): storing the message or showing it in the review
  viewer; replies / conversation; moderating or reporting the message; rich
  text or links.
- Nothing cut mid-build. Open question (accepted): a deleted notification loses
  the message.
- E2E: `e2e/tests/features/fanart-message-to-author.spec.ts` deleted at WRAP
  (feature-local, all server rules are covered by feature tests). Its
  callerless `FanartMessageForm` page object (duplicate of `FanartCreatePage`)
  removed with it; `NotificationsPage` kept as a reusable page object.
- No backlog row created: nothing left worth doing.
