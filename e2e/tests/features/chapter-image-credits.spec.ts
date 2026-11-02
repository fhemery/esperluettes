import path from 'node:path';
import { ChapterCreatePage } from '../../pages/ChapterCreatePage';
import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { setAuthorCredits } from '../../support/credits';
import { STORY } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — chapter image upload credit cost. Kept deliberately at WRAP: the only
 * browser coverage of the edit-form counter and of the insufficient-credit state.
 *
 * Only the live counter is browser-only; the charge itself is a feature test in
 * app/Domains/Story/Tests. The seeded author starts with 5 credits.
 */

const IMAGE = path.join(ROOT, 'e2e/fixtures/gift-image.png');

test('the counter follows picked files and removed blocks, and never blocks submit', async ({ author }) => {
  const create = new ChapterCreatePage(author);
  await create.goto();
  await create.blocks.goAdvanced();

  await expect(create.creditsCounter).toContainText('1 crédit');

  await create.blocks.addBlock('image');
  await create.blocks.addBlock('image');
  await create.imageFileInput(1).setInputFiles(IMAGE);
  await create.imageFileInput(2).setInputFiles(IMAGE);
  await expect(create.creditsCounter).toContainText('3 crédits');
  await expect(create.creditsCounter).not.toContainText('insuffisant');

  await create.blocks.removeBlock(2);
  await expect(create.creditsCounter).toContainText('2 crédits');

  await create.confirmPropertyOf(1);
  await create.title.fill('Chapitre avec image');
  await create.blocks.textBlockEditor(0).fill('Texte');
  await expect(create.saveButton).toBeEnabled();
  await create.saveButton.click();
  await author.waitForLoadState('networkidle');
  await expect(author).not.toHaveURL(/\/create$/);
});

test('create: an unaffordable save shows the text hint, is rejected, and keeps the input', async ({ author }) => {
  setAuthorCredits(2);
  const create = new ChapterCreatePage(author);
  await create.goto();
  await create.blocks.goAdvanced();
  await create.blocks.addBlock('image');
  await create.blocks.addBlock('image');
  await create.imageFileInput(1).setInputFiles(IMAGE);
  await create.imageFileInput(2).setInputFiles(IMAGE);
  await expect(create.creditsCounter).toContainText('insuffisant');

  await create.confirmPropertyOf(1);
  await create.confirmPropertyOf(2);
  await create.title.fill('Titre conservé');
  await create.blocks.textBlockEditor(0).fill('Texte conservé');
  await expect(create.saveButton).toBeEnabled();
  await create.saveButton.click();

  await expect(create.creditsError).toBeVisible();
  await expect(create.title).toHaveValue('Titre conservé');
  await expect(author).toHaveURL(/\/chapters\/create$/);
});

test('edit: saved images do not count, a pick counts, free text save at 0, rejected pick leaves content', async ({ author }) => {
  setAuthorCredits(5);
  const create = new ChapterCreatePage(author);
  await create.goto();
  await create.blocks.goAdvanced();
  await create.blocks.addBlock('image');
  await create.imageFileInput(1).setInputFiles(IMAGE);
  await create.confirmPropertyOf(1);
  await create.title.fill('Chapitre à éditer');
  await create.blocks.textBlockEditor(0).fill('Texte initial');
  await create.saveButton.click();
  await author.waitForLoadState('networkidle');
  await expect(author).not.toHaveURL(/\/create$/);

  const slug = (await author.evaluate(() => location.pathname)).split('/').filter(Boolean).pop()!;
  const edit = new ChapterEditPage(author, STORY.slug, slug);
  await edit.goto();
  await expect(edit.creditsCounter).toContainText('0 image');

  await edit.blocks.addBlock('image');
  await edit.imageFileInput(2).setInputFiles(IMAGE);
  await expect(edit.creditsCounter).toContainText('1 image');

  // Text-only save at balance 0 succeeds.
  await edit.blocks.removeBlock(2);
  setAuthorCredits(0);
  await edit.goto();
  await edit.blocks.textBlockEditor(0).fill('Texte modifié');
  await edit.save();

  // A new pick at balance 0 is rejected; the chapter keeps its content.
  await edit.goto();
  await edit.blocks.addBlock('image');
  await edit.imageFileInput(2).setInputFiles(IMAGE);
  await expect(edit.creditsCounter).toContainText('insuffisant');
  await edit.confirmPropertyOf(2);
  await edit.blocks.textBlockEditor(0).fill('Ne doit pas être sauvé');
  await edit.saveButton.click();
  await expect(edit.creditsError).toBeVisible();
  await edit.goto();
  await expect(edit.blocks.textBlockEditor(0).body).toContainText('Texte modifié');
});
