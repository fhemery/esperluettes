#!/usr/bin/env node
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import yaml from 'js-yaml';
import { makeLog, fileExists, runCmdWithOutput, determineRunner, isSailRunning } from './utils.js';

const log = makeLog('deptrac');

function readDeptracConfig() {
  const depPath = path.resolve(process.cwd(), 'deptrac.yaml');
  if (!fileExists(depPath)) return null;
  try {
    return yaml.load(fs.readFileSync(depPath, 'utf8'));
  } catch (e) {
    log.warn(`Failed to parse deptrac.yaml: ${e.message}`);
    return null;
  }
}

function getFilesystemDomains() {
  const domainsPath = path.resolve(process.cwd(), 'app/Domains');
  try {
    return fs.readdirSync(domainsPath, { withFileTypes: true })
      .filter(d => d.isDirectory())
      .map(d => d.name);
  } catch {
    return [];
  }
}

function getDeptracBaseDomains(deptracConfig) {
  const layers = deptracConfig?.deptrac?.layers || [];
  const names = new Set();
  for (const layer of layers) {
    if (!layer.name) continue;
    const m = layer.name.match(/^(.*?)(Public|Private|Tests)?$/);
    const base = m ? m[1] : layer.name;
    if (base) names.add(base);
  }
  return names;
}

function checkDomainCoverage(deptracConfig) {
  const fsDomains = getFilesystemDomains();
  const deptracDomains = getDeptracBaseDomains(deptracConfig);
  return fsDomains.filter(d => !deptracDomains.has(d));
}

function runDeptrac() {
  const runner = determineRunner();

  if (runner === 'sail' && !isSailRunning()) {
    log.warn('Sail is not running, skipping deptrac.');
    return 0;
  }

  const deptracConfig = readDeptracConfig();

  if (deptracConfig) {
    const missing = checkDomainCoverage(deptracConfig);
    if (missing.length > 0) {
      log.warn(`Domains not defined in deptrac.yaml: ${missing.join(', ')}`);
      log.raw('  → Add layers for these domains or they will not be checked for architectural violations.');
    }
  }

  log.header('Running deptrac...');
  const cmd = runner === 'php' ? 'composer' : path.join('vendor', 'bin', 'sail');
  const { ok, output } = runCmdWithOutput(cmd, ['composer', 'deptrac']);
  if (output) process.stdout.write(output + '\n');

  if (ok) {
    log.ok('Deptrac passed.');
    return 0;
  }

  log.warn('Deptrac found violations. Invoke the `fix-deptrac` skill to analyze and resolve.');
  return 1;
}

const __filename = fileURLToPath(import.meta.url);
if (process.argv[1] && path.resolve(process.argv[1]) === __filename) {
  process.exit(runDeptrac());
}

export { runDeptrac };
