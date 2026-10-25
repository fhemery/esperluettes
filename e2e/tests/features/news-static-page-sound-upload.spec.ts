import { devices } from '@playwright/test';
import { ChapterEditPage } from '../../pages/ChapterEditPage';
import { probeAudio } from '../../pages/SoundField';
import { shot, SoundBlocksFormPage } from '../../pages/SoundBlocksFormPage';
import { STORY } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — sound blocks in News / StaticPage advanced mode (temporary: deleted at WRAP).
 *
 * Browser-only: Alpine clones of the sound field, playback, the real public
 * player, the mode switch, the chapter palette. Storage, validation and
 * authorisation are feature tests in app/Domains/{News,StaticPage,Editor}/Tests.
 */

const MP3 = 'e2e/fixtures/gift-sound.mp3';
const MP3_SHORT = 'e2e/fixtures/gift-sound-short.mp3';
const TITLE = 'Actualité sonore';
const SLUG = 'actualite-sonore';
const PAGE_TITLE = 'Page sonore';
const PAGE_SLUG = 'page-sonore';

test.describe.configure({ mode: 'serial' });

test('admin adds two sound blocks to a news; each clone works on its own', async ({ admin }) => {
  const form = new SoundBlocksFormPage(admin, 'news');
  await form.gotoCreate();
  await form.fill(TITLE, SLUG, 'published');
  await form.editor.goAdvanced();
  await form.editor.textBlockEditor(0).fill('Introduction');

  await expect(form.editor.paletteButton('audio'), 'palette offers Son').toBeVisible();
  await form.editor.openInsertMenu(0);
  await expect(form.editor.insertMenuButton(0, 'audio'), '+ menu offers Son').toBeVisible();
  await form.editor.insertMenuButton(0, 'audio').click();
  await expect(form.editor.blocks).toHaveCount(2);
  await form.editor.addBlock('audio');

  const a = form.soundField(1);
  const b = form.soundField(2);
  await expect(a.dropZone).toBeVisible();
  await expect(b.dropZone).toBeVisible();
  await shot(admin, 'news-01-two-empty-sound-blocks');

  await a.pick(MP3);
  await expect(a.player, 'first clone previews').toBeVisible();
  await expect(b.dropZone, 'second clone untouched').toBeVisible();
  await expect(b.player).toHaveCount(0);

  await b.pick(MP3_SHORT);
  await expect(b.player, 'second clone previews').toBeVisible();
  await expect(a.player).toBeVisible();

  const pa = await probeAudio(a.player, 2);
  expect(pa.error).toBe(0);
  expect(pa.duration).toBeGreaterThan(10);
  expect(pa.currentTime).toBeGreaterThan(2);
  const pb = await probeAudio(b.player, 1);
  expect(pb.error).toBe(0);
  expect(pb.duration).toBeGreaterThan(5);
  expect(pb.duration, 'distinct files').toBeLessThan(pa.duration);

  await form.caption(1).fill('Légende du premier son');
  await shot(admin, 'news-02-two-sounds-picked');
  await form.submit.click();
  await expect(admin).toHaveURL(/\/admin\/news$/);
  await shot(admin, 'news-03-after-save');
});

test('guest plays both sounds on the published news, caption under the player', async ({ guest }) => {
  const res = await guest.goto(`/news/${SLUG}`);
  expect(res?.status()).toBe(200);
  const players = guest.locator('.news-content audio');
  await expect(players).toHaveCount(2);
  await expect(guest.locator('.news-content figcaption')).toHaveText('Légende du premier son');

  const first = players.first();
  await first.scrollIntoViewIfNeeded();
  const probe = await probeAudio(first, 0);
  expect(probe.error).toBe(0);
  expect(probe.currentTime).toBeGreaterThan(0.3);
  const wFigure = await first.evaluate((el) => [el.getBoundingClientRect().width, el.parentElement!.getBoundingClientRect().width]);
  expect(wFigure[0], 'player fills its container').toBeGreaterThan(wFigure[1]! * 0.95);
  await shot(guest, 'news-04-guest-public');
});

test('admin edits the news: stored preview plays, delete without replacement is refused, replace works', async ({ admin }) => {
  const form = new SoundBlocksFormPage(admin, 'news');
  await form.gotoEdit(TITLE);
  await expect(form.editor.blocks).toHaveCount(3);
  const a = form.soundField(1);
  await expect(a.player, 'stored sound is previewed').toBeVisible();
  const src = await a.player.getAttribute('src');
  expect(src).toMatch(/\/storage\/news\/.+\.mp3$/);
  expect((await probeAudio(a.player, 1)).error).toBe(0);
  await shot(admin, 'news-05-edit-existing');

  await a.deleteButton.click();
  await expect(a.dropZone).toBeVisible();
  await form.submit.click();
  await expect(admin.getByText('Un bloc son doit contenir un fichier.').first()).toBeVisible();
  await expect(form.editor.blocks, 'blocks kept after the error').toHaveCount(3);
  await expect(form.caption(1)).toHaveValue('Légende du premier son');
  await expect(form.editor.textBlockEditor(0).body).toContainText('Introduction');
  await shot(admin, 'news-06-error-no-file');

  // Now pick a replacement and save.
  await form.soundField(1).pick(MP3_SHORT);
  await expect(form.soundField(1).player).toBeVisible();
  await form.submit.click();
  await expect(admin).toHaveURL(/\/admin\/news$/);

  await form.gotoEdit(TITLE);
  const newSrc = await form.soundField(1).player.getAttribute('src');
  expect(newSrc, 'the file was replaced').not.toBe(src);
});

test('a wav and an oversized mp3 give a French error and keep the form', async ({ admin }) => {
  const form = new SoundBlocksFormPage(admin, 'news');
  await form.gotoEdit(TITLE);
  await form.soundField(2).deleteButton.click();
  await form.soundField(2).pickRaw('x.wav', 'audio/wav', Buffer.alloc(2000, 1));
  await form.submit.click();
  await expect(admin.getByText(/MP3/).first()).toBeVisible();
  await expect(form.editor.blocks).toHaveCount(3);
  await shot(admin, 'news-07-error-wav');

  await form.gotoEdit(TITLE);
  await form.soundField(2).deleteButton.click();
  await form.soundField(2).pickRaw('big.mp3', 'audio/mpeg', Buffer.alloc(11 * 1024 * 1024, 1));
  await form.submit.click();
  // Either the client refuses (field error) or the server does.
  const msg = admin.getByText(/10 ?Mo/i);
  await expect(msg.first()).toBeVisible();
  await shot(admin, 'news-08-error-big');
});

test('simple mode is out of reach while a sound block exists', async ({ admin }) => {
  const form = new SoundBlocksFormPage(admin, 'news');
  await form.gotoEdit(TITLE);
  await expect(form.editor.simpleButton).toBeDisabled();
  // Keep only the text + one sound: still disabled.
  await form.editor.removeBlock(2);
  await expect(form.editor.simpleButton).toBeDisabled();
  await form.editor.removeBlock(1);
  await expect(form.editor.simpleButton, 'only text left').toBeEnabled();
});

test('moderator adds a sound to a news', async ({ moderator, guest }) => {
  const form = new SoundBlocksFormPage(moderator, 'news');
  await form.gotoCreate();
  await form.fill('Actualité modérée', 'actualite-moderee', 'published');
  await form.editor.goAdvanced();
  await form.editor.textBlockEditor(0).fill('Texte');
  await form.editor.addBlock('audio');
  await form.soundField(1).pick(MP3_SHORT);
  await expect(form.soundField(1).player).toBeVisible();
  await shot(moderator, 'news-09-moderator');
  await form.submit.click();
  await expect(moderator).toHaveURL(/\/admin\/news$/);
  await guest.goto('/news/actualite-moderee');
  await expect(guest.locator('.news-content audio')).toHaveCount(1);
});

test('image picker of news / static pages never lists the uploaded mp3', async ({ admin }) => {
  for (const scope of ['news', 'static-pages']) {
    const res = await admin.request.get(`/media/library?scope=${scope}`);
    expect(res.status()).toBe(200);
    expect(await res.text()).not.toContain('.mp3');
  }
});

test('admin adds a sound to a static page; guest plays it once published', async ({ admin, guest }) => {
  const form = new SoundBlocksFormPage(admin, 'static-pages');
  await form.gotoCreate();
  await form.fill(PAGE_TITLE, PAGE_SLUG, 'published');
  await form.editor.goAdvanced();
  await form.editor.textBlockEditor(0).fill('Contenu');
  await form.editor.addBlock('audio');
  await form.soundField(1).pick(MP3);
  await expect(form.soundField(1).player).toBeVisible();
  await form.caption(1).fill('Légende de page');
  await shot(admin, 'static-01-form');
  await form.submit.click();
  await expect(admin).toHaveURL(/\/admin\/static-pages$/);

  const res = await guest.goto(`/${PAGE_SLUG}`);
  expect(res?.status()).toBe(200);
  const player = guest.locator('audio');
  await expect(player).toHaveCount(1);
  await expect(guest.locator('figcaption')).toHaveText('Légende de page');
  const probe = await probeAudio(player, 0);
  expect(probe.error).toBe(0);
  expect(probe.currentTime).toBeGreaterThan(0.3);
  await shot(guest, 'static-02-guest-public');
});

test('chapter editor offers no sound block', async ({ author }) => {
  const edit = new ChapterEditPage(author, STORY.slug, STORY.draftChapter.slug);
  await edit.goto();
  await edit.blocks.goAdvanced();
  await expect(edit.blocks.paletteButton('image')).toBeVisible();
  await expect(edit.blocks.paletteButton('audio')).toHaveCount(0);
  await edit.blocks.openInsertMenu(0);
  await expect(edit.blocks.insertMenuButton(0, 'audio')).toHaveCount(0);
  await expect(edit.blocks.root.locator('[data-block-template="audio"]')).toHaveCount(0);
  await shot(author, 'chapter-01-no-sound');
});

test.describe('mobile 375px', () => {
  test.use({ viewport: { width: 375, height: 800 }, userAgent: devices['Pixel 7'].userAgent });

  test('public player and editor sound block fit without horizontal overflow', async ({ guest, admin }) => {
    await guest.goto(`/news/${SLUG}`);
    await expect(guest.locator('.news-content audio').first()).toBeVisible();
    const overflowG = await guest.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflowG, 'public page overflows').toBeLessThanOrEqual(0);
    await shot(guest, 'mobile-01-public');

    const form = new SoundBlocksFormPage(admin, 'news');
    await form.gotoEdit('Actualité modérée');
    await expect(form.soundField(1).player).toBeVisible();
    const overflowA = await admin.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflowA, 'editor overflows').toBeLessThanOrEqual(0);
    await shot(admin, 'mobile-02-editor');
  });
});
