#!/usr/bin/env node
/**
 * One-time setup for browser-driving the app.
 *
 * `npm install` brings the playwright package, but not the browser binary,
 * which lives outside the repo (~/.cache/ms-playwright, ~115 MB). This checks
 * for it and downloads it only when missing, so it is safe (and fast) to run
 * before every session.
 *
 *   npm run browser:setup
 */
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);

function ok(msg) { console.log(`  ok    ${msg}`); }
function work(msg) { console.log(`  ...   ${msg}`); }
function fail(msg) { console.error(`  FAIL  ${msg}`); }

// 1. The npm package — a devDependency, so this is just a guard against a
//    stale node_modules.
let version;
try {
  version = require('playwright/package.json').version;
  ok(`playwright ${version}`);
} catch {
  fail('playwright is missing from node_modules. Run: npm install');
  process.exit(1);
}

// 2. The browser binary. executablePath() returns a path whether or not the
//    download happened, so check the file is actually there.
const { chromium } = require('playwright');
const { existsSync } = await import('node:fs');

if (existsSync(chromium.executablePath())) {
  ok('chromium binary present');
} else {
  work('downloading chromium (~115 MB, one time)');
  execFileSync('npx', ['playwright', 'install', 'chromium'], { stdio: 'inherit' });
  if (!existsSync(chromium.executablePath())) {
    fail('chromium still missing after install');
    process.exit(1);
  }
  ok('chromium installed');
}

// 3. Prove it launches. On a bare container this is where missing system
//    libraries surface; the error names the .so, and `npx playwright
//    install-deps chromium` fixes it.
try {
  const browser = await chromium.launch({ args: ['--no-sandbox'] });
  await browser.close();
  ok('chromium launches headless');
} catch (e) {
  fail(`chromium will not launch: ${e.message.split('\n')[0]}`);
  console.error('  Try: npx playwright install-deps chromium');
  process.exit(1);
}

// 4. Is the app actually up? Not fatal — you may be about to start it.
const base = process.env.APP_BASE_URL || 'http://localhost';
try {
  const res = await fetch(base, { signal: AbortSignal.timeout(5000) });
  ok(`app responding at ${base} (${res.status})`);
} catch {
  console.log(`  warn  no response from ${base} — start it with: ./vendor/bin/sail up -d`);
}

console.log('\nReady. Drive the app with: npm run browser:drive -- <flow>');
