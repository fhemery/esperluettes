#!/usr/bin/env node
/*
  Documentation consistency checks.

  Run standalone or as the `docs` step of `npm run gate`.

  Rule 1 — code docs must not depend on planning docs.
    A README.md or AGENTS.md under app/Domains may not reference
    docs/Feature_Planning.
    Planning documents are working memory for an in-flight task: they get
    renamed, split and deleted when a task wraps. A domain's documentation has
    to stand on its own, so whatever matters must be folded into it rather than
    linked. The dependency runs one way only — planning may link to code docs,
    never the reverse.

  Rule 2 — no broken relative links in any tracked markdown under docs/ and
    app/Domains/.
*/

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { makeLog } from './utils.js';

const log = makeLog('check-docs');
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

const SEARCH_ROOTS = ['docs', 'app/Domains'];
const PLANNING_DIR = 'Feature_Planning';

function walk(dir, out = []) {
  let entries;
  try {
    entries = fs.readdirSync(dir, { withFileTypes: true });
  } catch {
    return out;
  }
  for (const e of entries) {
    const full = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'node_modules' || e.name === 'vendor') continue;
      walk(full, out);
    } else if (e.name.endsWith('.md')) {
      out.push(full);
    }
  }
  return out;
}

function relative(p) {
  return path.relative(root, p).split(path.sep).join('/');
}

function checkPlanningReferences(files) {
  const failures = [];
  for (const file of files) {
    const rel = relative(file);
    if (!rel.startsWith('app/Domains/')) continue;
    const base = path.basename(file);
    if (base !== 'README.md' && base !== 'AGENTS.md') continue;

    const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);
    lines.forEach((line, i) => {
      if (line.includes(PLANNING_DIR)) {
        failures.push(`${rel}:${i + 1} references ${PLANNING_DIR} — fold the content in instead`);
      }
    });
  }
  return failures;
}

// Blank out fenced blocks and inline code spans, preserving offsets and
// newlines so reported line numbers stay accurate. A link inside backticks is
// an example, not a link.
function stripCode(text) {
  return text
    .replace(/```[\s\S]*?```/g, (m) => m.replace(/[^\n]/g, ' '))
    .replace(/`[^`\n]*`/g, (m) => ' '.repeat(m.length));
}

function checkRelativeLinks(files) {
  const failures = [];
  const linkRe = /\[[^\]]*\]\(([^)\s]+)\)/g;
  for (const file of files) {
    const text = stripCode(fs.readFileSync(file, 'utf8'));
    for (const m of text.matchAll(linkRe)) {
      const target = m[1];
      if (/^(https?:|mailto:|#|\/)/.test(target)) continue;
      const clean = decodeURIComponent(target.split('#')[0]);
      if (!clean) continue;
      const resolved = path.resolve(path.dirname(file), clean);
      if (!fs.existsSync(resolved)) {
        const line = text.slice(0, m.index).split('\n').length;
        failures.push(`${relative(file)}:${line} broken link → ${target}`);
      }
    }
  }
  return failures;
}

function main() {
  const files = SEARCH_ROOTS.flatMap((r) => walk(path.join(root, r)));

  const checks = [
    { label: 'domain docs do not reference Feature_Planning', failures: checkPlanningReferences(files) },
    { label: 'relative markdown links resolve', failures: checkRelativeLinks(files) },
  ];

  let failed = false;
  for (const check of checks) {
    if (check.failures.length === 0) {
      log.ok(check.label);
      continue;
    }
    failed = true;
    log.warn(`${check.label} — ${check.failures.length} problem(s)`);
    for (const f of check.failures) console.error(`    ${f}`);
  }

  if (failed) {
    console.error('\n[check-docs] FAILED');
    process.exit(1);
  }
  log(`checked ${files.length} markdown files`);
}

main();
