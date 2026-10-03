import { readFileSync } from 'node:fs';
import path from 'node:path';
import { type Page } from '@playwright/test';
import { SecretGiftActivityPage } from '../../pages/SecretGiftActivityPage';
import { probeAudio } from '../../pages/SoundField';
import { GIFT_MEDIA, GIFTS } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE — gift sound on Media (VERIFY of `media-sound-upload`).
 *
 * What only a browser can prove: the Alpine sound field (picker, drop, cancel,
 * delete), that a stored sound actually decodes and seeks in a real player
 * when it comes back through the Range-capable Media stream, the download's
 * file name as the browser saves it, and the gift image next to it.
 * Authorization, validation and status codes are PHP tests
 * (`SaveGiftTest`, `ServeFileTest`, `LegacyGiftSoundMoveTest`).
 *
 * The tests run in order on one activity: each starts from what the previous
 * one saved.
 */

const SAVED = 'Votre cadeau a bien été enregistré !';
const SOUND_ROUTE = /\/calendar\/secret-gift\/\d+\/sound\/\d+$/;

function fixtureBytes(fixture: string): Buffer {
  return readFileSync(path.join(ROOT, fixture));
}

/** Prepare tab, Sound mode, on the active gift `confirmed` gives to `author`. */
async function openSoundMode(page: Page): Promise<SecretGiftActivityPage> {
  const activity = new SecretGiftActivityPage(page, GIFTS.active.slug);
  expect(await activity.goto()).toBe(200);
  await expect(activity.giftTab('prepare')).toHaveAttribute('aria-selected', 'true');
  await activity.giftModeButton('Son').click();
  await expect(activity.soundField.root).toBeVisible();
  return activity;
}

/** A ranged GET from inside the page, i.e. with the page's own session. */
async function rangedFetch(page: Page, url: string): Promise<{ status: number; range: string | null; length: number }> {
  return page.evaluate(async (url) => {
    const res = await fetch(url, { headers: { Range: 'bytes=0-9' } });
    return { status: res.status, range: res.headers.get('Content-Range'), length: (await res.arrayBuffer()).byteLength };
  }, url);
}

test.describe.configure({ mode: 'serial' });

test('giver: pick, cancel, drop, save — the saved sound plays and seeks', async ({ confirmed }) => {
  const errors: string[] = [];
  confirmed.on('pageerror', (e) => errors.push(e.message));
  confirmed.on('console', (m) => { if (m.type() === 'error' || m.type() === 'warning') errors.push(m.text()); });

  const activity = await openSoundMode(confirmed);
  const field = activity.soundField;

  // --- empty
  await expect(field.dropZone).toBeVisible();
  await expect(field.dropZone).toContainText('Glissez-déposez un fichier audio');
  await expect(field.dropZone).toContainText('Format accepté : MP3');
  await expect(field.dropZone).toContainText('Taille maximale : 10 Mo');
  await expect(field.root).toContainText('Téléverser un fichier audio');
  await expect(field.root).toContainText('MP3, 10 Mo maximum');
  await expect(field.player).toHaveCount(0);
  await activity.evidence('01-prepare-empty', activity.giftForm);

  // --- file picker: preview with name and size, playable; × back to empty
  await field.pick(GIFT_MEDIA.sound.path);
  await expect(field.player).toBeVisible();
  await expect(field.fileInfo).toContainText('gift-sound.mp3');
  await expect(field.fileInfo).toContainText('KB');
  await expect(field.cancelButton).toBeVisible();
  await expect(field.deleteButton).toBeHidden();
  const picked = await probeAudio(field.player, 5);
  expect(picked.duration).toBeCloseTo(GIFT_MEDIA.sound.seconds, 0);
  await activity.evidence('02-prepare-picked', activity.giftForm);

  await field.cancelButton.click();
  await expect(field.dropZone).toBeVisible();
  await expect(field.player).toHaveCount(0);
  await activity.evidence('03-prepare-cancelled', activity.giftForm);
  expect(await field.fileInput.evaluate((el: HTMLInputElement) => el.files?.length ?? 0)).toBe(0);

  // --- drag & drop: a non-audio file is ignored, an mp3 is taken
  await field.drop('notes.txt', 'text/plain', Buffer.from('pas un son'));
  await expect(field.dropZone).toBeVisible();
  await expect(field.player).toHaveCount(0);

  await field.dropFixture(GIFT_MEDIA.sound.path, 'audio/mpeg');
  await expect(field.player).toBeVisible();
  await expect(field.fileInfo).toContainText('gift-sound.mp3');
  expect(await field.fileInput.evaluate((el: HTMLInputElement) => el.files?.[0]?.name ?? '')).toBe('gift-sound.mp3');
  await activity.evidence('04-prepare-dropped', activity.giftForm);

  // --- save: flash, then the reloaded form plays the stored sound from the sound route
  await activity.saveGiftButton.click();
  await expect(activity.flash(SAVED)).toBeVisible();
  await activity.evidence('05-prepare-saved-flash');

  const saved = await openSoundMode(confirmed);
  await expect(saved.soundField.player).toHaveAttribute('src', SOUND_ROUTE);
  await expect(saved.soundField.deleteButton).toBeVisible();
  await expect(saved.soundField.cancelButton).toBeHidden();

  const played = await probeAudio(saved.soundField.player, 12);
  expect(played.error).toBe(0);
  expect(played.duration).toBeCloseTo(GIFT_MEDIA.sound.seconds, 0);
  expect(played.currentTime).toBeGreaterThanOrEqual(12);
  expect(played.currentTime).toBeLessThan(14);
  await saved.evidence('06-prepare-reloaded-seeked', saved.giftForm);

  const src = (await saved.soundField.player.getAttribute('src'))!;
  const ranged = await rangedFetch(confirmed, src);
  expect(ranged).toEqual({ status: 206, range: `bytes 0-9/${fixtureBytes(GIFT_MEDIA.sound.path).length}`, length: 10 });

  expect(errors).toEqual([]);
});

test('giver: replacing the sound plays the new one', async ({ confirmed }) => {
  const activity = await openSoundMode(confirmed);
  await activity.soundField.pick(GIFT_MEDIA.shortSound.path);
  await expect(activity.soundField.player).toBeVisible();
  await activity.saveGiftButton.click();
  await expect(activity.flash(SAVED)).toBeVisible();

  const replaced = await openSoundMode(confirmed);
  const probe = await probeAudio(replaced.soundField.player, 4);
  expect(probe.duration).toBeCloseTo(GIFT_MEDIA.shortSound.seconds, 0);
  await replaced.evidence('07-prepare-replaced', replaced.giftForm);
});

test('giver: a non-mp3 file is refused under the field and nothing changes', async ({ confirmed }) => {
  const activity = await openSoundMode(confirmed);
  await activity.soundField.pickRaw('gift.wav', 'audio/wav', Buffer.from('RIFF....WAVEfmt not really a wav'));
  await activity.saveGiftButton.click();

  // The error must be visible, i.e. the form must come back on the Sound mode.
  const error = activity.soundField.error;
  await expect(error).toHaveText('Le fichier audio doit être au format MP3.');
  await expect(error).toBeVisible();
  await activity.evidence('08-prepare-invalid', activity.giftForm);

  const after = await openSoundMode(confirmed);
  const probe = await probeAudio(after.soundField.player, 1);
  expect(probe.duration).toBeCloseTo(GIFT_MEDIA.shortSound.seconds, 0);
});

test('giver: at 390 px the drop zone and the player fit', async ({ confirmed }) => {
  await confirmed.setViewportSize({ width: 390, height: 844 });
  const activity = await openSoundMode(confirmed);

  await expect(activity.soundField.player).toBeVisible();
  expect(await activity.clippedElements()).toEqual([]);
  const bin = await activity.soundField.deleteButton.boundingBox();
  expect(bin).not.toBeNull();
  expect(bin!.width).toBeGreaterThanOrEqual(28);
  expect(bin!.x + bin!.width).toBeLessThanOrEqual(390);
  await activity.soundField.root.scrollIntoViewIfNeeded();
  await activity.evidence('09-mobile-player');

  await activity.soundField.pick(GIFT_MEDIA.sound.path);
  await expect(activity.soundField.cancelButton).toBeVisible();
  expect(await activity.clippedElements()).toEqual([]);
  await activity.soundField.cancelButton.click();

  await activity.soundField.deleteButton.click();
  await expect(activity.soundField.dropZone).toBeVisible();
  expect(await activity.clippedElements()).toEqual([]);
  await activity.evidence('10-mobile-empty');
});

test('giver: removing the sound empties the field and the old url stops answering', async ({ confirmed }) => {
  const activity = await openSoundMode(confirmed);
  const oldSrc = (await activity.soundField.player.getAttribute('src'))!;

  await activity.soundField.deleteButton.click();
  await expect(activity.soundField.dropZone).toBeVisible();
  await expect(activity.soundField.removeFlag).toHaveValue('true');
  await activity.saveGiftButton.click();
  await expect(activity.flash(SAVED)).toBeVisible();

  // No text, image or sound left: the form falls back to the Text mode.
  const after = new SecretGiftActivityPage(confirmed, GIFTS.active.slug);
  await after.goto();
  await expect(after.giftModeButton('Texte')).toHaveClass(/bg-primary/);
  await after.giftModeButton('Son').click();
  await expect(after.soundField.dropZone).toBeVisible();
  await expect(after.soundField.player).toHaveCount(0);
  await after.evidence('11-prepare-removed', after.giftForm);

  const res = await confirmed.request.get(oldSrc);
  expect(res.status()).toBe(404);
});

test('giver: the gift image still previews and comes back after save', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.active.slug);
  await activity.goto();
  await activity.giftModeButton('Image').click();
  await activity.imageFileInput.setInputFiles(path.join(ROOT, GIFT_MEDIA.image.path));
  await expect(activity.imagePreview).toBeVisible();
  await activity.saveGiftButton.click();
  await expect(activity.flash(SAVED)).toBeVisible();

  const after = new SecretGiftActivityPage(confirmed, GIFTS.active.slug);
  await after.goto();
  await expect(after.imagePreview).toBeVisible();
  await expect(after.imagePreview).toHaveAttribute('src', /\/calendar\/secret-gift\/\d+\/image\/\d+$/);
  await expect.poll(() => after.imagePreview.evaluate((img: HTMLImageElement) => img.naturalWidth)).toBe(GIFT_MEDIA.image.width);
  await after.evidence('12-prepare-image-reloaded', after.giftForm);
});

test('recipient, ended: the sound plays, seeks, and downloads under its name', async ({ confirmed }) => {
  const soundStatuses: number[] = [];
  confirmed.on('response', (r) => { if (SOUND_ROUTE.test(new URL(r.url()).pathname)) soundStatuses.push(r.status()); });

  const activity = new SecretGiftActivityPage(confirmed, GIFTS.ended.slug);
  expect(await activity.goto()).toBe(200);
  await activity.giftTab('received').click();
  await expect(activity.giftTab('received')).toHaveAttribute('aria-selected', 'true');

  // --- player
  await expect(activity.revealSound).toBeVisible();
  const probe = await probeAudio(activity.revealSound, 15);
  expect(probe.error).toBe(0);
  expect(probe.duration).toBeCloseTo(GIFT_MEDIA.sound.seconds, 0);
  expect(probe.currentTime).toBeGreaterThanOrEqual(15);
  expect(soundStatuses.length).toBeGreaterThan(0);
  expect(soundStatuses.every((s) => s === 200 || s === 206)).toBe(true);
  console.log(`player sound responses: ${soundStatuses.join(',')}`);

  const src = (await activity.revealSound.getAttribute('src'))!;
  const bytes = fixtureBytes(GIFT_MEDIA.sound.path);
  expect(await rangedFetch(confirmed, src)).toEqual({ status: 206, range: `bytes 0-9/${bytes.length}`, length: 10 });

  // --- download
  const assignmentId = src.split('/').pop();
  const [download] = await Promise.all([confirmed.waitForEvent('download'), activity.revealSoundDownload.click()]);
  expect(download.suggestedFilename()).toMatch(new RegExp(`^gift-audio-\\d+-${assignmentId}\\.mp3$`));
  const saved = readFileSync((await download.path())!);
  expect(saved.equals(bytes)).toBe(true);

  // --- the image next to it
  await expect.poll(() => activity.revealImage.evaluate((img: HTMLImageElement) => img.naturalWidth)).toBe(GIFT_MEDIA.image.width);
  await activity.evidence('13-reveal-ended', activity.giftReceivedPanel);
  const [imageDownload] = await Promise.all([confirmed.waitForEvent('download'), activity.revealImageDownload.click()]);
  expect(imageDownload.suggestedFilename()).toMatch(new RegExp(`^gift-image-\\d+-${assignmentId}\\.png$`));
});
