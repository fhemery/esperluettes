# Story preferences — functional specification

> REFINE output. Describes **what** the feature does, never **how** it is built.
> Every statement here is either something the user confirmed or a stated
> assumption. No invented requirements.

## 1. Overview

Authenticated readers get two story-related preferences under a new settings tab
**Histoires**: one hides every on-screen trigger-warning cue (to avoid spoilers),
and one removes from discovery lists any story that is not marked “sans
avertissement” — the same rule as the library’s existing filter. Guests keep the
defaults (warnings visible, lists unfiltered).

## 2. Vocabulary

| Term | Meaning |
|------|---------|
| Avertissement (TW) | Trigger warning attached to a story via the existing disclosure model |
| Divulgation TW | Story field: `listed` (names shown), `no_tw` (aucun avertissement), `unspoiled` (avertissements non dévoilés) |
| Préférence « masquer les avertissements » | Reader setting: no TW UI anywhere when on |
| Préférence « masquer les histoires avec avertissement » | Reader setting: discovery lists only show stories with disclosure `no_tw` |
| Listes de découverte | Library `/stories`, dashboard « à découvrir », story search results (not ReadList) |
| Onglet Histoires | New settings tab for story-reading preferences |

## 3. Roles & visibility

| Role | Can see | Can do |
|------|---------|--------|
| Guest | Public story lists and pages with **defaults** (TW shown; no list filtering from prefs) | Cannot open Settings; no persisted prefs |
| `user` (non-confirmed) | Same UI as confirmed for these prefs | Change both preferences |
| `user-confirmed` | Same | Change both preferences |
| Author / co-author | No special exemption on discovery lists | Own authored stories still appear on own profile / keep-writing / author pickers; direct URLs still work |
| Moderator | Unchanged admin/moderation story tools | No override of another user’s prefs |
| Admin | Unchanged | No override of another user’s prefs |

Preferences are private to the account that set them; they never appear on profiles.

## 4. Functional requirements

### 4.1 Configure story preferences

1. An authenticated `user` or `user-confirmed` opens Settings.
2. They open the **Histoires** tab.
3. They can toggle:
   - **Masquer les avertissements** (default off) — description: hide all trigger-warning presence in the UI so names and labels cannot spoil.
   - **Masquer les histoires avec avertissement** (default off) — description: hide from discovery lists every story that is not “sans avertissement” (same meaning as the library checkbox).
4. Saving persists non-default values; turning back to default behaves like other settings.
5. Changing a preference applies immediately on subsequent page loads (no separate “apply” step beyond normal settings save UX).

### 4.2 Hide trigger-warning display

When **Masquer les avertissements** is on for the current user:

1. Story page: no TW badges, no `no_tw` label, no “avertissements non dévoilés” — no TW presence at all.
2. Story cards (library and elsewhere that show the TW component): same — no TW UI.
3. ReadList cards: same.
4. Direct URL / keep-reading / any other surface that currently shows TW: same rule — nowhere.

When the preference is off (default), current TW UI is unchanged (including author `unspoiled` / `no_tw` / listed behaviour).

Guests always see the current (default) TW UI.

### 4.3 Hide stories with TW from discovery lists

When **Masquer les histoires avec avertissement** is on for the current user:

1. **Library** `/stories` results omit every story whose disclosure is not `no_tw` (i.e. omit `listed` and `unspoiled`), matching today’s “Histoires sans avertissement” filter semantics.
2. **Dashboard « à découvrir »** (random stories) applies the same rule.
3. **Search** story results apply the same rule.

Does **not** apply to:

- **ReadList** (own pile or viewing another’s — leaving TW stories on a list is intentional; filtering would empty others’ lists)
- Opening a story by direct URL
- Keep-reading
- The reader’s own authored stories on their profile / author tools / Calendar author pickers (e.g. Jardino)
- Admin / moderation story search
- Other people’s profile story tabs (not a discovery list in this version)

When the preference is off, list behaviour is unchanged (library checkbox still works as a one-shot filter — see §4.4).

Guests never get this filter from a preference.

### 4.4 Library checkbox independence

The library’s existing “Histoires sans avertissement” checkbox remains a **one-shot request filter**, independent of the preference. It does not load or save the setting. A user may use either, both, or neither; when both would filter, the effective result is the same rule (`no_tw` only).

### 4.5 Edge paths

- Preference on + story has TW + user has a direct link → page opens; if hide-display is also on, no TW UI; if hide-display is off, TW UI shows as today.
- Preference on + user’s own TW story in library browse → hidden from that discovery list (no author exemption).
- User never touches settings → both prefs off; behaviour identical to today.
- Non-confirmed and confirmed users → identical access to these prefs.

## 5. Lifecycle

- Preferences are per-user settings rows (existing Settings storage). No new story-owned rows.
- User deactivated: settings remain; behaviour follows whatever Settings already does for deactivated accounts accessing the app.
- User deleted: settings cascade/cleanup follows existing Settings behaviour — no extra feature-specific cascade.
- Story TW disclosure changes: list filter and display react to current disclosure on next load; no backfill.
- No notifications or events when a preference changes.

## 6. Cross-cutting concerns

| Concern | Decision |
|---------|----------|
| Roles | Both `user` and `user-confirmed`; guests get defaults only |
| Visibility / privacy | Prefs are account-private; not on profile |
| Settings | New tab **Histoires**; two booleans, default off, `hide-*` polarity |
| Notifications | N/A — none |
| Domain events | N/A — none |
| Statistics | N/A — none |
| Moderation | N/A — does not change moderation tools or reporting |
| Lifecycle / cascade | Existing Settings user lifecycle; no feature-owned table |
| Media | N/A |
| Search | Story search results respect the hide-from-lists preference |
| i18n | French-only strings in lang files (tab, param names, descriptions) |
| Mobile | Same settings + list behaviour; no special mobile flow |
| Accessibility | Toggles follow existing Settings a11y patterns; removing TW UI must not leave empty labelled regions |

## 7. Decisions confirmed

| # | Question | Decision |
|---|----------|----------|
| 1 | Meaning of “has TW” for hide-from-lists | Same as library: only `no_tw` remains (`listed` and `unspoiled` hidden) |
| 2 | Surfaces for hide-from-lists | Library, dashboard discover, story search only — **not** ReadList (see DECISIONS #13). Direct URL, keep-reading, authored / pickers, admin, profile tabs unchanged |
| 3 | Hide TW display scope | No TW presence anywhere (names, `no_tw`, unspoiled) on every surface that currently shows them |
| 4 | Settings placement | New settings tab **Histoires** |
| 5 | Library checkbox vs preference | Independent one-shot filter; not synced; not removed |
| 6 | Who can set prefs | Both `user` and `user-confirmed` |
| 7 | Defaults | Both off |
| 8 | Notifications / events / stats / moderation | None for this feature |

## 8. Out of scope

- Guest-persisted preferences (cookies or otherwise)
- Syncing or removing the library “Histoires sans avertissement” checkbox
- Filtering ReadList, keep-reading, others’ profile story tabs, author pickers, or admin lists
- Blocking direct URL access to TW stories
- Author exemption on discovery lists
- Changing the author-facing TW disclosure model or catalog
- Mature/audience gating (separate from TW)
- Notifications, domain events, statistics, or moderation flows for these prefs
- Per-TW-id “exclude these warnings” preference (library’s per-id exclude stays as today, not promoted to a setting)

## 9. Open questions

None blocking.

Non-blocking (copy polish during BUILD / DESIGN): exact French `descriptionKey` sentences for the two parameters — working titles above may be tightened without changing behaviour.
