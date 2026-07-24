/**
 * Generic selection toolbar — detects text selection inside .annotable-region
 * elements and positions a cloned toolbar near the selection.
 *
 * The toolbar template (#comment-toolbar-template) is server-rendered by the
 * <x-comment::annotable> component. Each button inside the template carries its
 * own Alpine x-data / @click binding managed by the contributing domain.
 */

const TOOLBAR_ID = 'comment-toolbar-active';

function getOrCreateToolbar() {
    let el = document.getElementById(TOOLBAR_ID);
    if (el) return el;

    const template = document.getElementById('comment-toolbar-template');
    if (!template) return null;

    el = template.content.cloneNode(true).firstElementChild;
    el.id = TOOLBAR_ID;
    el.style.position = 'absolute';
    el.style.zIndex = '9999';
    el.style.display = 'none';
    document.body.appendChild(el);

    if (window.Alpine) {
        Alpine.initTree(el);
    }

    return el;
}

function positionToolbar(toolbar, range) {
    const rect = range.getBoundingClientRect();
    const scrollX = window.scrollX || window.pageXOffset;
    const scrollY = window.scrollY || window.pageYOffset;

    const top = rect.top + scrollY - toolbar.offsetHeight - 8;
    const left = rect.left + scrollX + rect.width / 2 - toolbar.offsetWidth / 2;

    toolbar.style.top = Math.max(0, top) + 'px';
    toolbar.style.left = Math.max(0, left) + 'px';
}

function getAnnotableRegion(node) {
    let el = node.nodeType === 3 ? node.parentElement : node;
    return el?.closest('[data-annotable]') ?? null;
}

function setTooLongState(toolbar, tooLong) {
    const actions = toolbar.querySelector('[data-toolbar-actions]');
    const message = toolbar.querySelector('[data-toolbar-too-long]');
    if (actions) actions.classList.toggle('hidden', tooLong);
    if (message) message.classList.toggle('hidden', !tooLong);
}

function showToolbar() {
    const selection = window.getSelection();
    if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
        hideToolbar();
        return;
    }

    const range = selection.getRangeAt(0);
    const region = getAnnotableRegion(range.commonAncestorContainer);
    if (!region || region.dataset.canAnnotate !== 'true') {
        hideToolbar();
        return;
    }

    const text = selection.toString().trim();
    if (!text) {
        hideToolbar();
        return;
    }

    const toolbar = getOrCreateToolbar();
    if (!toolbar) return;

    // A selection longer than the region's cap disables the actions and shows
    // a "selection too long" hint instead. The cap lives on the region so the
    // generic toolbar stays feature-agnostic.
    const maxSelection = parseInt(region.dataset.maxSelection ?? '0', 10);
    const tooLong = maxSelection > 0 && text.length > maxSelection;
    setTooLongState(toolbar, tooLong);

    toolbar.style.display = '';
    positionToolbar(toolbar, range);

    toolbar.dataset.entityType = region.dataset.entityType;
    toolbar.dataset.entityId = region.dataset.entityId;
}

function hideToolbar() {
    const toolbar = document.getElementById(TOOLBAR_ID);
    if (toolbar) toolbar.style.display = 'none';
}

document.addEventListener('mouseup', (e) => {
    if (e.target.closest('#' + TOOLBAR_ID)) return;
    setTimeout(showToolbar, 0);
});

document.addEventListener('touchend', (e) => {
    if (e.target.closest('#' + TOOLBAR_ID)) return;
    setTimeout(showToolbar, 50);
});

document.addEventListener('selectionchange', () => {
    const selection = window.getSelection();
    if (selection && selection.isCollapsed) {
        hideToolbar();
    }
});
