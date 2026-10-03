import { devices, type Browser, type Page } from '@playwright/test';
import path from 'node:path';
import { ChapterAnnotations } from '../../pages/ChapterAnnotations';
import { ChapterPage } from '../../pages/ChapterPage';
import { CommentThread } from '../../pages/CommentThread';
import { LoginPage } from '../../pages/LoginPage';
import { NewsArticlePage } from '../../pages/NewsArticlePage';
import { ACCOUNTS, COAUTHORED_STORY, type RoleName, STORY, storageStatePath } from '../../support/fixtures';
import { ROOT } from '../../support/sail';
import { expect, test } from '../../support/test';

/**
 * FEATURE (VERIFY of the `annotations` task) — the Visual QA checklist rows a
 * PHP test cannot settle: the selection toolbar, the capture form (Quill), the
 * localStorage drafts and their banner / pop-up, the server pop-up's Alpine
 * actions, and the 375 px layout. The happy path itself lives in
 * `core/chapter-annotations.spec.ts`; this file covers the edges around it.
 *
 * Serial: the later tests read the comments the earlier ones publish.
 *
 * Chapters used (none touched by the core spec, which owns `chapitre-compte-5`):
 * - `chapitre-simple-3`: `confirmed` drafts, fails, then publishes; others look.
 * - `chapitre-avance-4`: cross-block selection; mobile run as `user`.
 * - `chapitre-illustre-7`: caption; two users in one browser; Quote regression.
 * - `chapitre-coecrit-6` (author + co-author `confirmed`, beta `moderator`):
 *   `user` publishes; authors share the processed flag; moderator deletes.
 */

test.describe.configure({ mode: 'serial', timeout: 90_000 });

const SIMPLE = STORY.simpleChapter;
const ADVANCED = STORY.advancedChapter;
const ILLUSTRATED = STORY.illustratedChapter;

const ROOT_BODY =
  'Un commentaire de vérification assez long pour franchir le seuil des cent quarante ' +
  'caractères exigés pour un commentaire racine, publié avec ses annotations.';
const SHORT_ROOT = 'Trop court pour passer.';

const ROLE_PAGE_ARGS = (role: RoleName) => ({ storageState: path.join(ROOT, storageStatePath(role)) });

/** Ids of the comments the serial tests publish. */
const published = { simple: 0, coauthored: 0 };

test('capture: toolbar, form, saving rules, too-long selection, cross-block and caption', async ({ confirmed }) => {
  const annotations = new ChapterAnnotations(confirmed);

  await test.step('0 drafts: no banner above the root form', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
    const thread = new CommentThread(confirmed);
    await chapter.goto();
    await expect(thread.rootForm).toBeVisible();
    await expect(annotations.banner).toBeHidden();
  });

  await test.step('selection in one text block: « Citer » then « Annoter » on one row', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
    await chapter.selectText(chapter.textBlocks.first().locator('p').first());
    await expect(annotations.quoteButton).toBeVisible();
    await expect(annotations.annotateButton).toBeVisible();
    const cite = (await annotations.quoteButton.boundingBox())!;
    const annotate = (await annotations.annotateButton.boundingBox())!;
    // Same row: the icons differ in height, so compare vertical centres.
    expect(Math.abs(cite.y + cite.height / 2 - (annotate.y + annotate.height / 2))).toBeLessThan(3);
    expect(cite.x + cite.width).toBeLessThanOrEqual(annotate.x);
    await annotations.evidence('01-toolbar-citer-annoter');
  });

  await test.step('capture form: under the selection, inline toolbar, /1000, Save + Cancel', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
    const paragraph = (await chapter.textBlocks.first().locator('p').first().boundingBox())!;
    await annotations.annotateButton.click();
    await expect(annotations.captureForm).toBeVisible();
    await annotations.captureEditor.waitUntilReady();

    const form = (await annotations.captureForm.boundingBox())!;
    expect(form.y).toBeGreaterThan(paragraph.y);
    expect(form.y).toBeLessThan(paragraph.y + paragraph.height + 60);

    // Bold, italic, emoji — plus the reset button every rich-text toolbar ends with.
    const buttons = await annotations.captureEditor.toolbar
      .locator('.ql-formats > button')
      .evaluateAll((els) => els.map((el) => el.className));
    expect(buttons).toEqual(['ql-bold', 'ql-italic', 'ql-clean', 'ql-emoji']);
    await expect(annotations.captureEditor.counter).toHaveText('0');
    await expect(annotations.captureEditor.root).toContainText('0 / 1000');
    await expect(annotations.captureSave).toBeVisible();
    await expect(annotations.captureCancel).toBeVisible();
    await annotations.evidence('02-capture-form');
  });

  await test.step('clicking elsewhere closes the form without saving', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
    await annotations.captureEditor.type('Jamais enregistrée.');
    await chapter.textBlocks.first().locator('p').last().click();
    await expect(annotations.captureForm).toBeHidden();
    expect(await annotations.storedDrafts()).toEqual({});
    await expect(annotations.banner).toBeHidden();
  });

  await test.step('Ctrl/Cmd+Enter saves', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
    await chapter.selectText(chapter.textBlocks.first().locator('p').first());
    await annotations.annotateButton.click();
    await annotations.captureEditor.waitUntilReady();
    await annotations.captureEditor.fill('Première annotation, au clavier.');
    await confirmed.keyboard.press('ControlOrMeta+Enter');
    await expect(annotations.captureForm).toBeHidden();
    await expect(annotations.banner).toContainText('1 annotation, écrivez votre commentaire pour la sauvegarder');
  });

  await test.step('cross-block selection (Advanced chapter): inline error, Save disabled', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, ADVANCED.slug);
    await chapter.goto();
    await chapter.selectAcross(chapter.textBlocks.nth(0).locator('p').last(), chapter.textBlocks.nth(1).locator('p').first());
    await annotations.annotateButton.click();
    await expect(annotations.captureForm).toBeVisible();
    await expect(annotations.captureError).toHaveText('La sélection doit rester dans un même bloc de texte.');
    await expect(annotations.captureSave).toBeDisabled();
    await annotations.captureSave.scrollIntoViewIfNeeded();
    await annotations.evidence('04-cross-block');
    await annotations.captureCancel.click();
    expect(await annotations.storedDrafts()).toEqual(
      expect.not.objectContaining({ [`comment-drafts:${await userIdOf(confirmed)}:chapter:4`]: expect.anything() }),
    );
  });

  await test.step('image caption: a selection touching it shows no toolbar', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, ILLUSTRATED.slug);
    await chapter.goto();
    const caption = chapter.content.getByText(ILLUSTRATED.caption);
    await chapter.selectText(caption);
    await expect(annotations.toolbar).toBeHidden();

    await chapter.selectAcross(chapter.textBlocks.first().locator('p').first(), caption);
    await expect(annotations.toolbar).toBeHidden();
    await annotations.evidence('05-caption-no-toolbar');

    // Sanity: text below the image still offers both actions.
    await chapter.selectText(chapter.textBlocks.last().locator('p').first());
    await expect(annotations.annotateButton).toBeVisible();
    await expect(annotations.quoteButton).toBeVisible();
  });
});

test('drafts: three, pop-up edit round trip, reload, failed publish, publish', async ({ confirmed }) => {
  const chapter = new ChapterPage(confirmed, STORY.slug, SIMPLE.slug);
  const annotations = new ChapterAnnotations(confirmed);
  const thread = new CommentThread(confirmed);
  const paragraphs = chapter.textBlocks.first().locator('p');

  await test.step('three drafts: banner text and « Voir les annotations »', async () => {
    // Each test has its own browser context: the previous test's draft is not here.
    await chapter.goto();
    await expect(annotations.banner).toBeHidden();
    for (const i of [0, 1, 2]) {
      await chapter.selectText(paragraphs.nth(i));
      await annotations.capture(`Annotation numéro ${i + 1}.`);
    }
    await expect(annotations.banner).toContainText('3 annotations, écrivez votre commentaire pour les sauvegarder');
    await expect(annotations.banner.getByRole('button')).toHaveText(/Voir les annotations/);
    await annotations.banner.scrollIntoViewIfNeeded();
    await annotations.evidence('06-banner-3-drafts');
  });

  await test.step('pop-up: quote + body + Modifier / Supprimer on each row', async () => {
    await annotations.openDrafts();
    await expect(annotations.draftRows).toHaveCount(3);
    for (let i = 0; i < 3; i++) {
      const row = annotations.draftRows.nth(i);
      await expect(row.locator('blockquote')).not.toBeEmpty();
      await expect(row.getByRole('button', { name: 'Modifier', exact: true })).toBeVisible();
      await expect(row.getByRole('button', { name: 'Supprimer', exact: true })).toBeVisible();
    }
    await expect(annotations.draftRows.nth(1)).toContainText('Annotation numéro 2.');
    await annotations.evidence('07-drafts-popup');
  });

  await test.step('Modifier → capture form prefilled → saved body shows in the pop-up', async () => {
    await annotations.editDraft(annotations.draftRows.filter({ hasText: 'Annotation numéro 2.' }));
    await expect(annotations.draftsDialog).toBeHidden();
    expect(await annotations.captureEditor.text()).toBe('Annotation numéro 2.');
    await annotations.captureEditor.fill('Annotation numéro 2, corrigée.');
    await annotations.captureSave.click();
    await expect(annotations.captureForm).toBeHidden();
    await expect(annotations.banner).toContainText('3 annotations');
    await annotations.openDrafts();
    await expect(annotations.draftRows.nth(1)).toContainText('Annotation numéro 2, corrigée.');
    await confirmed.keyboard.press('Escape');
    await expect(annotations.draftsDialog).toBeHidden();
  });

  await test.step('reload keeps the drafts and the in-progress root comment', async () => {
    await thread.rootEditor.waitUntilReady();
    await thread.rootEditor.fill(SHORT_ROOT);
    await expect.poll(() => annotations.storedRootBody()).toContain(SHORT_ROOT);
    await confirmed.reload();
    await expect(annotations.banner).toContainText('3 annotations');
    await thread.rootEditor.waitUntilReady();
    expect(await thread.rootEditor.text()).toBe(SHORT_ROOT);
  });

  await test.step('failed publish (root under 140, forced past the disabled button): error, drafts and body kept', async () => {
    await expect(thread.rootSubmit).toBeDisabled();
    await thread.rootForm.evaluate((form: HTMLFormElement) => form.requestSubmit());
    await confirmed.waitForLoadState('networkidle');
    await expect(thread.rootForm.locator('.text-red-600').first()).toBeVisible();
    await expect(annotations.banner).toContainText('3 annotations');
    await thread.rootEditor.waitUntilReady();
    expect(await thread.rootEditor.text()).toBe(SHORT_ROOT);
    expect(Object.values(await annotations.storedDrafts()).flat()).toHaveLength(3);
    await annotations.banner.scrollIntoViewIfNeeded();
    await annotations.evidence('08-publish-failure');
  });

  await test.step('publish: root + remaining 2 annotations; banner gone; « 2 annotations »', async () => {
    await annotations.openDrafts();
    await annotations.deleteDraft(annotations.draftRows.first());
    await expect(annotations.draftRows).toHaveCount(2);
    await confirmed.keyboard.press('Escape');

    const key = `comment-drafts:${await userIdOf(confirmed)}:chapter:3`;
    published.simple = await thread.postRoot(ROOT_BODY);
    await expect(annotations.banner).toBeHidden();
    expect(Object.keys(await annotations.storedDrafts())).not.toContain(key);
    await thread.scrollToBottom();
    await expect(annotations.countLabel(thread.item(published.simple))).toHaveText('2 annotations');
    await thread.item(published.simple).scrollIntoViewIfNeeded();
    await annotations.evidence('09-published-2-annotations');
  });

  await test.step('reader who already commented: « Citer » only, no banner', async () => {
    await chapter.goto();
    await chapter.selectText(paragraphs.first());
    await expect(annotations.quoteButton).toBeVisible();
    await expect(annotations.annotateButton).toHaveCount(0);
    await expect(annotations.captureForm).toHaveCount(0);
    await expect(annotations.banner).toHaveCount(0);
    await annotations.evidence('10-commented-citer-only');
  });

  await test.step("commenter's pop-up: own annotations, read-only, no processed marker", async () => {
    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(published.simple));
    await expect(annotations.serverRows).toHaveCount(2);
    await expect(annotations.serverRows.first().locator('blockquote')).not.toBeEmpty();
    await expect(annotations.serverDialog.locator('li').getByRole('button')).toHaveCount(0);
    await expect(annotations.serverDialog.getByTestId('annotation-processed').filter({ visible: true })).toHaveCount(0);
    await annotations.evidence('11-commenter-popup');
  });
});

test('other reader and guest see no annotation UI on a commented chapter', async ({ user, guest }) => {
  await test.step('other reader: the comment, but no « N annotations »', async () => {
    const chapter = new ChapterPage(user, STORY.slug, SIMPLE.slug);
    const thread = new CommentThread(user);
    const annotations = new ChapterAnnotations(user);
    await chapter.goto();
    await thread.scrollToBottom();
    await expect(thread.item(published.simple)).toBeVisible();
    await expect(annotations.allCountButtons).toHaveCount(0);
  });

  await test.step('guest: no toolbar, no banner, no button, no form', async () => {
    const chapter = new ChapterPage(guest, STORY.slug, SIMPLE.slug);
    const thread = new CommentThread(guest);
    const annotations = new ChapterAnnotations(guest);
    await chapter.goto();
    await chapter.selectText(chapter.textBlocks.first().locator('p').first());
    await guest.waitForTimeout(200);
    await expect(annotations.toolbar).toBeHidden();
    // Guests get the members-only notice instead of the comments.
    await thread.scrollToBottom();
    await expect(thread.root).toBeVisible();
    await expect(thread.items).toHaveCount(0);
    await expect(annotations.banner).toHaveCount(0);
    await expect(annotations.allCountButtons).toHaveCount(0);
    await expect(annotations.captureForm).toHaveCount(0);
    await annotations.evidence('12-guest');
  });
});

test('co-authored chapter: authors share the processed flag; moderator delete decrements', async ({
  user,
  author,
  confirmed,
  moderator,
}) => {
  const path = [COAUTHORED_STORY.slug, COAUTHORED_STORY.chapter.slug] as const;

  await test.step('selection over 500 chars: « Sélection trop longue », no action', async () => {
    const chapter = new ChapterPage(user, ...path);
    const annotations = new ChapterAnnotations(user);
    await chapter.goto();
    await chapter.selectText(chapter.textBlocks.first().locator('p').last());
    expect(await user.evaluate(() => window.getSelection()!.toString().trim().length)).toBeGreaterThan(500);
    await expect(annotations.toolbarTooLong).toBeVisible();
    await expect(annotations.toolbarTooLong).toHaveText('Sélection trop longue');
    await expect(annotations.annotateButton).toBeHidden();
    await annotations.evidence('03-too-long');
  });

  await test.step('reader publishes two annotations', async () => {
    const chapter = new ChapterPage(user, ...path);
    const annotations = new ChapterAnnotations(user);
    const thread = new CommentThread(user);
    await chapter.goto();
    const paragraph = chapter.textBlocks.first().locator('p').first();
    await chapter.selectText(paragraph, 10);
    await annotations.capture('Annotation co-écrite A.');
    await chapter.selectText(paragraph);
    await annotations.capture('Annotation co-écrite B.');
    published.coauthored = await thread.postRoot(ROOT_BODY);
    await thread.scrollToBottom();
    await expect(annotations.countLabel(thread.item(published.coauthored))).toHaveText('2 annotations');
  });

  await test.step('author marks A processed', async () => {
    const annotations = await openPopup(author, ...path);
    const row = annotations.serverRows.filter({ hasText: 'Annotation co-écrite A.' });
    await annotations.markProcessedButton(row).click();
    await expect(annotations.processedMarker(row)).toBeVisible();
    await annotations.evidence('13-author-marked');
  });

  await test.step('commenter never sees the flag, even while A is processed', async () => {
    const annotations = await openPopup(user, ...path);
    await expect(annotations.serverRows).toHaveCount(2);
    await expect(annotations.serverDialog.getByTestId('annotation-processed').filter({ visible: true })).toHaveCount(0);
  });

  await test.step('co-author sees A processed and unmarks it; author sees the change', async () => {
    const annotations = await openPopup(confirmed, ...path);
    const row = annotations.serverRows.filter({ hasText: 'Annotation co-écrite A.' });
    await expect(annotations.processedMarker(row)).toBeVisible();
    await annotations.evidence('14-coauthor-sees-processed');
    await annotations.markUnprocessedButton(row).click();
    await expect(annotations.processedMarker(row)).toBeHidden();

    const authorView = await openPopup(author, ...path);
    await expect(authorView.processedMarker(authorView.serverRows.filter({ hasText: 'Annotation co-écrite A.' }))).toBeHidden();
  });

  await test.step('moderator (also beta reader here) deletes one: « 2 » → « 1 annotation »', async () => {
    const annotations = await openPopup(moderator, ...path);
    const thread = new CommentThread(moderator);
    await annotations.deleteButton(annotations.serverRows.filter({ hasText: 'Annotation co-écrite B.' })).click();
    await expect(annotations.serverRows).toHaveCount(1);
    await annotations.evidence('15-moderator-deleted-one');
    await moderator.keyboard.press('Escape');
    await expect(annotations.countLabel(thread.item(published.coauthored))).toHaveText('1 annotation');
  });
});

test('two users in one browser never see each other’s drafts', async ({ guest }) => {
  const login = new LoginPage(guest);
  const chapter = new ChapterPage(guest, STORY.slug, ILLUSTRATED.slug);
  const annotations = new ChapterAnnotations(guest);
  const paragraph = () => chapter.textBlocks.first().locator('p').first();

  await test.step('user: one draft, delete it from the pop-up → banner hidden; then one kept', async () => {
    await login.loginAs(ACCOUNTS.user);
    await chapter.goto();
    await chapter.selectText(paragraph());
    await annotations.capture('Brouillon de user.');
    await expect(annotations.banner).toContainText('1 annotation');
    await annotations.openDrafts();
    await annotations.deleteDraft(annotations.draftRows.first());
    await expect(annotations.draftRows).toHaveCount(0);
    await guest.keyboard.press('Escape');
    await expect(annotations.banner).toBeHidden();

    await chapter.selectText(paragraph());
    await annotations.capture('Brouillon de user.');
    await expect(annotations.banner).toContainText('1 annotation');
  });

  await test.step('confirmed, same browser: none of user’s drafts', async () => {
    await login.logout();
    await login.loginAs(ACCOUNTS.confirmed);
    await chapter.goto();
    await expect(annotations.banner).toBeHidden();
    const stored = await annotations.storedDrafts();
    expect(Object.keys(stored)).toHaveLength(1); // user's, still on this device
    expect(Object.keys(stored)[0]).not.toContain(`:${await userIdOf(guest)}:`);

    await chapter.selectText(paragraph());
    await annotations.capture('Brouillon de confirmed.');
    await expect(annotations.banner).toContainText('1 annotation');
    await annotations.openDrafts();
    await expect(annotations.draftRows).toHaveCount(1);
    await expect(annotations.draftRows).toContainText('Brouillon de confirmed.');
    await annotations.evidence('16-second-user-own-draft-only');
  });
});

test('Quote unchanged next to « Annoter »: tint, panel, author heat', async ({ confirmed, author }) => {
  await test.step('reader: « Citer » → mini-form → tint → panel', async () => {
    const chapter = new ChapterPage(confirmed, STORY.slug, ILLUSTRATED.slug);
    const annotations = new ChapterAnnotations(confirmed);
    await chapter.goto();
    const before = await chapter.quoteHighlights.count();
    await chapter.selectText(chapter.textBlocks.last().locator('p').first(), 11);
    await annotations.quoteButton.click();
    await expect(chapter.quoteMiniForm).toBeVisible();
    await chapter.quoteMiniForm.getByRole('button', { name: 'Enregistrer' }).click();
    await expect(chapter.quoteMiniForm).toBeHidden();
    await expect(chapter.quoteHighlights).toHaveCount(before + 1);
    await chapter.quoteHighlights.last().click();
    await expect(chapter.quotePanel).toBeVisible();
    await annotations.evidence('17-quote-panel');
  });

  await test.step('author: heat toggle tints the quoted passages', async () => {
    const chapter = new ChapterPage(author, STORY.slug, SIMPLE.slug);
    const annotations = new ChapterAnnotations(author);
    await chapter.goto();
    await chapter.heatToggle.click();
    await expect(chapter.heatMarks.first()).toBeVisible();
    await annotations.evidence('18-author-heat');
  });
});

test('news article: comment list as before, no annotation UI', async ({ confirmed }) => {
  const article = new NewsArticlePage(confirmed);
  const annotations = new ChapterAnnotations(confirmed);
  await article.goto();
  await expect(article.comments.rootForm).toBeVisible();
  await article.content.locator('p').first().evaluate((el) => {
    const range = document.createRange();
    range.selectNodeContents(el);
    window.getSelection()!.removeAllRanges();
    window.getSelection()!.addRange(range);
    el.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
  });
  await confirmed.waitForTimeout(200);
  await expect(annotations.toolbar).toHaveCount(0);
  await expect(annotations.banner).toBeHidden();
  const id = await article.comments.postRoot(ROOT_BODY);
  await article.comments.scrollToBottom();
  await expect(article.comments.item(id)).toBeVisible();
  await expect(annotations.banner).toBeHidden();
  await expect(annotations.allCountButtons).toHaveCount(0);
  await expect(annotations.captureForm).toHaveCount(0);
  await annotations.evidence('19-news');
});

test('mobile 375 px: touch selection, form fits, both pop-ups scroll', async ({ browser }) => {
  const page = await mobilePage(browser, 'user');
  const chapter = new ChapterPage(page, STORY.slug, ADVANCED.slug);
  const annotations = new ChapterAnnotations(page);
  const thread = new CommentThread(page);
  const width = 375;

  await test.step('touch selection → toolbar; capture form inside the viewport', async () => {
    await chapter.goto();
    await chapter.touchSelectText(chapter.textBlocks.first().locator('p').first());
    await expect(annotations.annotateButton).toBeVisible();
    await annotations.evidence('20-mobile-toolbar');
    await annotations.annotateButton.tap();
    await expect(annotations.captureForm).toBeVisible();
    await annotations.captureEditor.waitUntilReady();
    const form = (await annotations.captureForm.boundingBox())!;
    expect(form.x).toBeGreaterThanOrEqual(0);
    expect(form.x + form.width).toBeLessThanOrEqual(width);
    await annotations.evidence('21-mobile-form');
    await annotations.captureEditor.fill('Annotation mobile.');
    await annotations.captureSave.tap();
    await expect(annotations.banner).toContainText('1 annotation');
  });

  await test.step('drafts pop-up with many rows scrolls to the last one', async () => {
    await page.evaluate(() => {
      const form = document.querySelector<HTMLElement>('[data-annotation-drafts]')!;
      const { userId, entityType, entityId } = form.dataset;
      for (let i = 2; i <= 9; i++) {
        (window as any).commentDrafts.addAnnotation(Number(userId), entityType, entityId, {
          body: `<p>Annotation mobile ${i}. ${'Une note un peu longue pour remplir la ligne. '.repeat(4)}</p>`,
          highlighted: 'Alpha un.',
          prefix: '',
          suffix: 'La première',
        });
      }
    });
    await expect(annotations.banner).toContainText('9 annotations');
    await annotations.openDrafts();
    const last = annotations.draftRows.last();
    await last.scrollIntoViewIfNeeded();
    await expect(last).toBeInViewport();
    await annotations.evidence('22-mobile-drafts-popup-scrolled');
    await page.keyboard.press('Escape');
  });

  await test.step('server pop-up scrolls to the last row; no horizontal overflow', async () => {
    const id = await thread.postRoot(ROOT_BODY);
    await thread.scrollToBottom();
    await annotations.openPublished(thread.item(id));
    await expect(annotations.serverRows).toHaveCount(9);
    const last = annotations.serverRows.last();
    await last.scrollIntoViewIfNeeded();
    await expect(last).toBeInViewport();
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width);
    await annotations.evidence('23-mobile-server-popup-scrolled');
  });

  await page.context().close();
});

async function openPopup(page: Page, storySlug: string, chapterSlug: string): Promise<ChapterAnnotations> {
  const chapter = new ChapterPage(page, storySlug, chapterSlug);
  const thread = new CommentThread(page);
  const annotations = new ChapterAnnotations(page);
  await chapter.goto();
  await thread.scrollToBottom();
  await annotations.openPublished(thread.item(published.coauthored));
  return annotations;
}

async function mobilePage(browser: Browser, role: RoleName): Promise<Page> {
  const context = await browser.newContext({
    ...devices['Pixel 7'],
    viewport: { width: 375, height: 740 },
    ...ROLE_PAGE_ARGS(role),
  });
  return context.newPage();
}

/** The logged-in user's id, as the comment form prints it. */
async function userIdOf(page: Page): Promise<number> {
  return Number(await page.locator('[data-annotation-drafts]').first().getAttribute('data-user-id'));
}
