import { beforeEach, describe, expect, it } from 'vitest';
import {
  bootstrap,
  clearRoot,
  clearReply,
  load,
  saveReply,
  saveRoot,
} from './index.js';

function draftKey(userId, entityType, entityId) {
  return `comment-drafts:${userId}:${entityType}:${entityId}`;
}

function mountRootForm({ userId = 7, entityType = 'news', entityId = '42' } = {}) {
  document.body.innerHTML = `
    <form
      data-comment-draft="root"
      data-user-id="${userId}"
      data-entity-type="${entityType}"
      data-entity-id="${entityId}"
    >
      <div id="editor-root" data-toolbar="{}"></div>
      <textarea id="quill-editor-area-editor-root"></textarea>
    </form>
  `;
  return {
    form: document.querySelector('form[data-comment-draft="root"]'),
    textarea: document.getElementById('quill-editor-area-editor-root'),
  };
}

function mountReplyForm({
  userId = 7,
  entityType = 'news',
  entityId = '42',
  parentCommentId = 99,
} = {}) {
  document.body.innerHTML = `
    <form
      data-comment-draft="reply"
      data-user-id="${userId}"
      data-entity-type="${entityType}"
      data-entity-id="${entityId}"
      data-parent-comment-id="${parentCommentId}"
    >
      <div id="editor-reply" data-toolbar="{}"></div>
      <textarea id="quill-editor-area-editor-reply"></textarea>
    </form>
  `;
  return {
    form: document.querySelector('form[data-comment-draft="reply"]'),
    textarea: document.getElementById('quill-editor-area-editor-reply'),
  };
}

describe('comment-draft consume-before-restore', () => {
  beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    delete window.__commentDraftConsumed;
  });

  it('clears a matching root draft and does not restore it when consumed', () => {
    saveRoot(7, 'news', '42', '<p>just submitted</p>');
    window.__commentDraftConsumed = {
      scope: 'root',
      userId: 7,
      entityType: 'news',
      entityId: '42',
    };

    const { textarea } = mountRootForm();
    bootstrap(document);

    expect(load(7, 'news', '42').root).toBeNull();
    expect(localStorage.getItem(draftKey(7, 'news', '42'))).toBeNull();
    expect(textarea.value).toBe('');
  });

  it('clears a matching reply draft and does not restore it when consumed', () => {
    saveReply(7, 'news', '42', 99, '<p>reply submitted</p>');
    window.__commentDraftConsumed = {
      scope: 'reply',
      userId: 7,
      entityType: 'news',
      entityId: '42',
    };

    const { textarea } = mountReplyForm();
    bootstrap(document);

    expect(load(7, 'news', '42').reply).toBeNull();
    expect(textarea.value).toBe('');
  });

  it('still restores an unfinished root draft when nothing was consumed', () => {
    saveRoot(7, 'news', '42', '<p>still typing</p>');

    const { textarea } = mountRootForm();
    bootstrap(document);

    expect(textarea.value).toBe('<p>still typing</p>');
    expect(load(7, 'news', '42').root?.body).toBe('<p>still typing</p>');
  });

  it('ignores a consumed marker for a different entity', () => {
    saveRoot(7, 'news', '42', '<p>keep me</p>');
    window.__commentDraftConsumed = {
      scope: 'root',
      userId: 7,
      entityType: 'news',
      entityId: '99',
    };

    const { textarea } = mountRootForm();
    bootstrap(document);

    expect(textarea.value).toBe('<p>keep me</p>');
    expect(load(7, 'news', '42').root?.body).toBe('<p>keep me</p>');
  });

  it('exposes clear helpers used by the flash script', () => {
    saveRoot(7, 'news', '42', '<p>x</p>');
    clearRoot(7, 'news', '42');
    expect(load(7, 'news', '42').root).toBeNull();

    saveReply(7, 'news', '42', 1, '<p>y</p>');
    clearReply(7, 'news', '42');
    expect(load(7, 'news', '42').reply).toBeNull();
  });
});
