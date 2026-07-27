/**
 * Profile tab registry: the tabs a viewer sees, where they land, and the
 * owner-facing visibility indicator.
 *
 *   APP_USER=… APP_PASSWORD=… npm run browser:drive -- \
 *     .agents/skills/run-app/flows/profile-tabs.mjs
 *
 * Doubles as the worked example for writing new flows.
 */
export default async function ({ page, ctx, browser, helpers }) {
  const { login, goto, shot, tabStrip, toggleSetting, check, BASE } = helpers;

  await login(page);

  // --- as the owner -------------------------------------------------------
  await goto(page, '/profile');
  // /profile renders the own profile without redirecting to the slug URL, so
  // read the slug off a tab link rather than off the address bar.
  const slug = await page.evaluate(() =>
    new URL(document.querySelector('nav[role="tablist"] a[role="tab"]').href)
      .pathname.split('/').slice(-2)[0]);
  console.log('  slug  ' + slug);

  let tabs = await tabStrip(page);
  check('owner: lands on the default tab (stories)',
    tabs.find((t) => t.selected)?.key === 'stories', tabs.find((t) => t.selected)?.key);
  check('owner: indicators on exactly the setting-gated tabs',
    JSON.stringify(tabs.map((t) => [t.key, t.icon])) ===
      JSON.stringify([['about', null], ['stories', null], ['comments', 'visibility'],
        ['following', 'visibility'], ['quotes', 'visibility']]),
    tabs.map((t) => t.key + '=' + t.icon).join(' '));

  for (const key of ['about', 'stories', 'comments', 'following', 'quotes']) {
    const status = await goto(page, `/profile/${slug}/${key}`);
    const strip = await tabStrip(page);
    check(`owner: ${key} renders and self-selects`,
      status === 200 && strip.find((t) => t.selected)?.key === key,
      `status=${status} selected=${strip.find((t) => t.selected)?.key}`);
  }
  await shot(page, 'owner-profile');

  await goto(page, `/profile/${slug}/banana`);
  check('unknown tab redirects to the profile', page.url() === `${BASE}/profile/${slug}`, page.url());

  // --- toggling a privacy setting flips the indicator ---------------------
  await toggleSetting(page, 'profile', 'Masquer mon carnet de citations');
  await goto(page, `/profile/${slug}/stories`);
  let quotes = (await tabStrip(page)).find((t) => t.key === 'quotes');
  check('after hiding: owner keeps the tab', Boolean(quotes));
  check('after hiding: strip icon flips', quotes?.icon === 'visibility_off', quotes?.icon);
  await shot(page, 'owner-quotes-hidden');

  await toggleSetting(page, 'profile', 'Masquer mon carnet de citations');
  await goto(page, `/profile/${slug}/stories`);
  quotes = (await tabStrip(page)).find((t) => t.key === 'quotes');
  check('after restoring: strip icon back to visible', quotes?.icon === 'visibility', quotes?.icon);

  // --- as a guest ---------------------------------------------------------
  const guest = await (await browser.newContext()).newPage();
  await guest.goto(`${BASE}/profile/${slug}`, { waitUntil: 'networkidle' });
  const guestTabs = await tabStrip(guest);
  check('guest: only public tabs', JSON.stringify(guestTabs.map((t) => t.key)) === '["stories"]',
    guestTabs.map((t) => t.key).join(','));
  check('guest: no indicator', guestTabs.every((t) => t.icon === null));

  await guest.goto(`${BASE}/profile/${slug}/about`, { waitUntil: 'networkidle' });
  check('guest on a protected tab goes to login', guest.url().includes('/login'), guest.url());

  await guest.goto(`${BASE}/profile/${slug}/banana`, { waitUntil: 'networkidle' });
  check('guest on an unknown tab goes to the profile, not login',
    guest.url() === `${BASE}/profile/${slug}`, guest.url());
}
