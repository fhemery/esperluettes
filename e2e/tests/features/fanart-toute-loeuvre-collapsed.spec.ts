import { FanartStoryPage } from '../../pages/FanartStoryPage';
import { FANART } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/** FEATURE — « Toute l'œuvre » collapsed by default (temporary: deleted at WRAP). */

for (const role of ['confirmed', 'author'] as const) {
  test(`${role}: work group starts closed, no spoiler notice, expands and collapses on click`, async ({ confirmed, author }) => {
    const page = role === 'confirmed' ? confirmed : author;
    const storyPage = new FanartStoryPage(page, FANART.storySlug);
    await storyPage.goto();
    await storyPage.openFanartsTab();

    const work = storyPage.group('work');
    await expect(work).toBeVisible();
    await expect(work).toHaveAttribute('data-collapsed', 'true');
    await expect(work.locator('[data-fanart-id]').first()).toBeHidden();
    await expect(work.getByText('Attention, cette section peut dévoiler')).toBeHidden();

    await work.getByRole('button', { name: 'Montrer tout' }).click();
    await expect(work.locator('[data-fanart-id]').first()).toBeVisible({ timeout: 3000 });
    await work.getByRole('button', { name: 'Cacher tout' }).click();
    await expect(work.locator('[data-fanart-id]').first()).toBeHidden();
  });
}
