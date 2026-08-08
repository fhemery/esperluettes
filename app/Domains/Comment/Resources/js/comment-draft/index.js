// Comment domain — draft local-storage autosave.
//
// One key per (user, entityType, entityId) holds an object with three slots:
//   - root:        { body, savedAt }         | null  — the root-comment editor draft
//   - reply:       { parentCommentId, body, savedAt } | null  — a single reply-in-progress
//   - annotations: [...]                              — reserved for phase 11 (Chapter Annotations)
//
// Forms opt in by adding `data-comment-draft="root"` (or `="reply"`) plus
// `data-user-id`, `data-entity-type`, `data-entity-id`, and for replies `data-parent-comment-id`.
// The Quill editor inside the form is discovered via its `data-toolbar` attribute.

const SCHEMA_VERSION = 1;

function keyFor(userId, entityType, entityId) {
  return `comment-drafts:${userId}:${entityType}:${entityId}`;
}

function emptyState() {
  return { version: SCHEMA_VERSION, root: null, reply: null, annotations: [] };
}

function isEmpty(state) {
  return !state.root
    && !state.reply
    && (!Array.isArray(state.annotations) || state.annotations.length === 0);
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
    if (!parsed || parsed.version !== SCHEMA_VERSION) return emptyState();
    return {
      version: SCHEMA_VERSION,
      root: parsed.root && typeof parsed.root.body === 'string' ? parsed.root : null,
      reply: parsed.reply && typeof parsed.reply.body === 'string' && Number.isInteger(parsed.reply.parentCommentId)
        ? parsed.reply
        : null,
      annotations: Array.isArray(parsed.annotations) ? parsed.annotations : [],
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
    clearRoot(payload.userId, payload.entityType, payload.entityId);
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
    bootstrap,
  };
}
