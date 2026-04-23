---
name: document-domain
description: This skill should be used when the user asks to "document domain X", "update domain docs for X", "write the CLAUDE.md for X", "write the README for X domain", or "document the X module". Produces or updates both README.md and CLAUDE.md for a given domain under app/Domains/.
version: 0.1.0
---

# Document Domain

Produce or update two documentation files for a domain:
- **README.md** — human-readable domain overview for developers
- **CLAUDE.md** — agent instructions: only what cannot be derived from reading the code

Consult `references/content-guide.md` for the detailed breakdown of what belongs in each file and what to leave out.

## Step 1 — Identify the domain

Resolve the domain name and path. All domains live under `app/Domains/<DomainName>/`.

If the user provides a partial or lowercase name (e.g. "story", "read list"), match it against the Domain Registry in the root `CLAUDE.md`.

## Step 2 — Explore the domain

Read the following, in order. Stop reading a file once you have what you need — don't load everything blindly.

1. Existing `README.md` (if present) — note what's already covered and what's missing
2. Existing `CLAUDE.md` (if present) — note what's already there to avoid duplication
3. `Public/` folder — read the Public API class(es) and list of events; these are the domain's external contract
4. `Private/Services/` — skim service method signatures to understand business operations
5. `Private/Models/` — skim models for relationships and non-obvious casts/scopes
6. `Database/Migrations/` — identify owned tables (names only; don't copy field lists)
7. `Public/Providers/<Domain>ServiceProvider.php` — find cross-domain integrations: event subscriptions, registry registrations, notification type registrations
8. `Private/Listeners/` — identify what this domain reacts to from other domains
9. `Private/routes.php` — skim for route groups and notable access control patterns

Stop if the picture is already clear. The goal is understanding, not exhaustive reading.

## Step 3 — Write or update README.md

Target: `app/Domains/<Domain>/README.md`

Write for a **human developer** joining the project. See `references/content-guide.md` for the full content contract. In brief:

- Purpose and scope
- Key concepts that need explanation (business rules, non-trivial mechanics)
- Architecture decisions and their rationale
- Cross-domain delegation map (what this domain outsources and why)
- Link to feature planning doc if one exists under `docs/Feature_Planning/`

If a README already exists, preserve accurate content and extend or correct it rather than rewriting from scratch.

## Step 4 — Write or update CLAUDE.md

Target: `app/Domains/<Domain>/CLAUDE.md`

Write for an **AI agent** doing implementation work in this domain. See `references/content-guide.md` for the full content contract. In brief:

- README pointer
- Public API entry point(s) — what other domains must call, not implement directly
- Events catalogue — what to emit and when (spread across many files otherwise)
- Non-obvious invariants that span multiple files and would cause bugs if missed

If a CLAUDE.md already exists, merge and update rather than overwrite.

## Step 5 — Verify

After writing both files:

1. Check that CLAUDE.md contains **no information derivable from a single file** (model fields, route lists, service signatures). If found, remove it.
2. Check that README.md contains **no agent instructions** — only human-readable explanation.
3. Confirm the domain's entry in the root `CLAUDE.md` Domain Registry is accurate (path, responsibilities summary, tables). Update it if not.

## Additional Resources

- **`references/content-guide.md`** — detailed breakdown of what belongs in each file, with examples and anti-patterns
