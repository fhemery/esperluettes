import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
  addAnnotation,
  bootstrap,
  clearAnnotations,
  clearRoot,
  clearReply,
  listAnnotations,
  load,
  removeAnnotation,
  saveReply,
  saveRoot,
  updateAnnotation,
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

describe('comment-draft annotations slot', () => {
  const anchor = (body = '<p>note</p>') => ({
    body,
    highlighted: 'the quoted passage',
    prefix: 'before ',
    suffix: ' after',
  });

  beforeEach(() => {
    localStorage.clear();
    document.body.innerHTML = '';
    delete window.__commentDraftConsumed;
  });

  it('adds, lists, updates and removes annotation drafts per user and entity', () => {
    const first = addAnnotation(7, 'chapter', '42', anchor('<p>one</p>'));
    const second = addAnnotation(7, 'chapter', '42', anchor('<p>two</p>'));

    expect(typeof first.tempId).toBe('string');
    expect(first.tempId).not.toBe('');
    expect(first.tempId).not.toBe(second.tempId);
    expect(first).toMatchObject(anchor('<p>one</p>'));

    expect(listAnnotations(7, 'chapter', '42').map((a) => a.body)).toEqual(['<p>one</p>', '<p>two</p>']);
    expect(listAnnotations(7, 'chapter', '43')).toEqual([]);

    updateAnnotation(7, 'chapter', '42', first.tempId, '<p>one edited</p>');
    const afterUpdate = listAnnotations(7, 'chapter', '42');
    expect(afterUpdate[0]).toMatchObject({ ...anchor('<p>one edited</p>'), tempId: first.tempId });
    expect(afterUpdate[1].body).toBe('<p>two</p>');

    removeAnnotation(7, 'chapter', '42', first.tempId);
    expect(listAnnotations(7, 'chapter', '42').map((a) => a.tempId)).toEqual([second.tempId]);

    clearAnnotations(7, 'chapter', '42');
    expect(listAnnotations(7, 'chapter', '42')).toEqual([]);
  });

  it("another user's key never sees the drafts", () => {
    addAnnotation(7, 'chapter', '42', anchor());

    expect(listAnnotations(8, 'chapter', '42')).toEqual([]);
    expect(load(8, 'chapter', '42').annotations).toEqual([]);
    expect(localStorage.getItem(draftKey(8, 'chapter', '42'))).toBeNull();
  });

  it('annotation drafts survive a root-body save and a reply save', () => {
    const item = addAnnotation(7, 'chapter', '42', anchor());

    saveRoot(7, 'chapter', '42', '<p>root</p>');
    saveReply(7, 'chapter', '42', 99, '<p>reply</p>');
    clearReply(7, 'chapter', '42');

    expect(listAnnotations(7, 'chapter', '42')).toEqual([item]);
    expect(load(7, 'chapter', '42').root?.body).toBe('<p>root</p>');
  });

  it('a root consumed marker clears root and annotations; a reply marker clears neither', () => {
    saveRoot(7, 'chapter', '42', '<p>root</p>');
    saveReply(7, 'chapter', '42', 99, '<p>reply</p>');
    addAnnotation(7, 'chapter', '42', anchor());

    window.__commentDraftConsumed = { scope: 'reply', userId: 7, entityType: 'chapter', entityId: '42' };
    bootstrap(document);

    let state = load(7, 'chapter', '42');
    expect(state.reply).toBeNull();
    expect(state.root?.body).toBe('<p>root</p>');
    expect(state.annotations).toHaveLength(1);

    window.__commentDraftConsumed = { scope: 'root', userId: 7, entityType: 'chapter', entityId: '42' };
    bootstrap(document);

    state = load(7, 'chapter', '42');
    expect(state.root).toBeNull();
    expect(state.annotations).toEqual([]);
    expect(localStorage.getItem(draftKey(7, 'chapter', '42'))).toBeNull();
  });

  it('the storage key is removed once root, reply and annotations are all empty', () => {
    saveRoot(7, 'chapter', '42', '<p>root</p>');
    const item = addAnnotation(7, 'chapter', '42', anchor());

    clearRoot(7, 'chapter', '42');
    expect(localStorage.getItem(draftKey(7, 'chapter', '42'))).not.toBeNull();

    removeAnnotation(7, 'chapter', '42', item.tempId);
    expect(localStorage.getItem(draftKey(7, 'chapter', '42'))).toBeNull();
  });

  it('malformed annotation items are dropped on load', () => {
    const good = { tempId: 'a1', body: '<p>ok</p>', highlighted: 'passage', prefix: '', suffix: '' };
    localStorage.setItem(draftKey(7, 'chapter', '42'), JSON.stringify({
      version: 1,
      root: null,
      reply: null,
      annotations: [
        good,
        null,
        'string',
        { body: '<p>no id</p>', highlighted: 'x' },
        { tempId: 'a2', body: 42, highlighted: 'x' },
        { tempId: 'a3', body: '<p>no highlight</p>' },
        { tempId: '', body: '<p>empty id</p>', highlighted: 'x' },
      ],
    }));

    expect(listAnnotations(7, 'chapter', '42')).toEqual([good]);
  });

  describe('change events', () => {
    let events;
    const listener = (e) => events.push(e.detail);

    beforeEach(() => {
      events = [];
      window.addEventListener('comment-drafts:annotations-changed', listener);
    });

    afterEach(() => {
      window.removeEventListener('comment-drafts:annotations-changed', listener);
    });

    it('mutations dispatch comment-drafts:annotations-changed with the count', () => {
      const first = addAnnotation(7, 'chapter', '42', anchor());
      addAnnotation(7, 'chapter', '42', anchor());
      updateAnnotation(7, 'chapter', '42', first.tempId, '<p>edited</p>');
      removeAnnotation(7, 'chapter', '42', first.tempId);
      clearAnnotations(7, 'chapter', '42');

      expect(events).toEqual([
        { entityType: 'chapter', entityId: '42', count: 1 },
        { entityType: 'chapter', entityId: '42', count: 2 },
        { entityType: 'chapter', entityId: '42', count: 2 },
        { entityType: 'chapter', entityId: '42', count: 1 },
        { entityType: 'chapter', entityId: '42', count: 0 },
      ]);
    });
  });
});
