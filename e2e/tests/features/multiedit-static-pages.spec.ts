import { StaticPageAdminPage } from '../../pages/StaticPageAdminPage';
import { STATIC_PAGE } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — MultiEdit on StaticPage admin (temporary until WRAP).
 *
 * Shared Alpine block behaviour (add / reorder / delete / Simple re-sync) is
 * already guarded in `e2e/tests/core/multi-editor.spec.ts` on chapters. These
 * specs only cover what is StaticPage-specific and client-side: the admin form
 * boots MultiEdit with the editorial toolbar, Simple↔Avancé conversion keeps
 * the body, an image block wires the Media field (alt, caption, reuse picker
 * scoped to `static-pages`), the Simple control’s disabled tooltip appears, and
 * the controls stay usable at a phone-width viewport.
 *
 * Persistence, validation, auth, field order and public HTML are covered by
 * StaticPage feature tests from BUILD — they do not belong here.
 */

const TO_SIMPLE_DISABLED =
  'Retirez les images et ne gardez qu’un seul bloc de texte pour revenir en mode Simple.';

/** Minimal 1×1 PNG — enough for the image-field preview path. */
const TINY_PNG = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
  'base64',
);

test('create form boots MultiEdit in Simple with the editorial toolbar', async ({ admin }) => {
  const form = new StaticPageAdminPage(admin);
  await form.gotoCreate();

  expect(await form.blocks.mode()).toBe('simple');
  await expect(form.blocks.simple.body).toBeVisible();
  // `header` is part of the editorial preset and absent from `default`.
  // Quill keeps the <select> hidden and surfaces a .ql-picker for the UI.
  await expect(
    form.blocks.simple.toolbar.locator('.ql-header.ql-picker'),
    'editorial toolbar never booted (Quill / assets)',
  ).toBeVisible();
  await expect(form.blocks.simple.toolbar.locator('button.ql-link')).toBeVisible();
});

test('switching to Avancé turns the Simple body into exactly one text block', async ({ admin }) => {
  const form = new StaticPageAdminPage(admin);
  await form.gotoEdit();

  expect(await form.blocks.mode()).toBe('simple');
  expect(await form.blocks.simple.text()).toContain(STATIC_PAGE.body);

  await form.blocks.goAdvanced();

  expect(await form.blocks.mode()).toBe('advanced');
  await expect(form.blocks.blocks).toHaveCount(1);
  expect(await form.blocks.textBlockEditor(0).text()).toContain(STATIC_PAGE.body);
});

test('an image block exposes alt, caption and the static-pages reuse picker', async ({ admin }) => {
  const form = new StaticPageAdminPage(admin);
  await form.gotoCreate();
  await form.blocks.goAdvanced();
  await form.blocks.addBlock('image');

  await expect(form.blocks.imageBlocks).toHaveCount(1);
  await expect(form.blocks.imageBlockAlt(0)).toBeVisible();
  await expect(form.blocks.imageBlockCaption(0)).toBeVisible();
  // Required marker next to the alt label (Media image-field with altRequired).
  await expect(form.blocks.imageBlock(0).locator('label', { hasText: 'Texte alternatif' })).toContainText('*');

  await form.blocks.imageBlock(0).locator('input[type="file"]').setInputFiles({
    name: 'block.png',
    mimeType: 'image/png',
    buffer: TINY_PNG,
  });
  await expect(form.blocks.imageBlock(0).locator('img[alt^="Aperçu"]')).toBeVisible();

  await form.blocks.openImageLibrary(0);
  // openImageLibrary already asserted the modal heading; the empty state lives in an
  // Alpine x-if that may leave sibling pickers' copy in the DOM as hidden.
  const libraryModal = admin.locator('div.fixed').filter({ hasText: /Bibliothèque d.images/, visible: true });
  await expect(libraryModal.getByText(/Aucune image disponible pour le moment/)).toBeVisible();

  await expect(form.blocks.simpleButton).toBeDisabled();
  await expect(form.blocks.simpleButton).toHaveAttribute('title', TO_SIMPLE_DISABLED);
});

test('Simple is disabled with two text blocks and comes back after delete', async ({ admin }) => {
  const form = new StaticPageAdminPage(admin);
  await form.gotoEdit();
  await form.blocks.goAdvanced();
  await form.blocks.addBlock('text');

  await expect(form.blocks.simpleButton).toBeDisabled();
  await expect(form.blocks.simpleButton).toHaveAttribute('title', TO_SIMPLE_DISABLED);

  await form.blocks.removeBlock(1);

  await expect(form.blocks.simpleButton).toBeEnabled();
  await form.blocks.goSimple();
  expect(await form.blocks.mode()).toBe('simple');
  expect(await form.blocks.simple.text()).toContain(STATIC_PAGE.body);
});

test('mode toggle and block palette stay usable on a phone-width viewport', async ({ admin }) => {
  await admin.setViewportSize({ width: 375, height: 812 });
  const form = new StaticPageAdminPage(admin);
  await form.gotoCreate();

  await expect(form.blocks.advancedButton).toBeVisible();
  await form.blocks.goAdvanced();
  await expect(form.blocks.root.locator('button[x-on\\:click="appendBlock(\'text\')"]')).toBeVisible();
  await expect(form.blocks.root.locator('button[x-on\\:click="appendBlock(\'image\')"]')).toBeVisible();

  await form.blocks.addBlock('text');
  await expect(form.blocks.textBlockEditor(1).body).toBeVisible();
  await form.blocks.textBlockEditor(1).fill('Bloc mobile');
  expect(await form.blocks.textBlockEditor(1).text()).toBe('Bloc mobile');
});
