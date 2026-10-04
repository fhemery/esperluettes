// Comment domain — draft local-storage autosave.
//
// One key per (user, entityType, entityId) holds an object with four slots:
//   - root:        { body, savedAt }         | null  — the root-comment editor draft
//   - reply:       { parentCommentId, body, savedAt } | null  — a single reply-in-progress
//   - annotations: [{ tempId, body, highlighted, prefix, suffix }] — chapter annotation drafts,
//                  posted with the root comment and cleared with it by the root consumed marker
//   - annotationChanges: { adds: [{ tempId, body, highlighted, prefix, suffix }],
//                          edits: { [annotationId]: body }, deletes: [annotationId] }
//                  — pending changes to the annotations of an already-posted root comment,
//                  saved in one PUT. The root consumed marker leaves it alone; only a
//                  successful save clears it.
//
// Version 1 payloads (before annotationChanges) still load, with an empty slot.
//
// Forms opt in by adding `data-comment-draft="root"` (or `="reply"`) plus
// `data-user-id`, `data-entity-type`, `data-entity-id`, and for replies `data-parent-comment-id`.
// The Quill editor inside the form is discovered via its `data-toolbar` attribute.

const SCHEMA_VERSION = 2;
const READABLE_VERSIONS = [1, 2];

function keyFor(userId, entityType, entityId) {
  return `comment-drafts:${userId}:${entityType}:${entityId}`;
}

function emptyChanges() {
  return { adds: [], edits: {}, deletes: [] };
}

function emptyState() {
  return { version: SCHEMA_VERSION, root: null, reply: null, annotations: [], annotationChanges: emptyChanges() };
}

function countChanges(changes) {
  return changes.adds.length + Object.keys(changes.edits).length + changes.deletes.length;
}

function isEmpty(state) {
  return !state.root
    && !state.reply
    && (!Array.isArray(state.annotations) || state.annotations.length === 0)
    && countChanges(state.annotationChanges) === 0;
}

function isAnnotationId(value) {
  return Number.isInteger(value) && value > 0;
}

function normalizeChanges(raw) {
  if (!raw || typeof raw !== 'object') return emptyChanges();
  const edits = {};
  if (raw.edits && typeof raw.edits === 'object' && !Array.isArray(raw.edits)) {
    Object.entries(raw.edits).forEach(([id, body]) => {
      if (/^[1-9]\d*$/.test(id) && typeof body === 'string') edits[id] = body;
    });
  }
  return {
    adds: Array.isArray(raw.adds) ? raw.adds.filter(isAnnotationItem) : [],
    edits,
    deletes: Array.isArray(raw.deletes) ? [...new Set(raw.deletes.filter(isAnnotationId))] : [],
  };
}

function isAnnotationItem(item) {
  return !!item
    && typeof item === 'object'
    && typeof item.tempId === 'string'
    && item.tempId !== ''
    && typeof item.body === 'string'
    && typeof item.highlighted === 'string';
}

export function load(userId, entityType, entityId) {
  if (!userId) return emptyState();
  let raw;
  try {
    raw = localStorage.getItem(keyFor(userId, entityType, entityId));
  } catch (e) {
    return emptyState();
  }
  if (!raw) return emptyState();
  try {
    const parsed = JSON.parse(raw);
    if (!parsed || !READABLE_VERSIONS.includes(parsed.version)) return emptyState();
    return {
      version: SCHEMA_VERSION,
      root: parsed.root && typeof parsed.root.body === 'string' ? parsed.root : null,
      reply: parsed.reply && typeof parsed.reply.body === 'string' && Number.isInteger(parsed.reply.parentCommentId)
        ? parsed.reply
        : null,
      annotations: Array.isArray(parsed.annotations) ? parsed.annotations.filter(isAnnotationItem) : [],
      annotationChanges: parsed.version === 1 ? emptyChanges() : normalizeChanges(parsed.annotationChanges),
    };
  } catch (e) {
    return emptyState();
  }
}

function persist(userId, entityType, entityId, state) {
  const key = keyFor(userId, entityType, entityId);
  try {
    if (isEmpty(state)) {
      localStorage.removeItem(key);
    } else {
      localStorage.setItem(key, JSON.stringify({ ...state, version: SCHEMA_VERSION }));
    }
  } catch (e) {
    // Quota exceeded or storage disabled — silently ignore. The form still works.
  }
}

export function saveRoot(userId, entityType, entityId, body) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  state.root = { body, savedAt: Date.now() };
  persist(userId, entityType, entityId, state);
}

export function clearRoot(userId, entityType, entityId) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  state.root = null;
  persist(userId, entityType, entityId, state);
}

export function saveReply(userId, entityType, entityId, parentCommentId, body) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  state.reply = { parentCommentId, body, savedAt: Date.now() };
  persist(userId, entityType, entityId, state);
}

export function clearReply(userId, entityType, entityId) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  state.reply = null;
  persist(userId, entityType, entityId, state);
}

// ---------- Annotations slot ----------

function generateTempId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`;
}

function writeAnnotations(userId, entityType, entityId, state, annotations) {
  state.annotations = annotations;
  persist(userId, entityType, entityId, state);
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('comment-drafts:annotations-changed', {
      detail: { entityType, entityId, count: annotations.length },
    }));
  }
}

export function listAnnotations(userId, entityType, entityId) {
  return load(userId, entityType, entityId).annotations;
}

export function addAnnotation(userId, entityType, entityId, { body, highlighted, prefix = '', suffix = '' }) {
  if (!userId) return null;
  const state = load(userId, entityType, entityId);
  const item = {
    tempId: generateTempId(),
    body: String(body ?? ''),
    highlighted: String(highlighted ?? ''),
    prefix: String(prefix ?? ''),
    suffix: String(suffix ?? ''),
  };
  writeAnnotations(userId, entityType, entityId, state, [...state.annotations, item]);
  return item;
}

export function updateAnnotation(userId, entityType, entityId, tempId, body) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  writeAnnotations(userId, entityType, entityId, state, state.annotations.map(
    (item) => (item.tempId === tempId ? { ...item, body: String(body ?? '') } : item),
  ));
}

export function removeAnnotation(userId, entityType, entityId, tempId) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  writeAnnotations(userId, entityType, entityId, state, state.annotations.filter((item) => item.tempId !== tempId));
}

export function clearAnnotations(userId, entityType, entityId) {
  if (!userId) return;
  writeAnnotations(userId, entityType, entityId, load(userId, entityType, entityId), []);
}

// ---------- AnnotationChanges slot (post-publish pending changes) ----------

function writeChanges(userId, entityType, entityId, state, changes) {
  state.annotationChanges = changes;
  persist(userId, entityType, entityId, state);
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('comment-drafts:annotation-changes-changed', {
      detail: { entityType, entityId, count: countChanges(changes) },
    }));
  }
}

function changeChanges(userId, entityType, entityId, mutate) {
  if (!userId) return;
  const state = load(userId, entityType, entityId);
  writeChanges(userId, entityType, entityId, state, mutate(state.annotationChanges));
}

export function getAnnotationChanges(userId, entityType, entityId) {
  return load(userId, entityType, entityId).annotationChanges;
}

export function countAnnotationChanges(userId, entityType, entityId) {
  return countChanges(getAnnotationChanges(userId, entityType, entityId));
}

export function addPendingAnnotation(userId, entityType, entityId, { body, highlighted, prefix = '', suffix = '' }) {
  if (!userId) return null;
  const item = {
    tempId: generateTempId(),
    body: String(body ?? ''),
    highlighted: String(highlighted ?? ''),
    prefix: String(prefix ?? ''),
    suffix: String(suffix ?? ''),
  };
  changeChanges(userId, entityType, entityId, (c) => ({ ...c, adds: [...c.adds, item] }));
  return item.tempId;
}

export function updatePendingAdd(userId, entityType, entityId, tempId, body) {
  changeChanges(userId, entityType, entityId, (c) => ({
    ...c,
    adds: c.adds.map((item) => (item.tempId === tempId ? { ...item, body: String(body ?? '') } : item)),
  }));
}

export function removePendingAdd(userId, entityType, entityId, tempId) {
  changeChanges(userId, entityType, entityId, (c) => ({ ...c, adds: c.adds.filter((item) => item.tempId !== tempId) }));
}

export function setPendingEdit(userId, entityType, entityId, id, body) {
  changeChanges(userId, entityType, entityId, (c) => ({ ...c, edits: { ...c.edits, [id]: String(body ?? '') } }));
}

export function undoPendingEdit(userId, entityType, entityId, id) {
  changeChanges(userId, entityType, entityId, (c) => {
    const edits = { ...c.edits };
    delete edits[id];
    return { ...c, edits };
  });
}

export function setPendingDelete(userId, entityType, entityId, id) {
  const annotationId = Number(id);
  changeChanges(userId, entityType, entityId, (c) => {
    const edits = { ...c.edits };
    delete edits[annotationId];
    const deletes = c.deletes.includes(annotationId) ? c.deletes : [...c.deletes, annotationId];
    return { ...c, edits, deletes };
  });
}

export function undoPendingDelete(userId, entityType, entityId, id) {
  const annotationId = Number(id);
  changeChanges(userId, entityType, entityId, (c) => ({ ...c, deletes: c.deletes.filter((d) => d !== annotationId) }));
}

export function clearAnnotationChanges(userId, entityType, entityId) {
  changeChanges(userId, entityType, entityId, () => emptyChanges());
}

/**
 * Flash-driven "this draft was just posted" marker, set by an inline script
 * before the Vite module runs. Applied before any restore so a deferred module
 * cannot repopulate the form from localStorage after a successful submit.
 */
function readConsumedMarker() {
  if (typeof window === 'undefined') return null;
  const payload = window.__commentDraftConsumed;
  if (!payload || typeof payload !== 'object') return null;
  const userId = Number(payload.userId);
  const entityType = payload.entityType;
  const entityId = payload.entityId != null ? String(payload.entityId) : null;
  const scope = payload.scope;
  if (!userId || !entityType || !entityId) return null;
  if (scope !== 'root' && scope !== 'reply') return null;
  return { scope, userId, entityType, entityId };
}

function applyConsumedMarker() {
  const payload = readConsumedMarker();
  if (!payload) return null;
  if (payload.scope === 'root') {
    // Annotations are posted with the root comment, so they are consumed with it.
    // annotationChanges are not: they belong to an already-posted root.
    clearRoot(payload.userId, payload.entityType, payload.entityId);
    clearAnnotations(payload.userId, payload.entityType, payload.entityId);
  } else {
    clearReply(payload.userId, payload.entityType, payload.entityId);
  }
  return payload;
}

function markerMatchesForm(payload, scope, userId, entityType, entityId) {
  if (!payload) return false;
  return payload.scope === scope
    && payload.userId === userId
    && payload.entityType === entityType
    && String(payload.entityId) === String(entityId);
}

// ---------- Auto-wire forms ----------

const DEBOUNCE_MS = 500;

function findEditorContainer(form) {
  // The shared editor exposes its container with `data-toolbar` (JSON).
  return form.querySelector('[data-toolbar]');
}

function isEditorEmpty(textareaValue) {
  if (!textareaValue) return true;
  // Strip tags, &nbsp;, and whitespace to know if there's any real text content.
  const stripped = textareaValue
    .replace(/<[^>]*>/g, '')
    .replace(/&nbsp;/g, ' ')
    .trim();
  return stripped.length === 0;
}

function restoreIntoEditor(editorContainer, textarea, body) {
  textarea.value = body;
  if (editorContainer.dataset.quillInited === '1') {
    // Editor is already up — trigger its reverse-sync handler.
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  }
  // Otherwise the editor will pick up `textarea.value` when initQuillEditor runs.
}

function initDraftForm(form) {
  if (form.dataset.commentDraftWired === '1') return;
  const scope = form.dataset.commentDraft;
  if (scope !== 'root' && scope !== 'reply') return;

  const userId = form.dataset.userId ? parseInt(form.dataset.userId, 10) : null;
  if (!userId) return;

  const entityType = form.dataset.entityType;
  const entityId = form.dataset.entityId;
  if (!entityType || !entityId) return;

  const parentCommentId = scope === 'reply'
    ? parseInt(form.dataset.parentCommentId, 10)
    : null;
  if (scope === 'reply' && !Number.isInteger(parentCommentId)) return;

  const editorContainer = findEditorContainer(form);
  if (!editorContainer) return;
  const textarea = document.getElementById('quill-editor-area-' + editorContainer.id);
  if (!textarea) return;

  form.dataset.commentDraftWired = '1';

  // ---- Restore ----
  // Honour a successful-submit consume marker before reading localStorage, so
  // the just-posted body cannot come back into a form that stays visible
  // (e.g. news roots). Honour `old()` next: if the textarea already has content
  // (validation error), don't clobber it with a draft.
  const consumed = applyConsumedMarker();
  const skipRestore = markerMatchesForm(consumed, scope, userId, entityType, entityId);
  if (!skipRestore && isEditorEmpty(textarea.value)) {
    const state = load(userId, entityType, entityId);
    if (scope === 'root' && state.root?.body) {
      restoreIntoEditor(editorContainer, textarea, state.root.body);
    } else if (scope === 'reply' && state.reply && state.reply.parentCommentId === parentCommentId) {
      restoreIntoEditor(editorContainer, textarea, state.reply.body);
    }
  }

  // ---- Save on input (debounced) ----
  let timer = null;
  let lastCount = null;
  form.addEventListener('editor-valid', (e) => {
    if (!e.detail || e.detail.id !== editorContainer.id) return;
    lastCount = e.detail.count;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
      timer = null;
      if (lastCount === 0) {
        if (scope === 'root') clearRoot(userId, entityType, entityId);
        else clearReply(userId, entityType, entityId);
        return;
      }
      const body = textarea.value;
      if (scope === 'root') saveRoot(userId, entityType, entityId, body);
      else saveReply(userId, entityType, entityId, parentCommentId, body);
    }, DEBOUNCE_MS);
  });

  // Flush pending save right before submit so we never lose the last keystrokes.
  form.addEventListener('submit', () => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
      const body = textarea.value;
      if (isEditorEmpty(body)) {
        if (scope === 'root') clearRoot(userId, entityType, entityId);
        else clearReply(userId, entityType, entityId);
      } else if (scope === 'root') {
        saveRoot(userId, entityType, entityId, body);
      } else {
        saveReply(userId, entityType, entityId, parentCommentId, body);
      }
    }
  });
}

export function bootstrap(root = document) {
  // Clear storage even when no form is present yet (Alpine may load() the
  // reply slot to decide whether to auto-open a composer).
  applyConsumedMarker();
  root.querySelectorAll('form[data-comment-draft]').forEach(initDraftForm);
}

// Auto-bootstrap on DOM ready.
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => bootstrap());
  } else {
    bootstrap();
  }
}

// Expose globally for: (a) post-submit flash-driven clear scripts, (b) Alpine
// `commentList` reading the reply slot to auto-open the form, (c) re-bootstrap
// after the infinite-scroll loader appends new comment fragments.
if (typeof window !== 'undefined') {
  applyConsumedMarker();
  window.commentDrafts = {
    load,
    saveRoot,
    clearRoot,
    saveReply,
    clearReply,
    listAnnotations,
    addAnnotation,
    updateAnnotation,
    removeAnnotation,
    clearAnnotations,
    getAnnotationChanges,
    countAnnotationChanges,
    addPendingAnnotation,
    updatePendingAdd,
    removePendingAdd,
    setPendingEdit,
    undoPendingEdit,
    setPendingDelete,
    undoPendingDelete,
    clearAnnotationChanges,
    bootstrap,
  };
}
