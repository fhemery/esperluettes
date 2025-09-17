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
import { parse as dotenvParse } from 'dotenv';

const excludeFoldersOrFiles = [
  'docs/',
  'scripts/',
  'deptrac.yaml',
  '.windsurf',
  '.husky',
  '.vscode',
]


function log(msg) { process.stdout.write(`[staged-tests] ${msg}\n`); }

function fileExists(p) { try { return fs.existsSync(p); } catch { return false; } }

function runCmd(cmd, args, opts = {}) {
  const res = spawnSync(cmd, args, { stdio: 'inherit', shell: process.platform === 'win32', ...opts });
  return res.status === 0;
}

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

function determineRunner() {
  let localRunner = process.env.LOCAL_RUNNER;
  if (!localRunner) {
    const envFile = path.resolve(process.cwd(), '.env');
    if (fileExists(envFile)) {
      try { localRunner = dotenvParse(fs.readFileSync(envFile, 'utf8')).LOCAL_RUNNER; } catch {}
    }
  }
  localRunner = (localRunner || '').trim().toLowerCase();
  if (!localRunner || !['php', 'sail'].includes(localRunner)) localRunner = 'sail';
  return localRunner;
}

function runStagedTests(skipBranchCheck = false) {
  // Skip entirely if not on main
  const branch = getCurrentBranch();
  if (branch && branch !== 'main' && !skipBranchCheck) {
    log(`Current branch is '${branch}'. Skipping staged tests (only run on 'main').`);
    return 0;
  }

  const modified = getModifiedFiles();
  if (modified.length === 0) {
    log('No modified files; running full test suite.');
    const runner = determineRunner();
    if (runner === 'php') return runCmd('php', ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
    else return runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
  }

  const deptrac = readDeptracConfig();
  if (!deptrac) {
    log('deptrac.yaml not found or invalid; running full test suite.');
    const runner = determineRunner();
    if (runner === 'php') return runCmd('php', ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
    else return runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
  }

  const { domains, runAll } = extractDomainsFromFiles(modified);
  if (runAll) {
    log('Changes include files outside app/Domains (and not ignored); running full test suite.');
    const runner = determineRunner();
    if (runner === 'php') return runCmd('php', ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
    else return runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
  }
  if (domains.length === 0) {
    log('Changes contain only non code files, ignoring the  full test suite.');
    return 0;
  }

  // Build domain-level reverse dependency graph and its transitive closure
  const reverse = buildDomainReverseMap(deptrac);
  const closure = computeTransitiveClosure(reverse);

  // Compute impacted domains: directly modified + dependents via closure
  const impactedDomains = new Set(domains);
  for (const d of domains) for (const dep of closure.get(d) || []) impactedDomains.add(dep);

  // Print required info
  log(`Impacted domains (from modified files): ${[...new Set(domains)].join(', ')}`);
  // Print deptrac impacts (closure) succinctly
  for (const [src, deps] of closure.entries()) {
    if (deps.size > 0) log(`dep-impact ${src} -> ${[...deps].join(', ')}`);
  }

  const testDirs = testsDirsForDomains([...impactedDomains]);

  if (testDirs.length === 0) {
    log('No specific test directories resolved; running full test suite.');
    const runner = determineRunner();
    if (runner === 'php') return runCmd('php', ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
    else return runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
  }

  // Deduplicate and run
  const uniqueDirs = Array.from(new Set(testDirs));
  log(`Running tests for: ${uniqueDirs.join(' ')}`);
  const runner = determineRunner();
  const ok = (runner === 'php')
    ? runCmd('php', ['artisan', 'test', '--stop-on-failure', ...uniqueDirs])
    : runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure', ...uniqueDirs]);
  return ok ? 0 : 1;
}

// If executed directly, run and exit with returned code
const __filename = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === __filename) {
  const code = runStagedTests(true);
  process.exit(code);
}

export { runStagedTests };
