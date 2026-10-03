import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
  addAnnotation,
  addPendingAnnotation,
  bootstrap,
  clearAnnotationChanges,
  clearAnnotations,
  countAnnotationChanges,
  getAnnotationChanges,
  removePendingAdd,
  setPendingDelete,
  setPendingEdit,
  undoPendingDelete,
  undoPendingEdit,
  updatePendingAdd,
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

describe('comment-draft annotationChanges slot', () => {
  const anchor = (body = '<p>❤️</p>') => ({
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

  it('loads a version-1 payload with its annotations and an empty annotationChanges slot', () => {
    const draft = { tempId: 'a1', body: '<p>ok</p>', highlighted: 'passage', prefix: '', suffix: '' };
    localStorage.setItem(draftKey(7, 'chapter', '42'), JSON.stringify({
      version: 1,
      root: { body: '<p>root</p>', savedAt: 1 },
      reply: { parentCommentId: 99, body: '<p>reply</p>', savedAt: 1 },
      annotations: [draft],
    }));

    const state = load(7, 'chapter', '42');
    expect(state.root?.body).toBe('<p>root</p>');
    expect(state.reply?.body).toBe('<p>reply</p>');
    expect(state.annotations).toEqual([draft]);
    expect(state.annotationChanges).toEqual({ adds: [], edits: {}, deletes: [] });

    // The next write upgrades the payload to version 2 without losing anything.
    setPendingDelete(7, 'chapter', '42', 5);
    const stored = JSON.parse(localStorage.getItem(draftKey(7, 'chapter', '42')));
    expect(stored.version).toBe(2);
    expect(stored.annotations).toEqual([draft]);
    expect(stored.root.body).toBe('<p>root</p>');
  });

  it('drops malformed annotationChanges items on load', () => {
    const add = { tempId: 't1', ...anchor() };
    localStorage.setItem(draftKey(7, 'chapter', '42'), JSON.stringify({
      version: 2,
      root: null,
      reply: null,
      annotations: [],
      annotationChanges: {
        adds: [add, null, { tempId: 't2', body: 3, highlighted: 'x' }],
        edits: { 12: '<p>edit</p>', abc: '<p>bad id</p>', 13: 7 },
        deletes: [14, 'x', -1, 1.5],
      },
    }));

    expect(getAnnotationChanges(7, 'chapter', '42')).toEqual({
      adds: [add],
      edits: { 12: '<p>edit</p>' },
      deletes: [14],
    });
  });

  it('adds, edits, deletes and undoes pending changes and counts them', () => {
    const t1 = addPendingAnnotation(7, 'chapter', '42', anchor('<p>one</p>'));
    const t2 = addPendingAnnotation(7, 'chapter', '42', anchor('<p>two</p>'));
    expect(typeof t1).toBe('string');
    expect(t1).not.toBe(t2);

    updatePendingAdd(7, 'chapter', '42', t1, '<p>one edited</p>');
    setPendingEdit(7, 'chapter', '42', 12, '<p>edited 12</p>');
    setPendingDelete(7, 'chapter', '42', 13);
    setPendingDelete(7, 'chapter', '42', 13);

    expect(getAnnotationChanges(7, 'chapter', '42')).toEqual({
      adds: [
        { tempId: t1, ...anchor('<p>one edited</p>') },
        { tempId: t2, ...anchor('<p>two</p>') },
      ],
      edits: { 12: '<p>edited 12</p>' },
      deletes: [13],
    });
    expect(countAnnotationChanges(7, 'chapter', '42')).toBe(4);

    removePendingAdd(7, 'chapter', '42', t2);
    undoPendingEdit(7, 'chapter', '42', 12);
    undoPendingDelete(7, 'chapter', '42', 13);
    expect(getAnnotationChanges(7, 'chapter', '42')).toEqual({
      adds: [{ tempId: t1, ...anchor('<p>one edited</p>') }],
      edits: {},
      deletes: [],
    });
    expect(countAnnotationChanges(7, 'chapter', '42')).toBe(1);

    clearAnnotationChanges(7, 'chapter', '42');
    expect(countAnnotationChanges(7, 'chapter', '42')).toBe(0);
    expect(localStorage.getItem(draftKey(7, 'chapter', '42'))).toBeNull();
  });

  it('drops a pending edit when the same id is marked for deletion', () => {
    setPendingEdit(7, 'chapter', '42', 12, '<p>edited</p>');
    setPendingEdit(7, 'chapter', '42', 13, '<p>kept</p>');
    setPendingDelete(7, 'chapter', '42', 12);

    expect(getAnnotationChanges(7, 'chapter', '42')).toEqual({
      adds: [],
      edits: { 13: '<p>kept</p>' },
      deletes: [12],
    });
  });

  it('keeps annotationChanges when the root consumed marker clears the root draft', () => {
    saveRoot(7, 'chapter', '42', '<p>root</p>');
    addAnnotation(7, 'chapter', '42', anchor());
    const tempId = addPendingAnnotation(7, 'chapter', '42', anchor());
    setPendingDelete(7, 'chapter', '42', 13);

    window.__commentDraftConsumed = { scope: 'root', userId: 7, entityType: 'chapter', entityId: '42' };
    bootstrap(document);
    delete window.__commentDraftConsumed;

    const state = load(7, 'chapter', '42');
    expect(state.root).toBeNull();
    expect(state.annotations).toEqual([]);
    expect(state.annotationChanges.adds.map((a) => a.tempId)).toEqual([tempId]);
    expect(state.annotationChanges.deletes).toEqual([13]);
    expect(localStorage.getItem(draftKey(7, 'chapter', '42'))).not.toBeNull();
  });

  it('scopes the slot per user and per entity', () => {
    addPendingAnnotation(7, 'chapter', '42', anchor());
    setPendingEdit(7, 'chapter', '42', 12, '<p>edit</p>');

    expect(countAnnotationChanges(8, 'chapter', '42')).toBe(0);
    expect(countAnnotationChanges(7, 'chapter', '43')).toBe(0);
    expect(countAnnotationChanges(7, 'news', '42')).toBe(0);
    expect(localStorage.getItem(draftKey(8, 'chapter', '42'))).toBeNull();
    expect(countAnnotationChanges(7, 'chapter', '42')).toBe(2);
  });

  describe('change events', () => {
    let events;
    const listener = (e) => events.push(e.detail);

    beforeEach(() => {
      events = [];
      window.addEventListener('comment-drafts:annotation-changes-changed', listener);
    });

    afterEach(() => {
      window.removeEventListener('comment-drafts:annotation-changes-changed', listener);
    });

    it('dispatches comment-drafts:annotation-changes-changed with the count', () => {
      const tempId = addPendingAnnotation(7, 'chapter', '42', anchor());
      updatePendingAdd(7, 'chapter', '42', tempId, '<p>edited</p>');
      setPendingEdit(7, 'chapter', '42', 12, '<p>edit</p>');
      setPendingDelete(7, 'chapter', '42', 12);
      undoPendingDelete(7, 'chapter', '42', 12);
      setPendingEdit(7, 'chapter', '42', 13, '<p>edit</p>');
      undoPendingEdit(7, 'chapter', '42', 13);
      removePendingAdd(7, 'chapter', '42', tempId);
      setPendingDelete(7, 'chapter', '42', 14);
      clearAnnotationChanges(7, 'chapter', '42');

      expect(events.map((e) => e.count)).toEqual([1, 1, 2, 2, 1, 2, 1, 0, 1, 0]);
      expect(events[0]).toEqual({ entityType: 'chapter', entityId: '42', count: 1 });
    });
  });
});
