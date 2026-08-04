/**
 * VERIFY specs for `news-comments` — the comment thread on a news article.
 *
 * Temporary by design (see ../features/README.md): everything that a PHP
 * feature test can assert already lives in `app/Domains/News/Tests/Feature/`.
 * What is left here is what only a browser can prove — Quill booting, the
 * Alpine length gate on the submit button, the lazy `/comments/fragments`
 * fetch, the reply/edit toggles, the report modal, the deep-linked scroll, and
 * the layout at 375px.
 *
 * The tests are serial and stateful on purpose: the thread they build up in
 * the first tests is what the later ones report, moderate and delete.
 */
import { expect, test } from '../../support/test';
import { ACCOUNTS, NEWS } from '../../support/fixtures';
import { NewsAdminPage, NewsArticlePage } from '../../pages/NewsArticlePage';
import { NotificationSettingsPage, NotificationsPage } from '../../pages/NotificationsPage';
import { ModerationReportsPage } from '../../pages/ModerationReportsPage';
import { ReportModal } from '../../pages/ReportModal';
import { AdminUsersPage } from '../../pages/AdminUsersPage';
import type { Page } from '@playwright/test';

/** Set `E2E_SHOTS_DIR` to collect the layout evidence VERIFY reports on. */
const SHOTS = process.env.E2E_SHOTS_DIR;
async function shot(page: Page, name: string): Promise<void> {
  if (!SHOTS) return;
  await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true });
}

const ROOT_A = 'Premier commentaire de test sur cette actualite';
const ROOT_B = 'Deuxieme commentaire du meme membre confirme';
const ROOT_ADMIN = "Commentaire du createur de l'actualite lui-meme";
const ROOT_AUTHOR = "Commentaire d'un compte qui sera supprime";
const REPLY_SHORT = 'k';
const ROOT_A_EDITED = 'Premier commentaire de test, corrige apres coup';
const REPORT_DESCRIPTION = 'Signalement e2e news-comments';

let rootAId = 0;
let rootBId = 0;
let rootAdminId = 0;
let rootAuthorId = 0;
let replyId = 0;

test.describe.configure({ mode: 'serial' });

test.describe('News comments', () => {
  test('empty thread, editor and toolbar on a published article', async ({ confirmed }) => {
    const article = new NewsArticlePage(confirmed);
    await article.goto();

    await expect(article.section).toBeVisible();
    await expect(article.comments.root).toBeVisible();
    await expect(article.comments.membersOnlyBox).toHaveCount(0);
    await expect(article.comments.emptyMessage).toBeVisible();
    await expect(article.comments.items).toHaveCount(0);

    // Quill has to boot for the editor to be usable at all.
    await article.comments.rootEditor.waitUntilReady();
    await expect(article.comments.rootEditor.toolbar).toBeVisible();
    await expect(article.comments.rootSubmit).toBeVisible();

    // …and the thread sits below the article body, not above it.
    const body = await article.body.boundingBox();
    const thread = await article.section.boundingBox();
    expect(thread!.y).toBeGreaterThan(body!.y);

    await shot(confirmed, 'row01-empty-thread');
  });

  test('the root form gates on 20 characters and posts at 20', async ({ confirmed }) => {
    const article = new NewsArticlePage(confirmed);
    await article.goto();
    const editor = article.comments.rootEditor;
    await editor.waitUntilReady();

    await editor.fill('Dix-neuf caracteres'); // exactly 19
    await expect(editor.counter).toHaveText('19');
    await expect(article.comments.rootSubmit).toBeDisabled();

    await editor.type('!'); // 20
    await expect(editor.counter).toHaveText('20');
    await expect(article.comments.rootSubmit).toBeEnabled();

    await shot(confirmed, 'row03-counter-at-20');

    rootAId = await article.comments.postRoot(ROOT_A);
    await expect(article.comments.item(rootAId)).toBeVisible();
    await expect(article.comments.item(rootAId)).toContainText(ROOT_A);
  });

  test('the thread is lazy: items come from /comments/fragments, not the initial HTML', async ({
    confirmed,
  }) => {
    const article = new NewsArticlePage(confirmed);
    const fragmentCalls: string[] = [];
    confirmed.on('response', (response) => {
      if (response.url().includes('/comments/fragments')) fragmentCalls.push(response.url());
    });

    const html = await article.goto();
    expect(html, 'the comment body was server-rendered — lazy mode is not in effect').not.toContain(
      ROOT_A,
    );
    expect(html).toContain('page: 0');

    await article.comments.scrollToBottom();
    await expect(article.comments.item(rootAId)).toBeVisible();
    expect(fragmentCalls.length, 'no /comments/fragments call was made').toBeGreaterThan(0);
  });

  test('the same user can post a second root comment', async ({ confirmed }) => {
    const article = new NewsArticlePage(confirmed);
    await article.goto();

    // Unlike a chapter, the form is still there after a first root comment.
    await expect(article.comments.rootForm).toBeVisible();
    rootBId = await article.comments.postRoot(ROOT_B);

    await article.comments.scrollToBottom();
    await expect(article.comments.item(rootAId)).toBeVisible();
    await expect(article.comments.item(rootBId)).toBeVisible();
  });

  test("the article's creator can comment on their own article", async ({ admin }) => {
    const article = new NewsArticlePage(admin);
    await article.goto();

    await expect(article.comments.rootForm).toBeVisible();
    rootAdminId = await article.comments.postRoot(ROOT_ADMIN);
    await expect(article.comments.item(rootAdminId)).toContainText(ROOT_ADMIN);
  });

  test('a one-character reply is accepted and the thread stays one level deep', async ({ user }) => {
    const article = new NewsArticlePage(user);
    await article.goto(`?comment=${rootAId}`);

    await expect(article.comments.replyButtonOn(rootAId)).toBeVisible();
    replyId = await article.comments.postReply(rootAId, REPLY_SHORT);

    const reply = article.comments.item(replyId);
    await expect(reply).toBeVisible();
    // Nested inside its root, i.e. rendered as a child and not as a new root.
    await expect(article.comments.item(rootAId).locator(`#comment-${replyId}`)).toBeVisible();

    // No reply form of its own: the control on a reply continues the parent thread.
    await expect(article.comments.replyForm(replyId)).toHaveCount(0);
    const replyControls = reply.locator('[data-action="reply"]');
    for (let i = 0; i < (await replyControls.count()); i++) {
      await expect(replyControls.nth(i)).toHaveAttribute('data-comment-id', String(rootAId));
    }

    await shot(user, 'row06-reply-posted');
  });

  test('the root author is notified and the notification deep-links to the reply', async ({
    confirmed,
  }) => {
    const bell = new NotificationsPage(confirmed);
    const article = new NewsArticlePage(confirmed);
    await article.goto();
    await expect(bell.unreadBadge).toBeVisible();

    await bell.goto();
    const item = bell.itemsContaining("a répondu à un commentaire sur l'actualité");
    await expect(item).toHaveCount(1);
    await expect(item).toContainText(NEWS.title);
    await expect(item).toContainText(ACCOUNTS.user.displayName);

    const link = item.locator(`a[href*="comment=${replyId}"]`);
    await expect(link).toHaveAttribute('href', new RegExp(`/news/${NEWS.slug}\\?comment=${replyId}$`));

    await shot(confirmed, 'row07-notification');

    await link.click();
    await confirmed.waitForURL(new RegExp(`/news/${NEWS.slug}\\?comment=${replyId}`));
    // Deep links are pre-loaded server-side, so the target is there without scrolling…
    await expect(article.comments.item(replyId)).toBeVisible();
    // …and the page is scrolled to it.
    await expect(article.comments.item(replyId)).toBeInViewport();
  });

  test('replying to your own thread notifies nobody', async ({ admin }) => {
    const article = new NewsArticlePage(admin);
    await article.goto(`?comment=${rootAdminId}`);
    await article.comments.postReply(rootAdminId, 'Je me reponds a moi-meme.');

    const bell = new NotificationsPage(admin);
    await bell.goto();
    await expect(bell.itemsContaining("a répondu à un commentaire sur l'actualité")).toHaveCount(0);
  });

  test('the news comment notification has its own settings group, and turning it off sticks', async ({
    confirmed,
  }) => {
    const settings = new NotificationSettingsPage(confirmed);
    await settings.goto();

    const newsComments = settings.groupHeader("Commentaires d'actualités");
    await expect(newsComments).toHaveCount(1);
    // Distinct from Story's own "Commentaires" group.
    await expect(settings.groupHeader('Commentaires')).toHaveCount(1);

    const toggle = settings.websiteToggle('news.reply_comment');
    await expect(toggle).toHaveCount(1);
    await expect(toggle).toBeChecked();
    await expect(settings.row('news.reply_comment')).toContainText(
      "Un de mes commentaires d'actualité a reçu une réponse",
    );

    await shot(confirmed, 'row09-settings-group');

    // The input is `sr-only`; the label is the clickable surface.
    await settings.toggleLabel('news.reply_comment').click();
    await expect(toggle).not.toBeChecked();
    await settings.save();

    await settings.goto();
    await expect(settings.websiteToggle('news.reply_comment')).not.toBeChecked();

    // Put it back so the rest of the run sees the default.
    await settings.toggleLabel('news.reply_comment').click();
    await settings.save();
    await settings.goto();
    await expect(settings.websiteToggle('news.reply_comment')).toBeChecked();
  });

  test('a member can edit their own root comment, still under the 20-character rule', async ({
    confirmed,
  }) => {
    const article = new NewsArticlePage(confirmed);
    await article.goto(`?comment=${rootAId}`);

    await expect(article.comments.editButtonOn(rootAId)).toBeVisible();
    await article.comments.editButtonOn(rootAId).click();

    const form = article.comments.editForm(rootAId);
    await expect(form).toBeVisible();
    const editor = article.comments.editEditor(rootAId);
    await editor.waitUntilReady();
    await expect(editor.body).toContainText(ROOT_A);

    const save = form.locator('button[type="submit"]');
    await editor.fill('trop court');
    await expect(save).toBeDisabled();

    await editor.fill(ROOT_A_EDITED);
    await expect(save).toBeEnabled();
    await save.click();

    await confirmed.waitForURL(/\/news\//);
    await article.comments.scrollToBottom();
    await expect(article.comments.item(rootAId)).toContainText(ROOT_A_EDITED);
    await expect(article.comments.item(rootAId)).toContainText('Modifié le');
  });

  test('a member can edit their own reply, with no minimum length', async ({ user }) => {
    const article = new NewsArticlePage(user);
    await article.goto(`?comment=${replyId}`);

    await expect(article.comments.editButtonOn(replyId)).toBeVisible();
    await article.comments.editButtonOn(replyId).click();

    const form = article.comments.editForm(replyId);
    await expect(form).toBeVisible();
    const editor = article.comments.editEditor(replyId);
    await editor.waitUntilReady();
    await editor.fill('ok');
    await expect(form.locator('button[type="submit"]')).toBeEnabled();
  });

  test('a member can report a comment, and it reaches the moderation panel', async ({ user }) => {
    const article = new NewsArticlePage(user);
    await article.goto(`?comment=${rootAId}`);

    await expect(article.comments.reportButtonOn(rootAId)).toBeVisible();
    await article.comments.reportButtonOn(rootAId).click();

    const modal = new ReportModal(user);
    await expect(modal.title).toBeVisible();
    expect(await modal.reasonLabels()).toContain('Other');
    await shot(user, 'row11-report-modal');
    await modal.fileReport('Other', REPORT_DESCRIPTION);
  });

  test('the report deep-links back to the comment on the article', async ({ admin }) => {
    const reports = new ModerationReportsPage(admin);
    await reports.goto('comment');

    const row = reports.rowWithDescription(REPORT_DESCRIPTION);
    await expect(row).toHaveCount(1);

    const link = reports.contentLinkIn(row);
    await expect(link).toHaveAttribute('href', new RegExp(`/news/${NEWS.slug}\\?comment=${rootAId}$`));

    const href = await link.getAttribute('href');
    const response = await admin.goto(href!);
    expect(response?.status(), `GET ${href}`).toBe(200);
    await expect(new NewsArticlePage(admin).comments.item(rootAId)).toBeVisible();
  });

  test('a moderator gets the moderation actions on a comment', async ({ moderator }) => {
    const article = new NewsArticlePage(moderator);
    await article.goto(`?comment=${rootAId}`);

    const trigger = article.comments.moderationTriggerOn(rootAId);
    await expect(trigger).toBeVisible();
    await trigger.click();

    const menu = article.comments.openModerationMenu;
    await expect(menu).toBeVisible();
    await expect(menu).toContainText('Supprimer');
    await shot(moderator, 'row12-moderation-menu');
  });

  test('a guest gets the members-only prompt instead of the thread', async ({ guest }) => {
    const article = new NewsArticlePage(guest);
    await article.goto();

    await expect(guest.getByRole('heading', { name: NEWS.title })).toBeVisible();
    await expect(article.comments.membersOnlyBox).toBeVisible();
    await expect(article.comments.loginButton).toBeVisible();
    await expect(article.comments.rootForm).toHaveCount(0);
    await expect(article.comments.items).toHaveCount(0);
    await shot(guest, 'row14-guest-prompt');

    await article.comments.loginButton.click();
    await guest.waitForURL(/\/login/);
  });

  test('a draft article carries no comment section at all', async ({ admin }) => {
    const draft = new NewsArticlePage(admin, NEWS.draft.slug);
    const html = await draft.goto();

    // Anchor first: the negatives below are only worth anything on a page that
    // actually rendered the draft.
    await expect(admin.getByRole('heading', { name: NEWS.draft.title })).toBeVisible();
    await expect(draft.body).toBeVisible();

    expect(html).not.toContain('id="comment-list"');
    await expect(draft.section).toHaveCount(0);
    await expect(draft.comments.root).toHaveCount(0);
    await expect(draft.comments.membersOnlyBox).toHaveCount(0);
    await shot(admin, 'row13-draft-no-thread');
  });

  test('a regular member still gets a 404 on a draft article', async ({ user }) => {
    await new NewsArticlePage(user, NEWS.draft.slug).gotoExpecting404();
  });

  test('the thread is usable at 375px', async ({ confirmed }) => {
    await confirmed.setViewportSize({ width: 375, height: 800 });
    const article = new NewsArticlePage(confirmed);
    await article.goto(`?comment=${rootAId}`);

    await article.comments.rootEditor.waitUntilReady();
    await expect(article.comments.rootEditor.toolbar).toBeVisible();
    await expect(article.comments.rootSubmit).toBeVisible();
    await expect(article.comments.item(rootAId)).toBeVisible();

    const overflow = await confirmed.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow, 'the page scrolls sideways at 375px').toBeLessThanOrEqual(1);

    // The reply control is reachable and its editor fits.
    await article.comments.replyButtonOn(rootAId).click();
    await expect(article.comments.replyForm(rootAId)).toBeVisible();
    await article.comments.replyEditor(rootAId).waitUntilReady();
    await shot(confirmed, 'row18-mobile');
  });

  test('a deactivated author hides their comment, reactivating brings it back', async ({
    admin,
    confirmed,
  }) => {
    const article = new NewsArticlePage(confirmed);
    const users = new AdminUsersPage(admin);

    await users.deactivate(ACCOUNTS.user.email);
    await article.goto(`?comment=${rootAId}`);
    await article.comments.scrollToBottom();
    // The root is the anchor: the thread rendered, the reply is what is gone.
    await expect(article.comments.item(rootAId)).toBeVisible();
    await expect(article.comments.item(replyId)).toHaveCount(0);

    await users.reactivate(ACCOUNTS.user.email);
    await article.goto(`?comment=${replyId}`);
    await expect(article.comments.item(replyId)).toBeVisible();
  });

  test('deleting an article takes its thread with it', async ({ admin, confirmed }) => {
    const disposable = new NewsArticlePage(confirmed, NEWS.disposable.slug);
    await disposable.goto();
    const doomedId = await disposable.comments.postRoot('Commentaire sur une actualite condamnee');
    await expect(disposable.comments.item(doomedId)).toBeVisible();

    const newsAdmin = new NewsAdminPage(admin);
    await newsAdmin.goto();
    await newsAdmin.delete(NEWS.disposable.title);
    await expect(newsAdmin.row(NEWS.disposable.title)).toHaveCount(0);

    await new NewsArticlePage(confirmed, NEWS.disposable.slug).gotoExpecting404();

    // The surfaces that list articles still render.
    expect((await confirmed.goto('/news'))?.status()).toBe(200);
    await expect(confirmed.getByText(NEWS.disposable.title)).toHaveCount(0);
    expect((await confirmed.goto('/'))?.status()).toBe(200);

    // …and the surviving article's own thread is untouched.
    const survivor = new NewsArticlePage(confirmed);
    await survivor.goto(`?comment=${rootAId}`);
    await expect(survivor.comments.item(rootAId)).toBeVisible();
    await expect(survivor.comments.item(rootBId)).toBeVisible();
  });

  // Last on purpose: deleting an account cascades far beyond comments.
  test('a deleted author leaves the comment standing, with no profile link', async ({
    admin,
    author,
    confirmed,
  }) => {
    const byAuthor = new NewsArticlePage(author);
    await byAuthor.goto();
    rootAuthorId = await byAuthor.comments.postRoot(ROOT_AUTHOR);

    await new AdminUsersPage(admin).destroy(ACCOUNTS.author.email);

    const article = new NewsArticlePage(confirmed);
    await article.goto(`?comment=${rootAuthorId}`);
    const orphan = article.comments.item(rootAuthorId);
    await expect(orphan).toBeVisible();
    await expect(orphan).toContainText(ROOT_AUTHOR);
    await expect(orphan).toContainText('Esperluette disparue');
    await expect(orphan.locator(`a[href*="/profile/${ACCOUNTS.author.profileSlug}"]`)).toHaveCount(0);
    await shot(confirmed, 'row16-deleted-author');
  });
});
