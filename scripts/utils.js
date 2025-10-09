#!/usr/bin/env node
// Shared utilities for scripts in the repository (ESM)
// - Logging with optional namespace and levels (header, ok, warn)
// - Command runners (throwing and boolean-returning variants)
// - File helpers
// - Laravel runner determination (php|sail)

import fs from 'fs';
import path from 'path';
import { spawnSync } from 'child_process';
import { parse as dotenvParse } from 'dotenv';

// Logging
export function makeLog(namespace = null) {
  const BLUE = '\u001b[34m';
  const GREEN = '\u001b[32m';
  const YELLOW = '\u001b[33m';
  const NC = '\u001b[0m';
  const prefix = namespace ? `[${namespace}] ` : '';

  function write(line) {
    process.stdout.write(`${prefix}${line}\n`);
  }

  function base(sectionOrMsg, maybeMsg) {
    let section = null;
    let msg;
    if (maybeMsg !== undefined) {
      section = sectionOrMsg;
      msg = maybeMsg;
    } else {
      msg = sectionOrMsg;
    }
    const icon = section === 'header' ? `${BLUE}🚀${NC}` : section === 'ok' ? `${GREEN}✅${NC}` : section === 'warn' ? `${YELLOW}⚠️${NC}` : '';
    write(`${icon} ${msg}`);
  }
  base.header = (msg) => write(`${BLUE}🚀${NC} ${msg}`);
  base.ok = (msg) => write(`${GREEN}✅${NC} ${msg}`);
  base.warn = (msg) => write(`${YELLOW}⚠️${NC} ${msg}`);
  base.raw = (msg) => write(msg);
  return base;
}

// File helpers
export function fileExists(p) {
  try { return fs.existsSync(p); } catch { return false; }
}

// Command runners
// Feature-rich runner (throws on failure). Based on deploy-full.js implementation.
export function run(cmd, args, options = {}) {
  const isWin = process.platform === 'win32';
  const joined = [cmd, ...(args || [])].join(' ');
  // First try without a shell
  let res = spawnSync(cmd, args || [], { stdio: 'inherit', cwd: options.cwd, shell: false, ...options });
  if ((res.error || res.status !== 0) && isWin) {
    // Fallback: run through shell so Git Bash/CMD can resolve composer/composer.cmd and shims
    const cmdline = [cmd, ...((args || []).map(a => /\s/.test(a) ? `"${a}"` : a))].join(' ');
    res = spawnSync(cmdline, { stdio: 'inherit', cwd: options.cwd, shell: true, ...options });
  }
  if (res.error) throw res.error;
  if (res.status !== 0) throw new Error(`Command failed: ${joined}`);
}

// Runner that captures stdout (throws on failure)
export function runCapture(cmd, args, options = {}) {
  const isWin = process.platform === 'win32';
  const joined = [cmd, ...(args || [])].join(' ');
  const baseOpts = {
    cwd: options.cwd,
    shell: false,
    encoding: 'utf8',
    stdio: ['inherit', 'pipe', 'inherit'],
    ...options,
  };

  let res = spawnSync(cmd, args || [], baseOpts);

  if ((res.error || res.status !== 0) && isWin) {
    const cmdline = [cmd, ...((args || []).map(a => /\s/.test(a) ? `"${a}"` : a))].join(' ');
    res = spawnSync(cmdline, { ...baseOpts, shell: true });
  }

  if (res.error) throw res.error;
  if (res.status !== 0) throw new Error(`Command failed: ${joined}`);

  return (res.stdout || '').trim();
}

// Simple runner that returns boolean success
export function runCmd(cmd, args, opts = {}) {
  const res = spawnSync(cmd, args || [], { stdio: 'inherit', shell: process.platform === 'win32', ...opts });
  return res.status === 0;
}

// Determine whether to use local php or sail
export function determineRunner() {
  let localRunner = process.env.LOCAL_RUNNER;
  if (!localRunner) {
    const envFile = path.resolve(process.cwd(), '.env');
    if (fileExists(envFile)) {
      try { 
        localRunner = dotenvParse(fs.readFileSync(envFile, 'utf8')).LOCAL_RUNNER; 
      } catch (e) {}
    }
  }
  localRunner = (localRunner || '').trim().toLowerCase();
  if (!localRunner || !['php', 'sail'].includes(localRunner)) localRunner = 'sail';
  return localRunner;
}
