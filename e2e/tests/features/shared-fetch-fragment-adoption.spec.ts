import { FragmentLoaders } from '../../pages/FragmentLoaders';
import { expect, test } from '../../support/test';

/**
 * Four loaders adopted `window.fetchFragment`: happy path still injects, a CGU
 * redirect navigates the window (no page-in-a-page), a 419 reloads the page.
 * Failures are simulated by answering the fragment request in the browser.
 */

const SETTINGS = /\/settings\/[a-z-]+$/;
const CHAPTERS = /\/stories\/admin\/moderation\/\d+\/chapters$/;
const USER_SEARCH = /\/admin\/moderation\/search\?/;
const PROFILE_SEARCH = /\/profile\/search\?/;

test.describe('Settings tabs', () => {
  test('switching tab loads the content and updates the URL', async ({ confirmed }) => {
    const p = new FragmentLoaders(confirmed);
    await p.gotoSettings();
    const tabs = p.settingsTabs;
    expect(await tabs.count()).toBeGreaterThan(1);
    const before = await p.settingsContent.innerHTML();
    await tabs.nth(1).click();
    await expect(confirmed).toHaveURL(/\/settings\?tab=[a-z-]+$/);
    await expect.poll(() => p.settingsContent.innerHTML()).not.toBe(before);
    await expect(p.settingsContent.locator('html, body')).toHaveCount(0);
  });

  test('CGU redirect navigates the window and injects nothing', async ({ confirmed }) => {
    const p = new FragmentLoaders(confirmed);
    await p.gotoSettings();
    await p.tagDocument();
    await p.redirectFragmentsToTerms(SETTINGS);
    const navigated = confirmed.waitForEvent('load');
    await p.settingsTabs.nth(1).click();
    await navigated;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });

  test('419 reloads the page and injects nothing', async ({ confirmed }) => {
    const p = new FragmentLoaders(confirmed);
    await p.gotoSettings();
    await p.tagDocument();
    await p.expireFragments(SETTINGS);
    const reloaded = confirmed.waitForEvent('load');
    await p.settingsTabs.nth(1).click();
    await reloaded;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });
});

test.describe('Story admin moderation chapters', () => {
  test('chapters expand and collapse', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoStoryModeration();
    await p.chaptersToggle.click();
    await expect(p.expandedChapters.first()).toBeVisible();
    await expect(p.expandedChapters.first().locator('html, body')).toHaveCount(0);
    await moderator.locator('tbody button:has(.material-symbols-outlined:text-is("expand_less"))').first().click();
    await expect(p.expandedChapters).toHaveCount(0);
  });

  test('CGU redirect navigates the window', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoStoryModeration();
    await p.tagDocument();
    await p.redirectFragmentsToTerms(CHAPTERS);
    const navigated = moderator.waitForEvent('load');
    await p.chaptersToggle.click();
    await navigated;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });

  test('419 reloads the page', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoStoryModeration();
    await p.tagDocument();
    await p.expireFragments(CHAPTERS);
    const reloaded = moderator.waitForEvent('load');
    await p.chaptersToggle.click();
    await reloaded;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });
});

test.describe('Moderation user management search', () => {
  test('search returns results', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoUserManagement();
    await p.userSearch.fill('E2E');
    await expect(p.userSearchResults).toContainText('E2E Author');
    await expect(p.userSearchResults.locator('html, body')).toHaveCount(0);
  });

  test('CGU redirect navigates the window', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoUserManagement();
    await p.tagDocument();
    await p.redirectFragmentsToTerms(USER_SEARCH);
    const navigated = moderator.waitForEvent('load');
    await p.userSearch.fill('E2E');
    await navigated;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });

  test('419 reloads the page', async ({ moderator }) => {
    const p = new FragmentLoaders(moderator);
    await p.gotoUserManagement();
    await p.tagDocument();
    await p.expireFragments(USER_SEARCH);
    const reloaded = moderator.waitForEvent('load');
    await p.userSearch.fill('E2E');
    await reloaded;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });
});

test.describe('Activation-code sponsor search (Profile user-search-input)', () => {
  test('search shows results', async ({ admin }) => {
    const p = new FragmentLoaders(admin);
    await p.gotoActivationCodeCreate();
    await p.sponsorInput.fill('E2E');
    await expect(p.sponsorResults.first()).toBeVisible();
  });

  test('CGU redirect navigates the window', async ({ admin }) => {
    const p = new FragmentLoaders(admin);
    await p.gotoActivationCodeCreate();
    await p.tagDocument();
    await p.redirectFragmentsToTerms(PROFILE_SEARCH);
    const navigated = admin.waitForEvent('load');
    await p.sponsorInput.fill('E2E');
    await navigated;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });

  test('419 reloads the page', async ({ admin }) => {
    const p = new FragmentLoaders(admin);
    await p.gotoActivationCodeCreate();
    await p.tagDocument();
    await p.expireFragments(PROFILE_SEARCH);
    const reloaded = admin.waitForEvent('load');
    await p.sponsorInput.fill('E2E');
    await reloaded;
    expect(await p.isDocumentStillTagged()).toBe(false);
  });
});
