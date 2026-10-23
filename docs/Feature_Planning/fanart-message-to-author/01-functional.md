# Fanart submission: private message to the author — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.

## 1. Overview

When a reader proposes a fanart (image or sound) on a story, they can add a short
optional note addressed to the story's authors. The note travels in the
notification the authors already receive on submission, the same way the
authors' reply travels back when they accept or decline. Value: the submitter
finally has a way to speak to the authors (testers' feedback).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Message au auteur | Optional short text a submitter attaches to a fanart proposal, visible only to the story's authors via their notification |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | nothing | nothing |
| `user` (non-confirmed) | nothing (fanart submission is not open to them) | nothing |
| `user-confirmed` (submitter) | the field on the submission form | write a message when submitting a fanart |
| Author / co-author of the story | the message, in their `fanart.submitted` notification | read it |
| Moderator / Admin | nothing specific (not stored) | nothing |

## 4. Functional requirements

### 4.1 Submit a fanart with a message

1. A confirmed non-author opens the fanart submission form (image or sound).
2. The form shows an optional « message pour les auteurs » field, plain text,
   500 characters max.
3. On submission, every author of the story (except the submitter) receives the
   existing « fanart proposé » notification; when a message was given, it is
   appended to the notification text, escaped, in the same style as the reply on
   accept/decline.
4. Empty message: the notification is unchanged.
5. Over 500 characters: validation error in French, the form keeps the input.

### 4.2 Authors uploading their own artwork

The field is not shown: those uploads are published at once and notify nobody.

## 5. Lifecycle

- The message is **not stored**; it exists only inside the notification.
- Accepting, declining, or withdrawing the fanart does not touch the
  notification: the message stays readable there until the author removes it.
- Submitter deactivated or deleted, story or chapter deleted: nothing specific;
  the notification is a snapshot and behaves as today.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Same as the existing submission: confirmed users only |
| Visibility / privacy | Visible only to the story's authors (recipients of the notification) |
| Settings | N/A — no new preference |
| Notifications | Existing `fanart.submitted` type gains the optional message |
| Domain events | N/A — no new event |
| Statistics | N/A |
| Moderation | N/A — not stored, no report path |
| Lifecycle / cascade | See §5 |
| Media | N/A |
| Search | N/A |
| i18n | New label, hint and validation strings in French |
| Mobile | Plain textarea, no special handling |
| Accessibility | Labelled field with a character-limit hint |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Where can authors read the message? | Notification only, not stored or shown in the review viewer |
| 2 | Optional? Length? | Optional, 500 characters |
| 3 | Field for authors' own artwork uploads? | No, submissions only |

## 8. Out of scope

- Storing the message or showing it in the review viewer.
- Replying in a thread, or any conversation between submitter and authors.
- Moderating or reporting the message.
- Rich text or links.

## 9. Open questions

None blocking.
- Non-blocking: if an author deletes the notification before reviewing, the
  message is lost (accepted consequence of decision 1).
