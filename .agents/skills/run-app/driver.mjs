#!/usr/bin/env node
/**
 * Browser harness for driving the running app.
 *
 *   npm run browser:drive -- <flow.mjs> [args…]     run a flow file
 *   npm run browser:drive -- --goto /profile --as fred@hemit.fr --shot p.png
 *
 * A flow file default-exports `async ({ page, ctx, browser, helpers }) => {}`.
 * See flows/profile-tabs.mjs for a worked example.
 *
 * Credentials come from the environment, never from this repo:
 *   APP_USER / APP_PASSWORD   (or --as <email>, with APP_PASSWORD set)
 *   APP_BASE_URL              defaults to http://localhost
 */
import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { mkdirSync } from 'node:fs';
import path from 'node:path';

const HERE = path.dirname(new URL(import.meta.url).pathname);
const SHOTS = process.env.APP_SHOTS || path.join(HERE, 'shots');
export const BASE = (process.env.APP_BASE_URL || 'http://localhost').replace(/\/$/, '');

// ---------------------------------------------------------------- helpers

/** Log in through the real form. Returns the URL landed on. */
export async function login(page, email = process.env.APP_USER, password = process.env.APP_PASSWORD) {
  if (!email || !password) {
    throw new Error('Set APP_USER and APP_PASSWORD (never hardcode credentials in the repo).');
  }
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  if (page.url().includes('/login')) {
    const err = await page.locator('.text-red-600, [role="alert"]').first().textContent().catch(() => null);
    throw new Error(`Login failed for ${email}${err ? ': ' + err.trim() : ''}`);
  }
  return page.url();
}

export async function goto(page, urlPath) {
  const res = await page.goto(BASE + urlPath, { waitUntil: 'networkidle' });
  return res?.status();
}

/** Screenshot into the skill's shots/ dir. Returns the path — go look at it. */
export async function shot(page, name) {
  mkdirSync(SHOTS, { recursive: true });
  const file = path.join(SHOTS, name.endsWith('.png') ? name : name + '.png');
  await page.screenshot({ path: file, fullPage: false });
  console.log('  shot  ' + file);
  return file;
}

/**
 * Read the tab strip of any page using x-shared::scrollable-tabs
 * (profile, settings): key, label, selected, and the visibility icon.
 */
export async function tabStrip(page) {
  return page.evaluate(() => {
    const tabs = document.querySelectorAll('nav[role="tablist"] a[role="tab"]');
    return Array.from(tabs).map((a) => {
      const icons = Array.from(a.querySelectorAll('span.material-symbols-outlined'))
        .map((s) => s.textContent.trim());
      return {
        key: new URL(a.href).pathname.split('/').pop(),
        label: a.textContent.replace(/\s+/g, ' ').trim(),
        selected: a.getAttribute('aria-selected') === 'true',
        icon: icons.find((i) => i === 'visibility' || i === 'visibility_off') || null,
      };
    });
  });
}

/**
 * Flip a boolean user setting through the UI and save it.
 * The switch is an sr-only checkbox inside a label, so the label is the
 * clickable thing — see Gotchas in SKILL.md.
 */
export async function toggleSetting(page, tab, title) {
  await goto(page, `/settings?tab=${tab}`);
  const row = page.locator('div[x-data^="settingsParameterRow"]').filter({ hasText: title });
  if (await row.count() === 0) throw new Error(`No settings row titled ${JSON.stringify(title)} on tab ${tab}`);
  await row.locator('label:has(input[type="checkbox"])').click();
  await page.waitForTimeout(200);
  await row.getByRole('button', { name: /Enregistrer/ }).click();
  await page.waitForTimeout(1200);
}

/** Simple assertion that prints a PASS/FAIL line and tracks failures. */
export const results = { failures: [] };
export function check(name, ok, detail) {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail !== undefined ? '  — ' + detail : ''}`);
  if (!ok) results.failures.push(name);
}

const helpers = { login, goto, shot, tabStrip, toggleSetting, check, results, BASE };

// ---------------------------------------------------------------- runner

async function main() {
  const argv = process.argv.slice(2);
  const flag = (n) => { const i = argv.indexOf(n); return i === -1 ? null : argv[i + 1]; };

  const browser = await chromium.launch({ args: ['--no-sandbox'] });
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();

  // Surface anything the page complains about; a silent 500 is worse than a loud one.
  const bad = [];
  page.on('response', (r) => { if (r.status() >= 400) bad.push(`${r.status()} ${r.url()}`); });
  page.on('pageerror', (e) => bad.push('PAGEERROR ' + e.message));

  let code = 0;
  try {
    const flowArg = argv.find((a) => a.endsWith('.mjs'));
    if (flowArg) {
      const mod = await import(pathToFileURL(path.resolve(flowArg)).href);
      await mod.default({ page, ctx, browser, helpers, args: argv });
    } else {
      const as = flag('--as') || process.env.APP_USER;
      if (as) await login(page, as, process.env.APP_PASSWORD);
      const target = flag('--goto');
      if (target) console.log('  http  ' + (await goto(page, target)) + ' ' + BASE + target);
      const name = flag('--shot');
      if (name) await shot(page, name);
      const js = flag('--eval');
      if (js) console.log('  eval  ' + JSON.stringify(await page.evaluate(js), null, 1));
    }
  } catch (e) {
    console.error('\nDRIVER ERROR: ' + e.message);
    code = 1;
  }

  if (bad.length) console.log('\nHTTP >=400 / page errors:\n  ' + [...new Set(bad)].join('\n  '));
  if (results.failures.length) {
    console.log(`\n${results.failures.length} FAILURE(S): ${results.failures.join(', ')}`);
    code = 1;
  } else if (!code) {
    console.log('\nall green');
  }

  await browser.close();
  process.exit(code);
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) await main();
