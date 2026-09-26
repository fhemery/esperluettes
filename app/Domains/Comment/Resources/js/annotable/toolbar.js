/**
 * Generic selection toolbar — detects text selection inside .annotable-region
 * elements and positions a cloned toolbar near the selection.
 *
 * The toolbar template (#comment-toolbar-template) is server-rendered by the
 * <x-comment::annotable> component. Each button inside the template carries its
 * own Alpine x-data / @click binding managed by the contributing domain.
 *
 * An action (an element child of [data-toolbar-actions], or a descendant of
 * one) may carry data-requires-selection-within="<css selector>": it is then
 * shown only when every non-whitespace text node touched by the selection lies
 * inside an element matching that selector. Actions without the attribute are
 * always shown. When no action applies, the toolbar is not shown at all. The
 * selector belongs to the contributing domain; this module never knows it.
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

const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

function positionToolbar(toolbar, range) {
    const rect = range.getBoundingClientRect();
    const scrollX = window.scrollX || window.pageXOffset;
    const scrollY = window.scrollY || window.pageYOffset;

    // On touch devices, the browser's native selection action bar (Copy/Paste/...)
    // renders above the selection, so placing our toolbar there gets covered by it
    // (observed on Firefox for Android). Place it below the selection instead.
    const top = isTouchDevice
        ? rect.bottom + scrollY + 8
        : rect.top + scrollY - toolbar.offsetHeight - 8;
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

export function selectionIsWithin(range, selector) {
    const root = range.commonAncestorContainer;
    const nodes = [];
    if (root.nodeType === Node.TEXT_NODE) {
        nodes.push(root);
    } else {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) nodes.push(walker.currentNode);
    }

    for (const node of nodes) {
        if (!range.intersectsNode(node) || !node.textContent.trim()) continue;
        if (!node.parentElement?.closest(selector)) return false;
    }
    return true;
}

const REQUIRES_ATTR = 'data-requires-selection-within';

export function applyActionApplicability(toolbar, range) {
    const actions = toolbar.querySelector('[data-toolbar-actions]');
    if (!actions) return 0;

    let visible = 0;
    for (const action of actions.children) {
        const declaring = action.hasAttribute(REQUIRES_ATTR)
            ? action
            : action.querySelector(`[${REQUIRES_ATTR}]`);
        const applicable = !declaring
            || selectionIsWithin(range, declaring.getAttribute(REQUIRES_ATTR));
        action.style.display = applicable ? '' : 'none';
        if (applicable) visible++;
    }
    return visible;
}

export function showToolbar() {
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

    // No applicable action: show nothing rather than an empty bubble.
    if (applyActionApplicability(toolbar, range) === 0) {
        hideToolbar();
        return;
    }

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

export function hideToolbar() {
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
