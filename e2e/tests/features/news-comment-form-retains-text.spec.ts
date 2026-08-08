import { NewsArticlePage } from '../../pages/NewsArticlePage';
import { ChapterCommentsPage } from '../../pages/ChapterCommentsPage';
import { expect, test } from '../../support/test';

/**
 * Feature VERIFY — news comment compose form must be empty after a successful
 * submit (draft consume-before-restore). Temporary: delete or promote at WRAP.
 *
 * Client-only: PHP cannot see localStorage restore into Quill after redirect.
 */

const ROOT_BODY = 'Commentaire e2e apres soumission le formulaire doit etre vide';
const DRAFT_BODY = 'Brouillon e2e encore en cours de redaction';
const REPLY_BODY = 'Reponse e2e qui ne doit pas revenir dans le compositeur';

test('news root form is empty after a successful submit', async ({ confirmed }) => {
  const article = new NewsArticlePage(confirmed);
  await article.goto();

  await article.comments.postRoot(ROOT_BODY);

  await article.comments.rootEditor.waitUntilReady();
  await expect(article.comments.rootForm).toBeVisible();
  await expect(article.comments.rootEditor.body).toHaveText('');
});

test('unfinished news root draft still restores after leaving the page', async ({ confirmed }) => {
  const article = new NewsArticlePage(confirmed);
  await article.goto();

  await article.comments.rootEditor.waitUntilReady();
  await article.comments.rootEditor.fill(DRAFT_BODY);
  // Debounced autosave is 500ms.
  await confirmed.waitForTimeout(700);

  await confirmed.goto('/');
  await article.goto();

  await article.comments.rootEditor.waitUntilReady();
  await expect(article.comments.rootEditor.body).toContainText(DRAFT_BODY);
});

test('news reply composer does not reopen with the submitted body', async ({ confirmed }) => {
  const article = new NewsArticlePage(confirmed);
  await article.goto();

  const rootId = await article.comments.postRoot(`${ROOT_BODY} pour reponse`);
  await article.comments.scrollToBottom();
  await expect(article.comments.item(rootId)).toBeVisible();

  await article.comments.postReply(rootId, REPLY_BODY);

  // Root form stays; reply form should not auto-open with leftover body.
  await expect(article.comments.replyForm(rootId)).toBeHidden();
  await article.comments.openReply(rootId);
  await article.comments.replyEditor(rootId).waitUntilReady();
  await expect(article.comments.replyEditor(rootId).body).toHaveText('');
});

test('chapter still hides the root form after the author already has a root', async ({ author }) => {
  const comments = new ChapterCommentsPage(author);
  await comments.goto();
  await comments.waitForCommentsLoaded();
  await expect(author.locator('[data-comment-draft="root"]')).toHaveCount(0);
});
