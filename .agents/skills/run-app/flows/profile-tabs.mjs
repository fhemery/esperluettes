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
  check('owner: sees all five tabs',
    JSON.stringify(tabs.map((t) => t.key)) ===
      JSON.stringify(['about', 'stories', 'comments', 'following', 'quotes']),
    tabs.map((t) => t.key).join(','));
  check('owner: the tab strip carries no icons', tabs.every((t) => t.icon === null));

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

  // --- exactly one indicator per tab, and only on setting-gated ones -------
  const indicator = (p) => p.evaluate(() => {
    const boxes = document.querySelectorAll('[data-test-id="profile-tab-visibility"]');
    const eyes = Array.from(document.querySelectorAll('span'))
      .filter((s) => ['visibility', 'visibility_off'].includes(s.textContent.trim()));
    return { boxes: boxes.length, eyes: eyes.length, icon: eyes[0]?.textContent.trim() || null };
  });

  for (const key of ['comments', 'following', 'quotes']) {
    await goto(page, `/profile/${slug}/${key}`);
    const i = await indicator(page);
    check(`owner: ${key} shows exactly one indicator`, i.boxes === 1 && i.eyes === 1, JSON.stringify(i));
  }
  await goto(page, `/profile/${slug}/stories`);
  check('owner: stories has no indicator', (await indicator(page)).boxes === 0);

  // --- toggling a privacy setting flips the indicator ---------------------
  await toggleSetting(page, 'profile', 'Masquer mon carnet de citations');
  await goto(page, `/profile/${slug}/quotes`);
  check('after hiding: indicator flips to visibility_off',
    (await indicator(page)).icon === 'visibility_off', (await indicator(page)).icon);
  await shot(page, 'owner-quotes-hidden');

  await toggleSetting(page, 'profile', 'Masquer mon carnet de citations');
  await goto(page, `/profile/${slug}/quotes`);
  check('after restoring: indicator back to visible',
    (await indicator(page)).icon === 'visibility', (await indicator(page)).icon);

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
