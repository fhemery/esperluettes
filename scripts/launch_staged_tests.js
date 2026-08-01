#!/usr/bin/env node
/*
  Launch tests only for Domains impacted by modified (non-staged) changes and their dependent domains (via deptrac).

  Approach (as specified):
  - For each file that is modified (not staged), check its domain.
    * If it belongs to docs/ or any path segment starts with a '.', ignore it.
    * Else, if it starts with app/Domains/XXX/, extract the domain XXX.
    * In all other cases, run all tests.
  - Parse deptrac.yaml and build a reverse domain dependency map from the ruleset, removing Public/Private/Tests suffixes.
    Example: AuthPublic: [Shared, AuthPrivate, EventsPublic] -> Shared -> Auth, Events -> Auth
  - Compute the transitive closure of this reverse map (e.g., if Shared -> Auth and Auth -> Comment, then Shared -> [Auth, Comment]).
  - Take all touched domains, expand through the closure, and compute which tests to run.

  Output:
  - Print the Domains impacted by the modified files
  - Print the list of impacts from deptrac (reverse closure map)
*/

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import yaml from 'js-yaml';
import { makeLog, fileExists, runCmd, determineRunner } from './utils.js';

const excludeFoldersOrFiles = [
  '.github',
  '.gitignore',
  'docs/',
  'scripts/',
  'deptrac.yaml',
  '.windsurf',
  '.husky',
  '.vscode',
  '.claude/',
  'AGENTS.md',
  '.agents/',
  '.cursor',
  'e2e/',
  'playwright.config'
]

const log = makeLog('staged-tests');

// runCmd imported from utils

function getModifiedFiles() {
  // Collect modified files from staged, unstaged, and untracked sources
  // Staged changes
  const stagedRes = spawnSync('git', ['diff', '--name-only', '--cached'], { encoding: 'utf8' });
  const staged = stagedRes.status === 0 ? (stagedRes.stdout || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean) : [];

  // Unstaged modified files
  const diffRes = spawnSync('git', ['diff', '--name-only'], { encoding: 'utf8' });
  const unstaged = diffRes.status === 0 ? (diffRes.stdout || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean) : [];

  // Untracked files
  const untrackedRes = spawnSync('git', ['ls-files', '--others', '--exclude-standard'], { encoding: 'utf8' });
  const untracked = untrackedRes.status === 0 ? (untrackedRes.stdout || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean) : [];

  const set = new Set([...staged, ...unstaged, ...untracked]);
  const files = [...set];
  // Debug log to aid diagnosis (kept concise)
  log(`Detected modified files: ${files.join(', ')}`);

  return files;
}

function getCurrentBranch() {
  const res = spawnSync('git', ['rev-parse', '--abbrev-ref', 'HEAD'], { encoding: 'utf8' });
  if (res.status !== 0) return undefined;
  return (res.stdout || '').trim();
}

function readDeptracConfig() {
  const depPath = path.resolve(process.cwd(), 'deptrac.yaml');
  if (!fileExists(depPath)) return null;
  try {
    const raw = fs.readFileSync(depPath, 'utf8');
    return yaml.load(raw);
  } catch (e) {
    log(`Failed to parse deptrac.yaml: ${e.message}`);
    return null;
  }
}

function deriveDomainFromPath(relPath) {
  // expects forward slashes
  const m = relPath.match(/^app\/Domains\/([^/]+)\//);
  return m ? m[1] : null;
}

function extractDomainsFromFiles(files) {
  const domains = new Set();
  let runAll = false;
  for (const f of files) {
    const rel = f.replace(/\\/g, '/');
    if (!rel) continue;
    // ignore docs and hidden folders
    if (excludeFoldersOrFiles.some(ex => rel.startsWith(ex))) continue;

    const domain = deriveDomainFromPath(rel);
    if (domain) {
      domains.add(domain);
      continue;
    }

    // Any other case => run all tests
    runAll = true;
  }
  return { domains: [...domains], runAll };
}

function stripSuffixToDomain(layerName) {
  if (!layerName) return null;
  const m = layerName.match(/^(.*?)(Public|Private|Tests)$/);
  return m ? m[1] : layerName;
}

function buildDomainReverseMap(deptrac) {
  const ruleset = deptrac?.deptrac?.ruleset || {};
  const reverse = new Map(); // domain -> Set(of dependent domains)

  function ensure(k) { if (!reverse.has(k)) reverse.set(k, new Set()); }

  // Collect all domain keys from LHS and RHS
  for (const [lhs, deps] of Object.entries(ruleset)) {
    ensure(stripSuffixToDomain(lhs));
    for (const d of deps) ensure(stripSuffixToDomain(d));
  }

  // Build reverse edges on domain level, ignoring self-loops
  for (const [lhs, deps] of Object.entries(ruleset)) {
    const lhsDom = stripSuffixToDomain(lhs);
    for (const d of deps) {
      const rhsDom = stripSuffixToDomain(d);
      if (!lhsDom || !rhsDom) continue;
      if (lhsDom === rhsDom) continue; // ignore self
      reverse.get(rhsDom).add(lhsDom);
    }
  }
  return reverse;
}

function computeTransitiveClosure(reverseMap) {
  // For each domain X, compute all domains that depend on X (including multi-hop)
  const closure = new Map(); // domain -> Set(of dependents)
  const domains = [...reverseMap.keys()];
  for (const d of domains) {
    const visited = new Set();
    const stack = [...(reverseMap.get(d) || [])];
    while (stack.length) {
      const cur = stack.pop();
      if (visited.has(cur)) continue;
      visited.add(cur);
      for (const up of reverseMap.get(cur) || []) stack.push(up);
    }
    closure.set(d, visited);
  }
  return closure;
}

function testsDirsForDomains(domains) {
  // Map domain -> app/Domains/<Domain>/Tests
  const dirs = [];
  for (const d of domains) {
    const candidate = `app/Domains/${d}/Tests`;
    if (fileExists(path.resolve(process.cwd(), candidate))) dirs.push(candidate);
  }
  return dirs;
}

function testsDirsForTestLayers(testLayers) {
  // Map <Domain>Tests -> app/Domains/<Domain>/Tests
  const dirs = [];
  for (const layer of testLayers) {
    const m = layer.match(/^(.*)Tests$/);
    if (m) {
      dirs.push(`app/Domains/${m[1]}/Tests`);
    }
  }
  return dirs;
}

// determineRunner imported from utils

/*
  Decide which PHP tests a set of changed files requires.

  Returns one of:
  - { mode: 'all',  reason }            -> run the full suite
  - { mode: 'none', reason }            -> nothing to run
  - { mode: 'dirs', dirs, reason }      -> run `artisan test <dirs...>`
*/
function resolvePhpTestPlan(files, logger = log, { maxDirs = 5 } = {}) {
  if (files.length === 0) {
    return { mode: 'all', reason: 'no changed files detected' };
  }

  const deptrac = readDeptracConfig();
  if (!deptrac) {
    return { mode: 'all', reason: 'deptrac.yaml not found or invalid' };
  }

  const { domains, runAll } = extractDomainsFromFiles(files);
  if (runAll) {
    return { mode: 'all', reason: 'changes include files outside app/Domains (and not ignored)' };
  }
  if (domains.length === 0) {
    return { mode: 'none', reason: 'changes contain only non-code files' };
  }

  // Build domain-level reverse dependency graph and its transitive closure
  const reverse = buildDomainReverseMap(deptrac);
  const closure = computeTransitiveClosure(reverse);

  // Compute impacted domains: directly modified + dependents via closure
  const impactedDomains = new Set(domains);
  for (const d of domains) for (const dep of closure.get(d) || []) impactedDomains.add(dep);

  logger(`Impacted domains (from changed files): ${[...new Set(domains)].join(', ')}`);
  logger(`Impacted domains (with deptrac dependents): ${[...impactedDomains].join(', ')}`);

  const dirs = Array.from(new Set(testsDirsForDomains([...impactedDomains])));
  if (dirs.length === 0 || dirs.length > maxDirs) {
    return { mode: 'all', reason: 'no specific test directories (or too many of them) resolved' };
  }
  return { mode: 'dirs', dirs, reason: `${dirs.length} impacted test directories` };
}

function runFullSuite() {
  const runner = determineRunner();
  return (runner === 'php')
    ? runCmd('php', ['artisan', 'test:parallel'])
    : runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test:parallel']);
}

function runStagedTests(skipBranchCheck = false) {
  // Skip entirely if not on main
  const branch = getCurrentBranch();
  if (branch && branch !== 'main' && !skipBranchCheck) {
    log(`Current branch is '${branch}'. Skipping staged tests (only run on 'main').`);
    return 0;
  }

  const plan = resolvePhpTestPlan(getModifiedFiles());

  if (plan.mode === 'all') {
    log(`Running full test suite (${plan.reason}).`);
    return runFullSuite() ? 0 : 1;
  }
  if (plan.mode === 'none') {
    log(`Skipping tests (${plan.reason}).`);
    return 0;
  }

  log(`Running tests for: ${plan.dirs.join(' ')}`);
  const runner = determineRunner();
  const ok = (runner === 'php')
    ? runCmd('php', ['artisan', 'test', ...plan.dirs])
    : runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', ...plan.dirs]);
  return ok ? 0 : 1;
}

// If executed directly, run and exit with returned code
const __filename = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === __filename) {
  const code = runStagedTests(true);
  process.exit(code);
}

export { runStagedTests, resolvePhpTestPlan, getModifiedFiles };
