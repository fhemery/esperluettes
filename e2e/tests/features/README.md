# Feature specs — temporary by design

Specs written to verify **one feature in flight** live here, named after the
task slug (`quote-book.spec.ts`). They exist to answer "does this work in a
browser", not to be kept.

At WRAP, every spec in this folder is either:

- **deleted** — the usual outcome. It did its job at VERIFY; the behaviour it
  checked is now covered by PHP tests, or is not worth a permanent browser
  test. Say so in the task summary.
- **promoted** to `../core/` — only if it guards something used across the app
  and breakable from anywhere. Promoting costs everyone ~seconds per run
  forever, so it needs a reason written into the spec's header comment.

A spec left here after WRAP is a bug in the process, not a feature.

## Why the split exists

A suite that only grows stops being run. `core/` is the permanent net —
small, curated, everything in it earns its place. This folder is the workbench.

Before writing anything here, check the rule in
[`.agents/skills/verify-visually/SKILL.md`](../../../.agents/skills/verify-visually/SKILL.md):
if a PHP integration test can assert it, it does not belong in a browser.
