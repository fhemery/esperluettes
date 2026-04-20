---
name: fix-deptrac
description: Analyze deptrac violations after a failed deptrac check and decide whether to fix the code or update deptrac.yaml. Use when deptrac reports architectural violations.
---

# Fix Deptrac Violations

The deptrac output is already in your context from the hook that triggered this skill. Work through each violation systematically.

---

## Architecture rules for this project

Layers follow the naming convention `<Domain>Public`, `<Domain>Private`, `<Domain>Tests`:

- **Public layer** — the domain's external API. Other domains MAY depend on it.
- **Private layer** — internal implementation. Only code within the same domain may use it. No cross-domain access allowed, ever.
- **Tests layer** — test code. May only be accessed by `TestHelpers`.
- **Shared** — accessible by all domains without restriction.
- **TestHelpers** — accessible by all test layers.

Cross-domain rules:
- `DomainA → DomainBPublic` is potentially valid (needs ruleset entry)
- `DomainA → DomainBPrivate` is **never valid** — fix the code
- `DomainA → DomainBTests` is **never valid** — fix the code

---

## Step 1 — Read the violation output

Deptrac outputs violations like:

```
DomainAPrivate must not depend on DomainBPublic
  app/Domains/DomainA/Private/SomeService.php:42
```

For each violation, note:
- The **source layer** (where the dependency originates)
- The **target layer** (what it's trying to use)
- The **file and line** where the dependency is

---

## Step 2 — Find the offending code

Open the file at the indicated line. Identify:
- What class/namespace is being imported or used
- Which domain it belongs to

---

## Step 3 — Decide: fix code or update deptrac.yaml

**Fix the code if any of these is true:**
- The target layer is Private (`DomainBPrivate`) — cross-domain Private access is never allowed
- The target layer is a Tests layer — tests are not a shared API
- The dependency is circular (A → B → A)
- The usage was accidental (e.g. wrong import auto-completed)
- There is a better way to achieve the same result using an existing Public API

**Update `deptrac.yaml` if all of these are true:**
- The target layer is Public (`DomainBPublic`)
- The dependency is intentional and architecturally justified (e.g. Calendar sending a Notification)
- The calling layer is a Private or Public layer (not Tests)
- No existing Public API in Shared or another domain already covers this need

---

## Step 4a — Fix the code

Options, in order of preference:
1. Use an existing Public API from `Shared` instead
2. Use an existing `<DomainB>Public` API if there is one that covers the need
3. Move the shared logic to `Shared` domain
4. Inject via event/listener pattern to decouple the domains

---

## Step 4b — Update `deptrac.yaml`

Find the `ruleset:` section. Locate the entry for the source layer (e.g. `CalendarPrivate`). Add the target layer to its allowed list.

Example — adding `NotificationPublic` as a dependency of `CalendarPrivate`:

```yaml
ruleset:
  CalendarPrivate:
    - Shared
    - CalendarPublic
    - NotificationPublic   # ← add this line
```

If the source layer has no ruleset entry yet, add it:

```yaml
  DiscordPrivate:
    - Shared
    - NotificationPublic
```

Keep the list sorted alphabetically within each entry for readability.

---

## Step 5 — Verify

Run deptrac to confirm the violation is resolved:

```
./vendor/bin/sail composer deptrac
```

If more violations appear, repeat from Step 1.

---

## Step 6 — Check domain coverage warning

If the hook also reported "Domains not defined in deptrac.yaml", and the violation involves one of those uncovered domains, add the missing layers to `deptrac.yaml` first. Follow the existing layer pattern:

```yaml
- name: DiscordPublic
  collectors:
    - type: directory
      value: 'app/Domains/Discord/Public/'

- name: DiscordPrivate
  collectors:
    - type: bool
      must_not:
        - type: directory
          value: app/Domains/Discord/Tests/
      must:
        - type: directory
          value: app/Domains/Discord/
    
- name: DiscordTests
  collectors:
    - type: directory
      value: 'app/Domains/Discord/Tests/'
```

Then add the new layers to the `ruleset:` section with their allowed dependencies.
