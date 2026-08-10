# Story preferences — architecture

> DESIGN output. Describes **how** the feature is built. Every tradeoff the user
> arbitrated is recorded in §7 with the rejected options.
>
> Scope: **shape and contracts, not a change list.** Signatures, data shapes,
> enforcement points, deptrac edges. The file-by-file list of edits belongs to
> `03-plan.md` and must not be duplicated here — when the two disagree, the
> plan is the one BUILD reads, and the duplicate is what made them disagree.

- Functional spec: [`01-functional.md`](./01-functional.md)

## 1. Domain placement

**Story** owns the feature: settings registration (new **Histoires** tab + two
booleans), discovery-list filtering, and TW display gating. Behaviour is
story-shaped; Settings is only the persistence/UI shell.

No new domain. Assumption #4 from REFINE is confirmed.

### 1.1 Changes in other domains

#### Settings

None beyond Story registering through the existing `SettingsPublicApi`
(tab → section → parameters). Storage and save UX stay as today.

#### Search

No Settings dependency. Continues to call
`StoryPublicApi::searchStories($query, $viewerUserId, …)`. Story applies the
hide-from-lists preference inside that path when a viewer id is present.

#### ReadList

**No changes.** Hide-from-lists does not apply (decision #13). Hide-display is
owned by `<x-story::trigger-warnings>`, which ReadList already mounts — no
ReadList wiring.

#### Dashboard

No code change expected: discover is `<x-story::random-stories-component />`,
which goes through Story’s `getRandomStories`. Story applies the list filter
there. Hide-display is automatic via the TW component on cards.

## 2. Data model

### 2.1 Tables

No new tables. Preferences use the existing `settings` rows
`(user_id, domain, key, value)` owned by Settings. Only non-defaults are
stored. Story disclosure remains `stories.tw_disclosure`
(`listed` / `no_tw` / `unspoiled`).

### 2.2 Model

No new Eloquent model. Registration constants live on `StoryServiceProvider`
(same pattern as `KEY_HIDE_COMMENTS_SECTION` / ReadList’s own tab):

| Constant | Value |
|----------|-------|
| `TAB_STORIES` | `'stories'` |
| section (e.g. `SECTION_READING`) | `'reading'` |
| `KEY_HIDE_TRIGGER_WARNINGS` | `'hide-trigger-warnings'` |
| `KEY_HIDE_STORIES_WITH_TW` | `'hide-stories-with-tw'` |

Both parameters: `ParameterType::BOOL`, default `false`, available to `user` and
`user-confirmed` (Settings role filtering is UI-only as today).

### 2.3 Lifecycle rules

Follow Settings: deactivate leaves rows; delete follows existing Settings
user-deletion behaviour. No feature-owned cascade. No events on preference
change.

## 3. PHP architecture

### 3.1 Public API

No new public helpers required for this feature: Search and discovery stay on
existing Story APIs; ReadList is out of scope for the list filter.

`searchStories(string $query, ?int $viewerUserId = null, int $limit = 25)` keeps
its signature; when `$viewerUserId` is set and the hide-from-lists pref is true,
the search query restricts to `tw_disclosure = no_tw`.

`listStories` / `StoryQueryFilterDto::$noTwOnly` stay as today (library checkbox
and any explicit caller). The preference does **not** auto-merge into
`listStories`.

### 3.2 Services

Private **`StoryPreferenceService`** owns:

- reading the two booleans through `SettingsPublicApi`, with **once-per-request
  memoization** of the current user’s values (so Blade can call repeatedly
  without N Settings lookups);
- merging hide-from-lists into Story-owned discovery paths only:
  - library `searchStories` / `StoryFilterAndPagination` (OR with request
    `no_tw_only` checkbox — checkbox stays independent);
  - `getRandomStories`;
  - `StorySearchService` / public `searchStories` when viewer known.

### 3.3 Policy / authorization

No new policy. Settings page auth unchanged; both roles can toggle. Guests
never persist prefs and always see default behaviour (service returns `false`).

### 3.4 Events and listeners

None.

### 3.5 Routes, controllers, form requests

No new routes. Settings save uses existing Settings endpoints (PUT, not PATCH).
Library / random / search paths resolve prefs only for filtering; show view
resolves hide-display for its TW block.

## 4. Frontend architecture

Blade-first; no new Alpine store.

**Hide TW display (decisions #10 + #12):**

- `<x-story::trigger-warnings>` owns the gate: it asks
  `StoryPreferenceService` (request-memoized) whether to render, and renders
  nothing when the pref is on. Every card surface that already uses the
  component (library, dashboard, keep-reading, profile, ReadList, …) inherits
  the behaviour with no prop-drill.
- Story **show** has a parallel gate on its dedicated TW block (same service).
- Author create/edit TW form and StoryRef admin catalog stay ungated
  (configuration UIs, not reader spoilers).

**Settings UI:** standard Settings tab rendering; French keys under
`story::settings.*` for tab « Histoires », section, and the two parameters
(working copy from the functional spec; polish allowed in BUILD).

## 5. Deptrac

**No new edges.** Story already depends on `SettingsPublic`. Search already
depends on `StoryPublic` only. ReadList unchanged. Dashboard stays on Story’s
Blade component.

## 6. Testing strategy

**Integration (default):**

- Settings: tab/params registered; authenticated user can toggle both; guests
  see defaults.
- Library: pref on → only `no_tw` stories; checkbox still works alone and
  combined; pref does not rewrite the checkbox state.
- Random / dashboard discover: pref on → only `no_tw`.
- Search: pref on + viewer → only `no_tw` in story results.
- ReadList: pref on → listing **unchanged** (still shows TW stories); TW chrome
  still hidden via the shared component when hide-display is on.
- Hide display: with pref on, library/show/ReadList HTML has no TW badges /
  `no_tw` / unspoiled labels; with pref off, current UI unchanged.
- Direct story URL still loads when hide-from-lists is on.
- Profile stories / keep-reading: hide-from-lists does **not** filter; hide
  display still applies via the component.

**Unit:** only if preference memo helpers are worth isolating; otherwise keep
logic under feature tests.

**Vitest:** none expected.

**VERIFY:** settings tab visibility, toggle persistence, and visual absence of
TW chrome on a multi-card library page.

## 7. Tradeoffs locked

| # | Question | Options considered | Chosen | Why |
|---|----------|--------------------|--------|-----|
| 1 | Where hide-from-lists is applied | A: Story owns discovery paths (`listStories` does not auto-apply). B: every consumer reads Settings and passes `noTwOnly` | A (narrowed by #13) | Filter semantics already live in Story; Search needs no Settings edge |
| 2 | How hide-TW-display is enforced | A: Blade gate on shared TW UI + show block. B: strip TW from DTOs/view models | A | TW UI already centralized; Blade-first |
| 3 | Per-request resolution of hide-display | Prop-drill from each surface vs component reads memoized service | Component + once-per-request memo (+ show gate) | Zero consumer wiring; avoids N Settings lookups on library grids |
| 4 | Hide-from-lists on ReadList | Include ReadList as discovery vs exclude | Exclude | Own pile is intentional; filtering empties others’ read lists |

## 8. File layout

New (illustrative tree — exact names may vary in PLAN):

```
app/Domains/Story/
  Public/
    Providers/StoryServiceProvider.php  # TAB_STORIES + registerSettings
  Private/
    Services/StoryPreferenceService.php
    Resources/lang/fr/settings.php      # or extend existing story lang
```

Existing Story Blade (`trigger-warnings`, show TW block) and discovery query
paths gain the gates/filters. No ReadList file changes. No new JS.

## 9. Risks acknowledged

| Risk | Revisit when |
|------|----------------|
| A new discovery surface outside Story forgets to apply the list filter | Adding another discovery consumer that does not go through library / random / `searchStories` |
| Users expect ReadList to honour hide-from-lists | Support noise / backlog follow-up |
| Author form still shows TW while hide-display is on | If users treat the edit form as a spoiler surface (currently assumed out of scope) |
