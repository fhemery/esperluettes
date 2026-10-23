# Fanart submission: private message to the author — architecture

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

Entirely inside **Fanart**. The feature extends the existing submission flow
(`create` form → `StoreFanartRequest` → `FanartService::submit`) and the
existing `fanart.submitted` notification. No other domain is touched.

### 1.1 Changes in other domains

None. The Notification domain already carries an opaque payload per type;
`FanartSubmittedNotification` already holds a `comment` property (shared
`FanartNotificationContent`), currently unused for this type.

## 2. Data model

### 2.1 Tables

No migration. Decision 1 of the spec: the message lives only in the
notification payload.

### 2.2 Model

Unchanged. The message is a transient value, never set on `Fanart`.

### 2.3 Lifecycle rules

None of its own. The notification is a denormalised snapshot (see
`FanartNotificationContent`), so the message follows the notification's
lifetime, as the accept/decline reply already does.

## 3. PHP architecture

### 3.1 Public API

Unchanged. `FanartSubmittedNotification` keeps its payload shape; `comment`
becomes populated for this type (the type's row in `docs/notification-types.md`
already lists it).

### 3.2 Services

The message rides alongside the submission as an optional value:
`SubmitFanartData` gains a nullable `message`, `FanartService::submit` hands it
to `FanartNotifier::fanartSubmitted`, which forwards it as the notification's
`comment`. It is never persisted. Blank or whitespace-only input is normalised
to `null` so the notification text is unchanged when nothing was written.

### 3.3 Policy / authorization

No new rule. Submission is already gated for confirmed non-authors; authors
take the `uploadArtwork` path, which never reads the message.

### 3.4 Events and listeners

None added.

### 3.5 Routes, controllers, form requests

No new route. `StoreFanartRequest` accepts an optional `message` field
(`nullable|string|max:500`) with French validation messages. The field is only
read on the non-author path; for an author it is ignored.

## 4. Frontend architecture

One plain `<textarea>` on `pages/create.blade.php`, rendered only when the page
is not in artwork mode (`@unless ($isArtwork)`), shared by image and sound
kinds. It carries a label, a character-limit hint and the standard error
display, using the existing shared input components. The notification text
reuses the reply's rendering: the escaped message is appended to the
`submitted` display string as the same `:comment` suffix.

## 5. Deptrac

No new edge.

## 6. Testing strategy

Integration tests (extend `SubmitFanartTest` and `FanartNotificationsTest`):
- a non-author submitting with a message → every author's notification carries
  it, escaped; the submitter gets none;
- no message / blank message → notification text unchanged;
- over 500 characters → validation error, nothing created;
- nothing about the message is persisted (no new column, none on `fanarts`);
- an author's artwork upload with a `message` field ignores it.

`FanartViewsI18nTest` covers the new lang keys. The form field and the
notification rendering are re-checked visually in VERIFY.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where the message lives | Notification only; also stored + shown in review viewer | Notification only | Confirmed in REFINE (decision 1); no migration, mirrors the reply |
| 2 | How it appears in the notification | Inline `:comment` suffix like the reply; separate block | Inline suffix | Reuses existing, tested rendering and escaping; no new notification view |

No further forks: placement, data and authorization are forced by decision 1.

## 8. File layout

No new classes.

## 9. Risks acknowledged

- The message is lost if an author deletes the notification before reviewing
  (accepted in REFINE). Revisit if testers report it: the fix is a column on
  `fanarts` shown in the review viewer, cleared on decision.
- A long message inline makes a long notification line; 500 characters matches
  the reply's limit, so the display is already proven at that size.
