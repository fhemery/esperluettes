import { expect, test } from '../../support/test';
import { NewsArticlePage } from '../../pages/NewsArticlePage';

/**
 * CORE — the lazy load contract of `<x-comment::comment-list-component>`.
 *
 * Mounted with `page="0"`, the component must ship **no comment** in the
 * server-rendered HTML and fetch the thread from `GET /comments/fragments`
 * once its `x-intersect` sentinel is reached. That contract belongs to the
 * Comment domain, not to any one consumer: chapters and news articles both
 * mount the same component in the same mode, so a regression in the sentinel,
 * the Alpine loader or the fragments endpoint silently breaks every comment
 * thread in the app at once — and no PHP feature test can see it, because on
 * the server side the page is *supposed* to come back without comments.
 *
 * It is exercised here through a news article only because that is the
 * cheapest mount point: no per-user root cap, no author restriction, a 20
 * character minimum and nothing else.
 *
 * Everything else about news comments (who may post, lengths, notifications,
 * moderation, cascade delete) is asserted in app/Domains/News/Tests/Feature/.
 */

const BODY = 'Commentaire e2e du chargement paresseux du fil';

test('a lazy thread ships no comment in the HTML and fetches them on scroll', async ({
  confirmed,
}) => {
  const article = new NewsArticlePage(confirmed);

  // Arrange — the seeded article starts with an empty thread, so there has to
  // be one comment before there is anything to lazily load.
  await article.goto();
  const commentId = await article.comments.postRoot(BODY);

  const fragmentCalls: number[] = [];
  confirmed.on('response', (response) => {
    if (response.url().includes('/comments/fragments')) fragmentCalls.push(response.status());
  });

  // The HTML is the only deterministic witness of "not rendered server-side":
  // the article is short enough that the sentinel can already be in view on
  // load, so counting requests *before* scrolling would be a race.
  const html = await article.goto();
  expect(html, 'the comment body was server-rendered — lazy mode is not in effect').not.toContain(
    BODY,
  );
  expect(html, 'the thread was not mounted with page: 0').toContain('page: 0');

  await article.comments.scrollToBottom();
  await expect(article.comments.item(commentId)).toBeVisible();
  expect(fragmentCalls, 'the comment arrived without a GET /comments/fragments').toEqual([200]);
});
