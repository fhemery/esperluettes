# Fanart submission: private message to the author — implementation plan

> PLAN output. The phase index at the top is the summary; everything below is
> detail. BUILD reads **one phase at a time** and nothing else of this file, so
> every phase must stand alone: name the `02-architecture.md` sections it needs,
> and state what earlier phases left behind rather than assuming it was read.

- Functional spec: [`01-functional.md`](./01-functional.md)
- Architecture: [`02-architecture.md`](./02-architecture.md)

## Phase index

| # | Phase | Size | Depends on | Status |
|---|-------|------|------------|--------|
| 1 | Message field on submission, carried in the `fanart.submitted` notification | S | — | TODO |

Sizes: S ≈ half a day, M ≈ 1–2 days, L → split it.
Status per phase: `TODO` · `WIP` · `DONE`. BUILD updates this table as it goes;
it is what lets `WIP:BUILD (3/7)` resume correctly.

One phase only: no migration, no new class, no reshaping of existing code with
other consumers (the `comment` property of `FanartNotificationContent` and its
escaped ` — « … »` suffix already exist and are already used by
`fanart.accepted` / `fanart.declined`). Splitting server and form into two
phases would leave a phase whose only consumer is a test, for a change of
about forty lines. No checkpoint row: nothing shared is reshaped; the existing
`FanartNotificationsTest` and `ArtworkTest` cover the neighbouring behaviour.

## Working agreement

- One phase = one commit (or one PR). Each phase ships independently, keeps
  `pnpm run gate` green, and is revertable on its own.
- Failing test first, then the implementation.
- We do not move to phase N+1 until phase N's acceptance criteria are met.
- Re-ordering phases mid-build is a decision to surface, not to take silently.

---

## Phase 1 — Message field on submission, carried in the `fanart.submitted` notification

**Goal.** A confirmed non-author can attach an optional plain-text message
(≤ 500 chars) to a fanart submission; every author except the submitter reads
it, escaped, at the end of their `fanart.submitted` notification; it is never
stored on `fanarts`.

Read: architecture §3.2 (services), §3.5 (form request), §4 (frontend), §6
(testing). Decisions: `DECISIONS.md` rows 1–3 and assumptions 4–5 (inline
`:comment` suffix; blank = no message). Nothing earlier in this plan.

Current state you build on (verified at PLAN time):
- `FanartNotificationContent` (Public/Notifications) already has
  `?string $comment`, serialises it as `comment`, and its `display()` builds
  ` — « <escaped comment> »` and passes it as the `:comment` replacement. Only
  the lang string of `submitted` lacks the `:comment` placeholder.
- `FanartNotifier::forFanart($class, $fanart, $actorId, ?string $comment = null)`
  already forwards a comment; `fanartSubmitted(Fanart $fanart, int $actorId)`
  just does not take one yet.
- `FanartController::store` calls `uploadArtwork` for authors (no
  notification) and `submit` otherwise, both with `$request->toData()`.

**Deliverables.**
- `app/Domains/Fanart/Private/Support/SubmitFanartData.php` — add a last
  constructor param `public readonly ?string $message = null` (default keeps
  existing call sites, e.g. `ArtworkTest`, compiling).
- `app/Domains/Fanart/Private/Requests/StoreFanartRequest.php`
  - rule `'message' => ['nullable', 'string', 'max:500']`;
  - message `'message.max' => __('fanart::ui.validation.message_max')`;
  - `toData()` passes `message:` as the trimmed input, or `null` when the
    trimmed value is `''` (assumption 5).
- `app/Domains/Fanart/Private/Services/FanartService.php` — `submit()` calls
  `$this->notifier->fanartSubmitted($fanart, $userId, $data->message)`. The
  message is **not** added to `Fanart::create([...])`. `uploadArtwork()` is
  untouched (it never reads `message`).
- `app/Domains/Fanart/Private/Services/FanartNotifier.php` —
  `fanartSubmitted(Fanart $fanart, int $actorId, ?string $message = null)`
  forwards `$message` as the `$comment` argument of `forFanart`.
- `app/Domains/Fanart/Private/Resources/lang/fr/notification.php` — append
  `:comment` to `submitted.display` (after the closing `</a>`, same as
  `accepted.display`). With no message, `display()` replaces it with `''`, so
  the text is unchanged.
- `app/Domains/Fanart/Private/Resources/lang/fr/ui.php`
  - `create.message_label` → `Message pour les auteur·ices (facultatif)`;
  - `create.message_help` → `Visible uniquement par les auteur·ices de l'histoire, dans la notification qu'iels reçoivent. 500 caractères maximum.`;
  - `validation.message_max` → `Le message ne doit pas dépasser 500 caractères.`
  (Wording follows the file's existing inclusive `auteur·ices` style; the spec's
  « message pour les auteurs » is the concept, not a mandated string.)
- `app/Domains/Fanart/Private/Resources/views/pages/create.blade.php` — inside
  the form, after the chapter `<select>` block and before the attestation,
  wrapped in `@unless ($isArtwork) … @endunless`:
  `<x-shared::input-label for="fanart-message" :value="__('fanart::ui.create.message_label')" />`,
  a plain `<textarea id="fanart-message" name="message" maxlength="500" rows="3" aria-describedby="fanart-message-help" class="w-full border border-accent rounded p-2 surface-read text-on-surface">{{ old('message') }}</textarea>`
  (same styling as the review comment textarea in `fragments/viewer.blade.php`),
  a `<p id="fanart-message-help" class="text-sm">` with `create.message_help`,
  and `<x-shared::input-error :messages="$errors->get('message')" />`.
  Shared by the image and sound kinds.
- `app/Domains/Fanart/AGENTS.md` — on the **Notification** bullet, one clause:
  `fanart.submitted` carries the submitter's optional message in `comment`;
  it is not stored on `fanarts`.

**Tests.** Write first; all Pest feature tests, real users (`alice` author,
`carol` co-author, `bob` confirmed reader), through the `fanart.store` route
unless stated.

`app/Domains/Fanart/Tests/Feature/SubmitFanartTest.php` (reuse
`imagePayload()` / `audioPayload()` with a `message` override):
- `it('delivers the submitter message to every author in the submitted notification')`
  — two authors, `bob` posts an image with `message => 'Merci pour cette histoire'`;
  render the latest `fanart.submitted` (set locale `fr`, via
  `NotificationPublicApi::getNotificationsByIds(...)->htmlDisplay`, as
  `renderLatest()` does in `FanartNotificationsTest`) → contains the message;
  targets are exactly `[alice, carol]` (not `bob`).
- `it('carries the message on an audio submission too')` — `audioPayload`
  with a message → notification contains it.
- `it('leaves the submitted notification unchanged without a message, or with a blank one')`
  — dataset: field absent, `''`, `'   '` → stored payload `comment` is `null`
  and the rendered text contains no `« ` suffix after the story link (assert
  it does not contain ` — « `).
- `it('rejects a message over 500 characters and creates nothing')` —
  `str_repeat('a', 501)` → `assertSessionHasErrors('message')`, no `Fanart`
  row, no `fanart.submitted` notification; 500 chars exactly is accepted.
- `it('does not store the message on the fanart')` — after a submission with a
  message, `DB::table('fanarts')->first()` serialised to JSON does not contain
  the message string, and `Schema::hasColumn('fanarts', 'message')` is false.
- `it('shows the message field on the submission form only')` — `bob` on
  `fanart.create` image and audio → `assertSee('name="message"', false)` and the
  label; `alice` (author, artwork mode) on `fanart.create` image →
  `assertDontSee('name="message"', false)`.
- `it('keeps the typed message when validation fails')` — post with a message
  and no title, follow redirect (`from(route('fanart.create', ...))`) → the
  message is in the re-rendered textarea.

`app/Domains/Fanart/Tests/Feature/ArtworkTest.php`:
- `it('ignores a message sent with an author artwork upload')` — `alice` posts
  an image with `message` → published as before, no notification of any type
  (co-author `carol` included), message not in the `fanarts` row.

`app/Domains/Fanart/Tests/Feature/FanartNotificationsTest.php`:
- extend the `submitted` dataset row, or add
  `it('appends the escaped submitter message to the submitted notification')`
  — `fanartSubmitted($fanart, $artist->id, '<script>x</script>')` → rendered
  HTML contains `&lt;script&gt;` and not `<script>`.

`app/Domains/Fanart/Tests/Feature/FanartViewsI18nTest.php`:
- also render `route('fanart.create', [$story->slug, 'image'])` as `bob` (the
  author sees the artwork variant, which hides the new field) and assert no
  `fanart::` raw key; assert `__('fanart::ui.validation.message_max')` is not
  the raw key.

**Acceptance.**
- ✅ `bob` (confirmed, non-author) posting an image or a sound with a message
  → `alice` and `carol` each have a `fanart.submitted` notification whose text
  ends with ` — « <message> »`; `bob` has none.
- ✅ HTML in the message is rendered escaped (`&lt;script&gt;`).
- ✅ Without a message, or with a whitespace-only one, the notification text is
  byte-identical to today's (`comment` is `null` in the payload).
- ✅ A 501-character message → validation error on `message` in French
  (`Le message ne doit pas dépasser 500 caractères.`), nothing created, input
  kept; 500 characters are accepted.
- ✅ No column `message` on `fanarts`; the message string never appears in the
  `fanarts` row.
- ✅ The create page shows the labelled textarea with its hint to a non-author
  (image and sound); an author's artwork create page does not show it, and an
  author upload carrying `message` notifies nobody.
- ✅ Existing `SubmitFanartTest`, `ArtworkTest`, `FanartNotificationsTest`,
  `ReviewFanartTest` stay green unchanged (apart from the additions above).
- ✅ `pnpm run gate` green.

---

## Visual QA checklist

Filled by VERIFY. One row per surface worth looking at with real eyes, written
during PLAN while the flows are fresh.

| Surface | Check | OK? |
|---------|-------|-----|
| Image submission form, confirmed non-author, desktop | Field « Message pour les auteur·ices (facultatif) » sits between chapter and attestation, label + hint readable, textarea full width, theme colours consistent with the other inputs | |
| Sound submission form, confirmed non-author | Same field present, same placement | |
| Submission form, mobile width (~375px) | Textarea full width, no overflow, hint wraps cleanly | |
| Author's artwork upload form (image and sound) | No message field shown | |
| Validation error, 501-char message (bypass `maxlength` via devtools) | French error under the field; message, title and other inputs kept | |
| Author's notification list, submission with a short message | « X a proposé une image (« … ») sur *Histoire* — « message » », links still work | |
| Author's notification list, submission without message (empty state) | Text identical to before the feature, no dangling ` — « »` | |
| Co-author's notification list | Receives the same notification with the message | |
| Submitter's notification list | Receives nothing for their own submission | |
| Notification with a 500-char message, desktop and mobile | Long line wraps, no layout break in the notification list / dropdown | |
| Notification with `<b>html</b>` in the message | Shown literally, not interpreted | |
| Notification after the fanart was declined / withdrawn / its story deleted (stale data) | Still renders with the message (snapshot) | |
| Notification after the submitter was deactivated | Still renders with the message (snapshot; actor link behaves as today) | |

## Open items

None blocking. Verified at PLAN time: `FanartNotificationContent::$comment`
and its escaped suffix exist; `forFanart` already takes a comment;
`submitted.display` lacks `:comment` (Phase 1 adds it); no shared textarea
component exists in `Shared` (`input-label`, `text-input`, `input-error` only),
hence the plain `<textarea>` styled like the review viewer's.

- Phase 1: the French strings above are proposals; the user may reword them at
  VERIFY.
