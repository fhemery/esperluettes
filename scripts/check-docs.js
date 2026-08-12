#!/usr/bin/env node
/*
  Documentation consistency checks.

  Run standalone or as the `docs` step of `pnpm run gate`.

  Rule 1 — code docs must not depend on planning docs.
    A README.md or AGENTS.md under app/Domains may not reference
    docs/Feature_Planning.
    Planning documents are working memory for an in-flight task: they get
    renamed, split and deleted when a task wraps. A domain's documentation has
    to stand on its own, so whatever matters must be folded into it rather than
    linked. The dependency runs one way only — planning may link to code docs,
    never the reverse.

  Rule 2 — ADRs must not depend on planning docs.
    Any markdown under docs/adr/ may not reference Feature_Planning (active
    task folders or _done/). ADRs are durable decisions; planning paths
    disappear on WRAP. Fold what matters into the ADR itself — see the
    write-adr skill.

  Rule 3 — no broken relative links in any tracked markdown under docs/ and
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
const CLAUDE_SHIM = '@AGENTS.md\n';

export function listDomainNames(rootDir) {
  const domainsDir = path.join(rootDir, 'app', 'Domains');
  let entries;
  try {
    entries = fs.readdirSync(domainsDir, { withFileTypes: true });
  } catch {
    return [];
  }
  return entries.filter((e) => e.isDirectory()).map((e) => e.name).sort();
}

export function checkDomainTrio(rootDir, domainName) {
  const domainDir = path.join(rootDir, 'app', 'Domains', domainName);
  const failures = [];
  const required = ['README.md', 'AGENTS.md', 'CLAUDE.md'];

  for (const file of required) {
    if (!fs.existsSync(path.join(domainDir, file))) {
      failures.push(`app/Domains/${domainName}: missing ${file}`);
    }
  }

  const claudePath = path.join(domainDir, 'CLAUDE.md');
  if (fs.existsSync(claudePath)) {
    const content = fs.readFileSync(claudePath);
    if (!content.equals(Buffer.from(CLAUDE_SHIM))) {
      failures.push(`app/Domains/${domainName}: CLAUDE.md is not the @AGENTS.md shim`);
    }
  }

  return failures;
}

export function parseRegistryDomainPaths(agentsMdContent) {
  const lines = agentsMdContent.split(/\r?\n/);
  const startIdx = lines.findIndex((line) => line.trim() === '## Domain Registry');
  if (startIdx === -1) {
    return [];
  }

  const paths = [];
  for (let i = startIdx + 1; i < lines.length; i++) {
    const line = lines[i];
    if (line.startsWith('## ')) {
      break;
    }
    if (!line.startsWith('|')) {
      continue;
    }
    const cells = line.split('|').map((c) => c.trim()).filter((c) => c !== '');
    if (cells.length < 2 || /^:?-+:?$/.test(cells[0])) {
      continue;
    }
    const match = cells[1].match(/`(app\/Domains\/[^`]+)`/);
    if (match) {
      paths.push(match[1]);
    }
  }
  return paths;
}

export function checkRegistrySync(domainNames, registryPaths) {
  const failures = [];
  const registrySet = new Set(registryPaths);
  const domainSet = new Set(domainNames);

  for (const name of domainNames) {
    const expected = `app/Domains/${name}`;
    if (!registrySet.has(expected)) {
      failures.push(`app/Domains/${name}: not listed in Domain Registry`);
    }
  }

  for (const regPath of registryPaths) {
    const match = regPath.match(/^app\/Domains\/(.+)$/);
    if (!match) {
      continue;
    }
    if (!domainSet.has(match[1])) {
      failures.push(`${regPath}: listed in Domain Registry but no directory on disk`);
    }
  }

  return failures;
}

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

function linesReferencingPlanning(file) {
  const failures = [];
  const rel = relative(file);
  const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);
  lines.forEach((line, i) => {
    if (line.includes(PLANNING_DIR)) {
      failures.push(`${rel}:${i + 1} references ${PLANNING_DIR} — fold the content in instead`);
    }
  });
  return failures;
}

function checkDomainPlanningReferences(files) {
  const failures = [];
  for (const file of files) {
    const rel = relative(file);
    if (!rel.startsWith('app/Domains/')) continue;
    const base = path.basename(file);
    if (base !== 'README.md' && base !== 'AGENTS.md') continue;
    failures.push(...linesReferencingPlanning(file));
  }
  return failures;
}

function checkAdrPlanningReferences(files) {
  const failures = [];
  for (const file of files) {
    const rel = relative(file);
    if (!rel.startsWith('docs/adr/')) continue;
    failures.push(...linesReferencingPlanning(file));
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

function checkAllDomainTrios(rootDir) {
  const failures = [];
  for (const name of listDomainNames(rootDir)) {
    failures.push(...checkDomainTrio(rootDir, name));
  }
  return failures;
}

function checkDomainRegistrySync(rootDir) {
  const agentsPath = path.join(rootDir, 'AGENTS.md');
  const content = fs.readFileSync(agentsPath, 'utf8');
  const registryPaths = parseRegistryDomainPaths(content);
  const domainNames = listDomainNames(rootDir);
  return checkRegistrySync(domainNames, registryPaths);
}

function main() {
  const files = SEARCH_ROOTS.flatMap((r) => walk(path.join(root, r)));

  const checks = [
    { label: 'domain docs do not reference Feature_Planning', failures: checkDomainPlanningReferences(files) },
    { label: 'ADRs do not reference Feature_Planning', failures: checkAdrPlanningReferences(files) },
    { label: 'relative markdown links resolve', failures: checkRelativeLinks(files) },
    {
      label: 'every domain has README.md, AGENTS.md, and CLAUDE.md shim',
      failures: checkAllDomainTrios(root),
    },
    {
      label: 'Domain Registry matches app/Domains on disk',
      failures: checkDomainRegistrySync(root),
    },
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

const isDirectRun = process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url);
if (isDirectRun) {
  main();
}
