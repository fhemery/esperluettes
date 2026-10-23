import { devices } from '@playwright/test';
import path from 'node:path';
import { FanartMessageForm } from '../../pages/FanartStoryPage';
import { NotificationsPage } from '../../pages/NotificationsPage';
import { COAUTHORED_STORY, GIFT_MEDIA, STORY, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — optional message to the authors on a fanart submission
 * (temporary: deleted at WRAP).
 *
 * Browser-only: layout of the field, what the notification list actually
 * shows. Delivery targets, escaping at the payload level, validation and
 * storage are feature tests in app/Domains/Fanart/Tests.
 */

const image = path.join(ROOT, GIFT_MEDIA.image.path);
const sound = path.join(ROOT, GIFT_MEDIA.sound.path);
const SHORT = 'Merci pour cette histoire E2E';
const HTML = '<b>html</b> litteral E2E';
const LONG = ('Mot' + 'x'.repeat(20) + ' ').repeat(23).slice(0, 500);

async function submitImage(form: FanartMessageForm, title: string, message: string | null): Promise<void> {
  await form.goto('image');
  await form.title.fill(title);
  await form.attachImage(image);
  if (message !== null) await form.message.fill(message);
  await form.attestation.check();
  await form.submit.click();
}

test.describe('submission form', () => {
  for (const kind of ['image', 'audio'] as const) {
    test(`${kind}: field between chapter and attestation, full width`, async ({ confirmed }) => {
      const form = new FanartMessageForm(confirmed, STORY.slug);
      await form.goto(kind);
      await expect(form.label).toContainText('Message pour les auteur·ices (facultatif)');
      await expect(form.help).toContainText('500 caractères maximum');
      await expect(form.message).toHaveAttribute('maxlength', '500');

      const chapter = (await form.chapter.boundingBox())!;
      const message = (await form.message.boundingBox())!;
      const attestation = (await form.attestation.boundingBox())!;
      expect(message.y).toBeGreaterThan(chapter.y + chapter.height - 1);
      expect(attestation.y).toBeGreaterThan(message.y + message.height - 1);
      const title = (await form.title.boundingBox())!;
      expect(Math.abs(message.width - title.width)).toBeLessThanOrEqual(4);
      await form.shot(`form-${kind}-desktop`);
    });
  }

  test('mobile 375 px: no horizontal overflow, textarea within the viewport', async ({ browser }) => {
    const context = await browser.newContext({
      ...devices['Pixel 7'],
      viewport: { width: 375, height: 800 },
      storageState: path.join(ROOT, storageStatePath('confirmed')),
    });
    const page = await context.newPage();
    const form = new FanartMessageForm(page, STORY.slug);
    await form.goto('image');
    await expect(form.message).toBeVisible();
    const box = (await form.message.boundingBox())!;
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(375);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
    await form.shot('form-image-mobile');
    await context.close();
  });

  for (const kind of ['image', 'audio'] as const) {
    test(`author's artwork form (${kind}) has no message field`, async ({ author }) => {
      const form = new FanartMessageForm(author, STORY.slug);
      await form.goto(kind);
      await expect(form.title).toBeVisible();
      await expect(form.message).toHaveCount(0);
      await form.shot(`form-artwork-${kind}`);
    });
  }

  test('a 501-character message shows the French error and keeps the input', async ({ confirmed }) => {
    const form = new FanartMessageForm(confirmed, STORY.slug);
    await form.goto('image');
    await form.title.fill('Titre conservé E2E');
    await form.attachImage(image);
    await form.message.evaluate((el: HTMLTextAreaElement) => el.removeAttribute('maxlength'));
    await form.message.fill('a'.repeat(501));
    await form.attestation.check();
    await form.submit.click();

    await expect(form.messageError).toContainText('Le message ne doit pas dépasser 500 caractères.');
    await expect(form.title).toHaveValue('Titre conservé E2E');
    await expect(form.message).toHaveValue('a'.repeat(501));
    await form.shot('form-error-501');
  });
});

test.describe('notifications', () => {
  test('authors read the message; co-author too; submitter gets nothing; HTML stays literal; long wraps', async ({
    confirmed,
    moderator,
    author,
    browser,
  }) => {
    // confirmed (reader of story 1) submits three: short, HTML, 500 chars, and one without.
    const form = new FanartMessageForm(confirmed, STORY.slug);
    await submitImage(form, 'Avec message E2E', SHORT);
    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();
    await submitImage(form, 'Avec HTML E2E', HTML);
    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();
    await submitImage(form, 'Long message E2E', LONG);
    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();
    await submitImage(form, 'Sans message E2E', null);
    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();

    // Submitter's own list: nothing for their own submission.
    const own = new NotificationsPage(confirmed);
    await own.goto();
    await expect(own.items.filter({ hasText: 'Avec message E2E' })).toHaveCount(0);
    await expect(own.items.filter({ hasText: SHORT })).toHaveCount(0);

    // Author's list.
    const list = new NotificationsPage(author);
    await list.goto();
    const short = list.item(SHORT);
    await expect(short).toHaveCount(1);
    await expect(short).toContainText(`« ${SHORT} »`);
    await expect(short.locator('a').first()).toHaveAttribute('href', /.+/);

    const html = list.item('litteral E2E');
    await expect(html).toContainText('<b>html</b> litteral E2E');
    await expect(html.locator('b')).toHaveCount(0);

    const none = list.item('Sans message E2E');
    await expect(none).toHaveCount(1);
    const text = (await none.locator('.col-span-2').innerText()).trim();
    expect(text).not.toContain('— «');
    expect(text.endsWith('»')).toBe(false);
    expect(text).not.toMatch(/—\s*$/);

    const long = list.item(LONG.slice(0, 40));
    await expect(long).toHaveCount(1);
    const box = (await long.boundingBox())!;
    expect(box.x + box.width).toBeLessThanOrEqual(1280);
    expect(await author.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(0);
    await list.shot('notifications-author-desktop');

    // Long one on mobile.
    const mobile = await browser.newContext({
      ...devices['Pixel 7'],
      viewport: { width: 375, height: 800 },
      storageState: path.join(ROOT, storageStatePath('author')),
    });
    const mpage = await mobile.newPage();
    const mlist = new NotificationsPage(mpage);
    await mlist.goto();
    await expect(mlist.item(LONG.slice(0, 40))).toHaveCount(1);
    expect(await mpage.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(0);
    await mlist.shot('notifications-author-mobile');
    await mobile.close();

    // Co-authored story 2 (author + confirmed): moderator submits, both authors read it.
    const modForm = new FanartMessageForm(moderator, COAUTHORED_STORY.slug);
    await modForm.goto('image');
    await modForm.title.fill('Coécrite E2E');
    await modForm.attachImage(image);
    await modForm.message.fill('Pour les deux auteurs E2E');
    await modForm.attestation.check();
    await modForm.submit.click();
    await expect(moderator.getByText('Votre fanart a été envoyé.')).toBeVisible();

    const authorList = new NotificationsPage(author);
    await authorList.goto();
    await expect(authorList.item('Pour les deux auteurs E2E')).toHaveCount(1);
    const coList = new NotificationsPage(confirmed);
    await coList.goto();
    await expect(coList.item('Pour les deux auteurs E2E')).toHaveCount(1);
    const modList = new NotificationsPage(moderator);
    await modList.goto();
    await expect(modList.item('Pour les deux auteurs E2E')).toHaveCount(0);
  });

  test('a sound submission carries the message too', async ({ confirmed, author }) => {
    const form = new FanartMessageForm(confirmed, STORY.slug);
    await form.goto('audio');
    await form.title.fill('Son avec message E2E');
    await form.attachSound(sound);
    await form.chapter.selectOption({ index: 1 });
    await form.message.fill('Message du son E2E');
    await form.attestation.check();
    await form.submit.click();
    await expect(confirmed.getByText('Votre fanart a été envoyé.')).toBeVisible();

    const list = new NotificationsPage(author);
    await list.goto();
    await expect(list.item('Message du son E2E')).toHaveCount(1);
  });
});
