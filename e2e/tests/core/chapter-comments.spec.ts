import { ChapterCommentsPage } from '../../pages/ChapterCommentsPage';
import { COMMENTS } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — inline comment reply/edit editors.
 *
 * `<x-comment::comment-list>` loads Quill through the list shell and boots
 * composers when Répondre / Éditer open. If that wiring breaks — especially
 * when the root form is absent (`canCreateRoot=false`) — chapter comments go
 * blank app-wide. Feature tests catch missing assets; only a browser catches
 * a silent Quill init failure on inline composers.
 */

test('author without root form sees a Quill toolbar when opening Répondre', async ({ author }) => {
  const comments = new ChapterCommentsPage(author);

  await comments.goto();
  await comments.waitForCommentsLoaded();

  await expect(author.locator('[data-comment-draft="root"]')).toHaveCount(0);

  await comments.openReplyOnRoot();

  const editor = comments.replyEditor();
  await editor.waitUntilReady();
  await expect(editor.toolbar).toBeVisible();
  await expect(editor.toolbar.locator('button')).not.toHaveCount(0);

  await editor.type(' Réponse E2E.');
  await expect(editor.body).toContainText('Réponse E2E');
});

test('comment author sees Quill with existing body when opening Éditer', async ({ confirmed }) => {
  const comments = new ChapterCommentsPage(confirmed);

  await comments.goto();
  await comments.waitForCommentsLoaded();

  await comments.openEditOnRoot();

  const editor = comments.editEditor();
  await editor.waitUntilReady();
  await expect(editor.toolbar).toBeVisible();
  await expect(editor.body).toContainText(COMMENTS.bodyMarker);

  await editor.type(' — modifié.');
  await expect(editor.body).toContainText('modifié');
});
