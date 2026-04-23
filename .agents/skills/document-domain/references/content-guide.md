# Content Guide — README.md vs CLAUDE.md

## Core principle

**README.md** = human understanding. Written for a developer who just joined the project.  
**CLAUDE.md** = agent instructions. Written for an AI that will implement features in this domain.  

The test for CLAUDE.md: *would an agent cause a bug or architectural violation by not knowing this, and can it NOT be derived by reading a single file?* If both answers are yes, it belongs in CLAUDE.md. Otherwise, leave it out.

---

## README.md

### Always include

**Purpose and scope** — One paragraph: what does this domain own, what problems does it solve, what is explicitly out of scope.

**Key concepts** — Explain domain-specific mechanics in prose. These are things a developer needs to understand before touching the code. Examples:
- The chapter credit system (who earns, who spends, what revokes)
- The three trigger-warning disclosure modes and their implications
- The visibility model and what each level means
- The collaborator/author distinction

**Architecture decisions with rationale** — Not just *what* was decided, but *why*. Examples:
- "Slugs are stored with a `-{id}` suffix to guarantee global uniqueness without a separate lookup"
- "User deactivation soft-deletes stories (recoverable); user deletion hard-deletes them (permanent)"
- "Sparse sort ordering (increments of 100) allows inserting chapters without reordering everything"

**Cross-domain delegation map** — What this domain intentionally outsources to others, and why. This prevents developers from accidentally re-implementing something that already exists.

**Link to feature planning doc** — If `docs/Feature_Planning/<Domain>.md` exists, link it. Note if the spec may be outdated.

### Include when relevant

**Rendering / UI architecture** — For domains with non-trivial front-end logic (lazy loading, fragment loading, Alpine.js state), explain the approach.

**Plugin/registry pattern** — If the domain uses a registry that other domains hook into (Calendar activity types, Comment policies, Moderation topics, Notification content types), explain how to register a new plugin.

**Admin panel notes** — If the Filament resources live in `app/Domains/Admin/` rather than inside the domain itself, call this out.

### Never include in README.md

- Agent instructions ("when implementing X, remember to...")
- Lists of model fields or table columns — developers read the migration
- Route lists — developers read routes.php
- Anything that would go stale if a method is renamed

---

## CLAUDE.md

### Always include

**README pointer**
```markdown
- README: [app/Domains/<Domain>/README.md](README.md)
```

**Public API entry point(s)** — The class(es) other domains must call. State what they expose at a high level. This prevents agents from bypassing the Public API and reaching into Private code.

```markdown
- Public API: [StoryPublicApi](Public/Api/StoryPublicApi.php) — story listing, access control, search
```

**Events catalogue** — What events this domain emits and under what conditions. This is otherwise spread across services, observers, and listeners, requiring many file reads to reconstruct.

```markdown
## Events emitted
- `StoryCreated` — on story creation
- `StoryVisibilityChanged(oldVisibility, newVisibility)` — when visibility changes
- `ChapterPublished` — when chapter transitions to published; also updates `story.last_chapter_published_at`
```

**Non-obvious invariants** — Rules that span multiple files and would cause silent bugs if missed. Each entry should name *why* it exists, not just what it is.

Examples of good invariants:
- "Slug stored as `{base}-{id}` — use `SlugWithId::build()` to generate and `SlugWithId::extractId()` to parse. Breaking this corrupts all story/chapter URLs."
- "`tw_disclosure != 'listed'` → TW IDs must be cleared on save, not just ignored. The model does not enforce this automatically."
- "`cover_data` must contain a genre slug that is in the story's selected genres. Validated in the service layer, not the model."
- "Soft delete stories on user deactivation (`UserDeactivated`), hard delete on user deletion (`UserDeleted`). These are separate listeners, not a single handler."

**Cross-domain listeners** — What events from other domains this domain reacts to, and what action it takes. This is otherwise only discoverable by reading the ServiceProvider and each listener file.

```markdown
## Listens to
- `Auth::UserRegistered` → grants 5 initial chapter credits
- `Auth::UserDeleted` → hard-deletes all stories and removes credits row
- `Comment::CommentPosted` → grants 1 credit (root comment on published chapter, once per user/chapter)
```

### Include when relevant

**Registry integration** — If this domain registers into another domain's registry (e.g. moderation topics, notification types, comment policies, calendar activity types), document what it registers and where. Agents adding new registrations need to know the pattern.

**No FK constraint note** — If the domain intentionally avoids a FK to `users` (per architecture rules), state it explicitly with the reason.

### Never include in CLAUDE.md

| What | Why |
|------|-----|
| Model field lists | Agent reads the model |
| Table names | Already in root CLAUDE.md Domain Registry |
| Route lists | Agent reads routes.php |
| Service method signatures | Agent reads the service |
| Anything in a single readable file | Creates drift when the file changes |
| "human" explanations of concepts | Those go in README.md |

---

## Worked example — News domain

### README.md would contain
- Purpose: news articles with publish/unpublish lifecycle, pinned carousel for home page
- Key concept: the `status` field drives visibility; `published_at` is set on the publish transition, not on creation
- Architecture: Admin Filament resources live in `app/Domains/Admin/`, not inside the News domain itself
- Cross-domain: Home domain uses `NewsPublicApi::getPinnedForCarousel()` to render the carousel

### CLAUDE.md would contain
- README pointer
- Public API: `NewsPublicApi` — `getPinnedForCarousel()` for Home domain
- Events: `NewsPublished`, `NewsUnpublished`, `NewsUpdated`, `NewsDeleted`
- Invariants:
  - Slug auto-generated from title on creation only; `doNotGenerateSlugsOnUpdate` is intentional
  - `created_by` is nullable; user deletion must not cascade — no FK constraint to `users`

### README.md would NOT contain
- List of `news` table columns
- The `NewsRequest` validation rules

### CLAUDE.md would NOT contain
- Explanation of what "published" means to a user
- The fact that `is_pinned` is a boolean

---

## Calibration checklist

Before finalising either file, run through these:

**README.md**
- [ ] Would a new developer understand the domain without reading any code?
- [ ] Are all non-trivial business mechanics explained?
- [ ] Are architecture decisions justified (not just described)?
- [ ] Is the cross-domain map complete?

**CLAUDE.md**
- [ ] Does every entry require reading *multiple files* to discover independently?
- [ ] Is the Public API clearly identified?
- [ ] Are all emitted events listed?
- [ ] Are all cross-domain listeners listed?
- [ ] Is nothing derivable from a single file?
- [ ] Is there any drift risk? (If so, remove the entry)
