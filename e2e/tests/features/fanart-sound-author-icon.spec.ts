import { FanartStoryPage } from '../../pages/FanartStoryPage';
import { ACCOUNTS, FANART, STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — fanart sound author icon (temporary: deleted at WRAP).
 *
 * Alpine behaviour only: the story-page icon is hidden until play and follows
 * the chapter as the queue advances. Rendering is covered by feature tests.
 */

const confirmed = ACCOUNTS.confirmed;
const techAdmin = ACCOUNTS.tech_admin;

test('chapter page: icon links to the artist profile with a « Par » tooltip', async ({ confirmed: page }) => {
  await page.goto(`/stories/${STORY.slug}/chapters/${STORY.publishedChapter.slug}`);
  const link = page.locator('[data-chapter-audio] [data-artist-link]');
  await expect(link).toBeVisible();
  await expect(link).toHaveAttribute('href', new RegExp(`/profile/${confirmed.profileSlug}$`));
  await expect(link).toHaveAttribute('title', `Par ${confirmed.displayName}`);
});

test('story page: icon hidden until play, then follows the playing chapter', async ({ confirmed: page }) => {
  const storyPage = new FanartStoryPage(page, FANART.storySlug);
  await storyPage.goto();
  await expect(storyPage.artistLink).toBeHidden();

  await storyPage.audioIcon(FANART.chapterIds.published).click();
  await expect(storyPage.artistLink).toBeVisible();
  await expect(storyPage.artistLink).toHaveAttribute('href', new RegExp(`/profile/${confirmed.profileSlug}$`));
  await expect(storyPage.artistLink).toHaveAttribute('title', `Par ${confirmed.displayName}`);

  // Switch to chapter three by clicking its speaker (no need to wait for the end of the clip).
  await storyPage.audioIcon(FANART.chapterIds.simple).click();
  await expect(storyPage.player.locator('audio')).toHaveAttribute('src', new RegExp(`/fanarts/${FANART.ids.soundThree}/file$`));
  await expect(storyPage.artistLink).toHaveAttribute('href', new RegExp(`/profile/${techAdmin.profileSlug}$`));
  await expect(storyPage.artistLink).toHaveAttribute('title', `Par ${techAdmin.displayName}`);
});

test('mobile 375px: the player row does not overflow', async ({ confirmed: page }) => {
  await page.setViewportSize({ width: 375, height: 800 });
  await page.goto(`/stories/${STORY.slug}/chapters/${STORY.publishedChapter.slug}`);
  const row = page.locator('[data-chapter-audio]');
  await expect(row.locator('[data-artist-link]')).toBeVisible();
  const box = await row.locator('[data-artist-link]').boundingBox();
  expect(box!.x + box!.width).toBeLessThanOrEqual(375);
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(375);
  await page.screenshot({ path: '/tmp/claude-1000/-home-fred-ws-esperluettes/6a4dede3-2d53-49fb-aea8-93843095b5e3/scratchpad/m.png' });
});
