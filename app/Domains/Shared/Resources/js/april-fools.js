(function () {
  // --- Activation guard ---
  // fool=0 → explicit opt-out, never run
  // fool=1 → force-on
  // April 1st → on (unless fool=0)
  function shouldActivate() {
    const foolValue = localStorage.getItem('fool');
    if (foolValue === '0') return false;
    const now = new Date();
    const isAprilFool = now.getMonth() === 3 && now.getDate() === 1;
    return isAprilFool || foolValue === '1';
  }

  if (!shouldActivate()) return;

  // --- Inject styles ---
  const style = document.createElement('style');
  style.textContent = `
    .amp-fool {
      cursor: crosshair;
      display: inline;
      vertical-align: middle;
    }
    .amp-fool:hover { opacity: 0.75; }

    /* Opt-out toast */
    #amp-toast {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      background: #fff;
      border: 1px solid #d1c5a0;
      border-radius: 0.5rem;
      padding: 0.75rem 1rem;
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
      z-index: 100000;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      max-width: 300px;
      animation: amp-slidein 0.3s ease;
    }
    #amp-toast-off {
      background: #7c6f3e;
      color: #fff;
      border: none;
      border-radius: 0.25rem;
      padding: 0.3rem 0.7rem;
      cursor: pointer;
      font-size: 0.8rem;
      white-space: nowrap;
      flex-shrink: 0;
    }
    #amp-toast-off:hover { background: #5a5030; }
    #amp-toast-dismiss {
      background: none;
      border: none;
      color: #aaa;
      cursor: pointer;
      font-size: 1.1rem;
      line-height: 1;
      padding: 0;
      flex-shrink: 0;
    }
    #amp-toast-dismiss:hover { color: #555; }

    /* Endgame modal */
    #amp-modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 100001;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: amp-fadein 0.3s ease;
    }
    #amp-modal {
      background: #fff;
      border-radius: 1rem;
      padding: 2.5rem 2rem;
      max-width: 420px;
      width: 90%;
      text-align: center;
      box-shadow: 0 8px 40px rgba(0,0,0,0.25);
    }
    #amp-modal h2 {
      font-size: 3.5rem;
      font-weight: 800;
      color: #7c6f3e;
      margin: 0 0 0.75rem;
      letter-spacing: -1px;
      line-height: 1;
    }
    #amp-modal p {
      font-size: 1.1rem;
      margin: 0 0 2rem;
      line-height: 1.6;
      color: #444;
    }
    .amp-modal-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .amp-modal-buttons button {
      border: none;
      border-radius: 0.5rem;
      padding: 0.6rem 1.25rem;
      cursor: pointer;
      font-size: 0.95rem;
      font-weight: 600;
    }
    #amp-modal-disable {
      background: #7c6f3e;
      color: #fff;
    }
    #amp-modal-disable:hover { background: #5a5030; }
    #amp-modal-continue {
      background: #f0ead8;
      color: #5a5030;
    }
    #amp-modal-continue:hover { background: #e0d5b8; }

    @keyframes amp-fadein {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes amp-slidein {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  `;
  document.head.appendChild(style);

  // --- Constants ---
  const EMOJI_SIZE = 24; // px — inline display size
  const EMOJIS = [
    'esperamour', 'esperbravo', 'esperclindoeil', 'espercolere',
    'esperfourire', 'esperlunettes', 'espersnob', 'espersourire', 'espertriste',
  ].map(n => `/images/icons/emojis/${n}.png`);

  const E_CHARS = new Set([...'eéèêëEÉÈÊË']);
  const E_PATTERN = /[eéèêëEÉÈÊË]/;

  function randomEmoji() { return EMOJIS[Math.floor(Math.random() * EMOJIS.length)]; }

  // --- State ---
  let clickCount = 0;
  let toastShown = false;
  let endgameShown = false;

  // --- Stop: set fool=0 and reload ---
  function disable() {
    localStorage.setItem('fool', '0');
    location.reload();
  }

  // --- Opt-out toast (shown after 10 clicked &'s) ---
  function showToast() {
    if (toastShown) return;
    toastShown = true;
    const toast = document.createElement('div');
    toast.id = 'amp-toast';
    toast.innerHTML =
      '<span>Ça commence à faire beaucoup&hellip;</span>' +
      '<button id="amp-toast-off">Désactiver</button>' +
      '<button id="amp-toast-dismiss" aria-label="Fermer">&times;</button>';
    document.body.appendChild(toast);
    document.getElementById('amp-toast-off').addEventListener('click', disable);
    document.getElementById('amp-toast-dismiss').addEventListener('click', () => toast.remove());
  }

  // --- Endgame modal (fewer than 10 e's remaining) ---
  function showEndgame() {
    if (endgameShown) return;
    endgameShown = true;
    const backdrop = document.createElement('div');
    backdrop.id = 'amp-modal-backdrop';
    backdrop.innerHTML =
      '<div id="amp-modal">' +
        '<h2>Owned</h2>' +
        '<p>Souvenez-vous\u00a0: les esperluettes sont partout</p>' +
        '<div class="amp-modal-buttons">' +
          '<button id="amp-modal-disable">J\u2019ai compris, d\u00e9sactiver</button>' +
          '<button id="amp-modal-continue">Continuer</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(backdrop);
    document.getElementById('amp-modal-disable').addEventListener('click', disable);
    document.getElementById('amp-modal-continue').addEventListener('click', () => backdrop.remove());
  }

  // --- Collect all individual e-character positions in text nodes ---
  function collectCandidates() {
    const results = [];
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_TEXT,
      {
        acceptNode(node) {
          if (!E_PATTERN.test(node.nodeValue)) return NodeFilter.FILTER_SKIP;
          const parent = node.parentElement;
          if (!parent) return NodeFilter.FILTER_SKIP;
          const tag = parent.tagName.toLowerCase();
          if (['script', 'style', 'noscript', 'textarea', 'input'].includes(tag)) return NodeFilter.FILTER_SKIP;
          if (parent.isContentEditable) return NodeFilter.FILTER_SKIP;
          if (parent.closest('.amp-fool')) return NodeFilter.FILTER_SKIP;
          return NodeFilter.FILTER_ACCEPT;
        },
      }
    );

    let node;
    while ((node = walker.nextNode())) {
      const text = node.nodeValue;
      for (let i = 0; i < text.length; i++) {
        if (E_CHARS.has(text[i])) results.push({ node, index: i });
      }
    }
    return results;
  }

  // --- Replace a random e with a clickable & ---
  function replaceRandomE() {
    const candidates = collectCandidates();
    if (candidates.length === 0) return;

    const { node, index } = candidates[Math.floor(Math.random() * candidates.length)];
    const text = node.nodeValue;

    const span = document.createElement('img');
    span.className = 'amp-fool';
    span.src = randomEmoji();
    span.alt = '&';
    span.width = EMOJI_SIZE;
    span.height = EMOJI_SIZE;
    span.dataset.original = text[index];
    span.addEventListener('click', (e) => { e.stopPropagation(); fallDown(span); });

    const parent = node.parentNode;
    if (!parent) return;

    const before = text.slice(0, index);
    const after = text.slice(index + 1);
    if (before) parent.insertBefore(document.createTextNode(before), node);
    parent.insertBefore(span, node);
    if (after) parent.insertBefore(document.createTextNode(after), node);
    parent.removeChild(node);

    // After replacement: remaining count is candidates.length - 1
    if (candidates.length - 1 < 10) showEndgame();
  }

  // --- Falling animation: gravity + spin + splat ---
  function fallDown(span) {
    clickCount++;
    if (clickCount >= 10) showToast();

    const rect = span.getBoundingClientRect();
    const src = span.src;
    const original = span.dataset.original;
    span.replaceWith(document.createTextNode(original));

    const flyer = document.createElement('img');
    flyer.src = src;
    flyer.width = EMOJI_SIZE;
    flyer.height = EMOJI_SIZE;
    flyer.style.cssText =
      `position:fixed;left:${rect.left}px;top:${rect.top}px;` +
      `pointer-events:none;z-index:99999;transform-origin:center center;`;
    document.body.appendChild(flyer);

    const startY = rect.top;
    const landY = window.innerHeight - EMOJI_SIZE * 1.2;
    const totalDistance = landY - startY;
    const duration = 900 + Math.random() * 300;
    const startTime = performance.now();

    function animate(now) {
      const elapsed = now - startTime;
      const t = Math.min(elapsed / duration, 1);
      const easedT = t * t;
      flyer.style.top = `${startY + totalDistance * easedT}px`;
      flyer.style.transform = `rotate(${t * 540}deg) scale(${1 + t * 0.8})`;
      if (t < 1) {
        requestAnimationFrame(animate);
      } else {
        flyer.remove();
        splat(rect.left, landY, src);
      }
    }
    requestAnimationFrame(animate);
  }

  function splat(x, y, src) {
    const el = document.createElement('img');
    el.src = src;
    el.width = EMOJI_SIZE;
    el.height = EMOJI_SIZE;
    el.style.cssText =
      `position:fixed;left:${x}px;top:${y}px;` +
      `pointer-events:none;z-index:99999;transform-origin:bottom center;`;
    document.body.appendChild(el);

    const startTime = performance.now();
    const squishDuration = 200;
    const holdDuration = 400;
    const fadeDuration = 500;
    const total = squishDuration + holdDuration + fadeDuration;

    function animateSplat(now) {
      const elapsed = now - startTime;
      if (elapsed < squishDuration) {
        const st = elapsed / squishDuration;
        el.style.transform = `scaleX(${1 + st * 2.5}) scaleY(${1 - st * 0.75})`;
        el.style.opacity = '1';
      } else if (elapsed < squishDuration + holdDuration) {
        el.style.transform = `scaleX(3.5) scaleY(0.25)`;
        el.style.opacity = '1';
      } else {
        const ft = (elapsed - squishDuration - holdDuration) / fadeDuration;
        el.style.transform = `scaleX(3.5) scaleY(0.25)`;
        el.style.opacity = String(1 - ft);
      }
      if (elapsed < total) {
        requestAnimationFrame(animateSplat);
      } else {
        el.remove();
      }
    }
    requestAnimationFrame(animateSplat);
  }

  // --- Escalating scheduler ---
  let delay = 10_000;
  const minDelay = 50;
  const decayFactor = 0.9;

  function scheduleNext() {
    setTimeout(() => {
      replaceRandomE();
      delay = Math.max(minDelay, delay * decayFactor);
      scheduleNext();
    }, delay);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleNext);
  } else {
    scheduleNext();
  }
})();
