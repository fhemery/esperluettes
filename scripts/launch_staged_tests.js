#!/usr/bin/env node
/*
  Launch tests only for Domains impacted by staged changes.

  Approach:
  - Read staged files via `git diff --cached --name-only`.
  - Parse deptrac.yaml with js-yaml to understand layers and ruleset.
  - Map each staged file to one or more layers using `directory` collectors and conventions:
      * If a file is under app/Domains/<Domain>/Tests/ => <Domain>Tests layer
      * If under app/Domains/<Domain>/PublicApi/ => <Domain>Public layer (if exists)
      * Else under app/Domains/<Domain>/ => <Domain>Private layer (if exists)
    Additionally, for layers with `collectors: [{type: directory, value: ...}]` we match by path prefix.
  - Build a dependency graph from ruleset (layer -> allowed layers). Then compute reverse reachability
    from changed layers to determine which Test layers depend on them.
  - Translate impacted Test layers to test directories (app/Domains/<Domain>/Tests) and run
    `php artisan test` (or Sail) for just those directories.

  Notes:
  - If no staged files map to any domain, run full test suite as fallback.
  - If we can't parse deptrac or graph resolution fails, fallback to full tests.
*/

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import yaml from 'js-yaml';
import { parse as dotenvParse } from 'dotenv';

function log(msg) { process.stdout.write(`[staged-tests] ${msg}\n`); }

function fileExists(p) { try { return fs.existsSync(p); } catch { return false; } }

function runCmd(cmd, args, opts = {}) {
  const res = spawnSync(cmd, args, { stdio: 'inherit', shell: process.platform === 'win32', ...opts });
  return res.status === 0;
}

function getStagedFiles() {
  const res = spawnSync('git', ['diff', '--cached', '--name-only'], { encoding: 'utf8' });
  if (res.status !== 0) return [];
  return (res.stdout || '').split(/\r?\n/).map(s => s.trim()).filter(Boolean);
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

function buildLayerDirectoryMap(deptrac) {
  const layers = deptrac?.deptrac?.layers || [];
  const dirMap = new Map(); // layerName -> [dir prefixes]
  for (const layer of layers) {
    const name = layer.name;
    const dirs = [];
    for (const c of layer.collectors || []) {
      if (c.type === 'directory' && typeof c.value === 'string') {
        // Normalize to forward slashes and ensure trailing slash
        let v = c.value.replace(/\\/g, '/');
        if (!v.endsWith('/')) v += '/';
        dirs.push(v);
      }
    }
    if (dirs.length) dirMap.set(name, dirs);
  }
  return dirMap;
}

function deriveDomainFromPath(relPath) {
  // expects forward slashes
  const m = relPath.match(/^app\/Domains\/([^/]+)\//);
  return m ? m[1] : null;
}

function probableLayerNamesForPath(relPath, deptracLayers) {
  // Using conventions to cover layers that use complex collectors
  const domain = deriveDomainFromPath(relPath);
  if (!domain) return [];
  const names = deptracLayers.map(l => l.name);
  const candidates = [];
  if (relPath.startsWith(`app/Domains/${domain}/Tests/`)) {
    const testLayer = `${domain}Tests`;
    if (names.includes(testLayer)) candidates.push(testLayer);
  } else if (relPath.startsWith(`app/Domains/${domain}/PublicApi/`)) {
    const pubLayer = `${domain}Public`;
    if (names.includes(pubLayer)) candidates.push(pubLayer);
  } else {
    const privLayer = `${domain}Private`;
    if (names.includes(privLayer)) candidates.push(privLayer);
  }
  return candidates;
}

function mapFilesToLayers(stagedFiles, deptrac) {
  const layers = deptrac?.deptrac?.layers || [];
  const dirMap = buildLayerDirectoryMap(deptrac); // dir-based mapping
  const impacted = new Set();
  const directTestDirs = new Set();

  for (const f of stagedFiles) {
    const rel = f.replace(/\\/g, '/');
    if (!rel.startsWith('app/Domains/')) continue;

    // If a staged file is itself a test under a domain, schedule that domain's tests directly
    const testMatch = rel.match(/^app\/Domains\/([^/]+)\/Tests\//);
    if (testMatch) {
      const domain = testMatch[1];
      directTestDirs.add(`app/Domains/${domain}/Tests`);
    }

    // 1) Exact directory collectors mapping
    for (const [layer, dirs] of dirMap.entries()) {
      if (dirs.some(d => rel.startsWith(d))) impacted.add(layer);
    }
    // 2) Convention-based fallback for layers defined via bool collectors (e.g., *Private)
    for (const name of probableLayerNamesForPath(rel, layers)) {
      impacted.add(name);
    }
  }
  return { changedLayers: [...impacted], directTestDirs: [...directTestDirs] };
}

function buildReverseGraph(deptrac) {
  const ruleset = deptrac?.deptrac?.ruleset || {};
  const reverse = new Map(); // node -> set(of dependents)
  const nodes = new Set(Object.keys(ruleset));
  // Include any layers that are only on RHS
  for (const deps of Object.values(ruleset)) for (const d of deps) nodes.add(d);
  for (const n of nodes) reverse.set(n, new Set());
  for (const [layer, deps] of Object.entries(ruleset)) {
    for (const d of deps) {
      if (!reverse.has(d)) reverse.set(d, new Set());
      reverse.get(d).add(layer);
    }
  }
  return reverse;
}

function collectDependentTestsLayers(changedLayers, deptrac) {
  const reverse = buildReverseGraph(deptrac);
  const testsLayers = (deptrac?.deptrac?.layers || [])
    .map(l => l.name)
    .filter(n => n.endsWith('Tests'));

  const impactedTests = new Set();
  // BFS/DFS upwards: from changed layer to all dependents; capture *Tests layers
  const visited = new Set();
  const stack = [...changedLayers];
  while (stack.length) {
    const node = stack.pop();
    if (visited.has(node)) continue;
    visited.add(node);
    if (testsLayers.includes(node)) impactedTests.add(node);
    const ups = reverse.get(node);
    if (ups) for (const u of ups) stack.push(u);
  }

  // Also include test layers for the same domain of each changed layer by convention
  for (const layer of changedLayers) {
    const m = layer.match(/^(.*?)(Public|Private|Tests)$/);
    const domain = m ? m[1] : (layer === 'StoryRef' ? 'StoryRef' : null);
    if (domain) {
      const t = `${domain}Tests`;
      if (testsLayers.includes(t)) impactedTests.add(t);
    }
  }

  return [...impactedTests];
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

  const staged = getStagedFiles();
  if (staged.length === 0) {
    log('No staged files; running full test suite.');
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

  const { changedLayers, directTestDirs } = mapFilesToLayers(staged, deptrac);
  if (changedLayers.length === 0 && directTestDirs.length === 0) {
    log('No staged files mapped to domains; running full test suite.');
    const runner = determineRunner();
    if (runner === 'php') return runCmd('php', ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
    else return runCmd(path.join('vendor', 'bin', 'sail'), ['artisan', 'test', '--stop-on-failure']) ? 0 : 1;
  }

  if (changedLayers.length) log(`Changed layers: ${changedLayers.join(', ')}`);
  const impactedTestsLayers = collectDependentTestsLayers(changedLayers, deptrac);
  if (impactedTestsLayers.length) log(`Impacted test layers: ${impactedTestsLayers.join(', ')}`);
  const testDirs = [...testsDirsForTestLayers(impactedTestsLayers), ...directTestDirs];

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
