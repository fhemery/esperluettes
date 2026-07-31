/**
 * VERIFY — editor-domain, rows 7-20: every editing surface.
 *
 * For each page: it loads, the editor(s) initialise (Quill really runs — a
 * `.ql-toolbar` + `.ql-editor` exist), the toolbar tokens are token-for-token
 * what the pre-refactor literal produced, and `editor-bundle.js` / `editor.css`
 * are declared exactly once.
 *
 * Driven as fred@hemit.fr (tech-admin).
 */

const DEFAULT = ['bold', 'italic', 'underline', 'strike', 'blockquote', 'align', 'list', 'custom-emoji'];
const LINKS = [...DEFAULT, 'link'];
const EDITORIAL = ['bold', 'italic', 'underline', 'strike', 'header', 'blockquote', 'align', 'list', 'custom-emoji', 'link'];
const NARRATIVE = [...DEFAULT, 'link', 'spoiler'];

/** Everything Quill-related the page ended up with. */
const probe = (page) => page.evaluate(() => {
  const re = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;
  const declared = Array.from(document.querySelectorAll('script[src],link[rel=stylesheet]'))
    .map((e) => new URL(e.src || e.href).pathname).filter((p) => re.test(p));

  const editors = Array.from(document.querySelectorAll('[data-toolbar]')).map((host) => {
    const wrap = host.closest('.rich-content');
    const toolbar = wrap && wrap.querySelector('.ql-toolbar');
    const qlEditor = wrap && wrap.querySelector('.ql-editor');
    const tokens = toolbar
      ? [...new Set(Array.from(toolbar.querySelectorAll('button, .ql-picker'))
        .map((b) => (b.className.match(/\bql-([a-z-]+)/) || [])[1])
        .filter((t) => t && t !== 'picker' && t !== 'expanded'))]
      : null;
    const counter = document.getElementById('quill-counter-wrap-' + host.id);
    const qlContainer = wrap && wrap.querySelector('.ql-container');
    return {
      id: host.id,
      declaredToolbar: JSON.parse(host.dataset.toolbar || '[]'),
      live: !!qlEditor && !!toolbar,
      tokens,
      toolbarPadding: toolbar && getComputedStyle(toolbar).padding,
      toolbarBorder: toolbar && getComputedStyle(toolbar).borderBottomWidth,
      editorHeight: qlEditor && Math.round(qlEditor.getBoundingClientRect().height),
      // editor-bundle.js sets `resize: vertical` on `.ql-container`, not `.ql-editor`
      resizable: qlContainer && qlContainer.style.resize,
      indent: !!(wrap && wrap.querySelector('.ql-indent')),
      counterText: counter && counter.textContent.replace(/\s+/g, ' ').trim(),
      spoilerBtn: !!(toolbar && toolbar.querySelector('button.ql-spoiler')),
      spoilerBtnStyled: (() => {
        const b = toolbar && toolbar.querySelector('button.ql-spoiler');
        if (!b) return null;
        const svg = b.querySelector('svg');
        // On the chapter *create* page the author-note block starts collapsed
        // (display:none), so computed sizes read `auto`; only assert when shown.
        return { visible: !!b.offsetParent, bg: getComputedStyle(b).backgroundColor, svgW: svg && getComputedStyle(svg).width };
      })(),
      linkAttrs: (() => {
        const w = wrap;
        return w && w.dataset.linkVisit ? { visit: w.dataset.linkVisit, enter: w.dataset.linkEnter, edit: w.dataset.linkEdit, save: w.dataset.linkSave, remove: w.dataset.linkRemove } : null;
      })(),
    };
  });
  return { declared, editors };
});

const dedup = (declared) => ({
  bundleJs: declared.filter((p) => /editor-bundle-[A-Za-z0-9_]+\.js$/.test(p)).length,
  editorCss: declared.filter((p) => /\/editor-[A-Za-z0-9_]+\.css$/.test(p)).length,
  bundleCss: declared.filter((p) => /editor-bundle-[A-Za-z0-9_]+\.css$/.test(p)).length,
});

export default async function ({ page, helpers, args }) {
  const { login, goto, shot, check } = helpers;
  const only = args.find((a) => a.startsWith('--only='))?.split('=')[1];
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  await login(page);

  /** Visit a page, assert the editors on it. */
  async function surface(row, url, expected, extra = {}) {
    if (only && !row.startsWith(only)) return null;
    errors.length = 0;
    const st = await goto(page, url);
    await page.waitForTimeout(2500);
    const p = await probe(page);
    const d = dedup(p.declared);
    console.log(`  ${row} ${url}  status=${st}\n    ` + JSON.stringify({ dedup: d, editors: p.editors }, null, 1).replace(/\n/g, '\n    '));
    check(`${row} page loads`, st === 200, `status ${st}`);
    check(`${row} no JS page error`, errors.length === 0, errors.join(' | '));

    if (expected === null) {
      check(`${row} no editor on the page`, p.editors.length === 0, `${p.editors.length} editor(s)`);
      check(`${row} no editor asset loaded`, p.declared.length === 0, p.declared.join(', ') || 'none');
    } else {
      const list = Array.isArray(expected[0]) ? expected : [expected];
      check(`${row} ${list.length} editor(s) present`, p.editors.length >= list.length,
        `${p.editors.length} found`);
      check(`${row} every editor is live (Quill ran)`, p.editors.length > 0 && p.editors.every((e) => e.live),
        JSON.stringify(p.editors.map((e) => [e.id, e.live])));
      p.editors.slice(0, list.length).forEach((e, i) => {
        // `data-toolbar` is the token list the preset resolved to — this is the
        // thing phase 3 changed, and it must equal the pre-refactor literal.
        check(`${row} declared toolbar[${i}] (${e.id})`,
          JSON.stringify(e.declaredToolbar) === JSON.stringify(list[i]),
          `got ${JSON.stringify(e.declaredToolbar)} want ${JSON.stringify(list[i])}`);
        // and Quill must actually render a control per token. `custom-emoji`
        // renders as `.ql-emoji`; Quill always appends its own `clean`.
        const want = new Set(list[i].map((t) => (t === 'custom-emoji' ? 'emoji' : t)).concat('clean'));
        const got = new Set(e.tokens || []);
        check(`${row} rendered toolbar[${i}] (${e.id})`,
          want.size === got.size && [...want].every((t) => got.has(t)),
          `got ${JSON.stringify([...got])} want ${JSON.stringify([...want])}`);
      });
      check(`${row} editor.css declared once`, d.editorCss === 1, `${d.editorCss}`);
      check(`${row} editor-bundle.js declared once`, d.bundleJs === 1, `${d.bundleJs}`);
      if (extra.assert) extra.assert(p, check);
    }
    await shot(page, extra.shotName || row);
    return p;
  }

  // ---------------------------------------------------------------- rows 7-8
  // SecretGift: the page's hand-written @vite was deleted (decision #8), and the
  // page only sometimes has an editor. Seeded: `qa-secret-gift-active` (fred is a
  // participant with an assignment as giver -> gift preparation, editor present)
  // and `qa-secret-gift-preview` (preview phase -> no editor).
  await surface('row07-secretgift-with-editor', '/activities/qa-secret-gift-active', DEFAULT, {
    assert: (p, c) => c('row07 gift editor is live', p.editors.every((e) => e.live), JSON.stringify(p.editors.map((e) => [e.id, e.live]))),
  });
  await surface('row08-secretgift-no-editor', '/activities/qa-secret-gift-preview', null);

  // ---------------------------------------------------------------- row 9
  await surface('row09-calendar-create', '/admin/calendar/activities/create', LINKS, {
    assert: (p, c) => c('row09 link tooltip labels in French',
      !!p.editors[0].linkAttrs && /Visiter|Entrer|Modifier|Enregistrer|Supprimer/.test(Object.values(p.editors[0].linkAttrs).join(' ')),
      JSON.stringify(p.editors[0].linkAttrs)),
  });
  await surface('row09-calendar-edit', '/admin/calendar/activities/6/edit', LINKS);

  // ---------------------------------------------------------------- row 11
  await surface('row11-faq-create', '/admin/faq/faq-questions/create', EDITORIAL);
  await surface('row11-faq-edit', '/admin/faq/faq-questions/1/edit', EDITORIAL);

  // ---------------------------------------------------------------- row 12
  await surface('row12-message-compose', '/messages/compose', DEFAULT);

  // ---------------------------------------------------------------- rows 13-14
  await surface('row13-news-create', '/admin/news/create', EDITORIAL);
  await surface('row13-news-edit', '/admin/news/3/edit', EDITORIAL);
  await surface('row14-news-multi-edit', '/admin/news/4/edit', EDITORIAL);

  // ---------------------------------------------------------------- row 15
  await surface('row15-profile-edit', '/profile/edit', DEFAULT, {
    assert: (p, c) => {
      const e = p.editors[0];
      c('row15 counter shows the 1000-char max', /\/ 1000/.test(e.counterText || ''), e.counterText);
      c('row15 editor is ~10 lines tall', e.editorHeight > 150 && e.editorHeight < 400, `${e.editorHeight}px`);
    },
  });

  // ---------------------------------------------------------------- row 16
  await surface('row16-staticpage-create', '/admin/static-pages/create', EDITORIAL);
  await surface('row16-staticpage-edit', '/admin/static-pages/1/edit', EDITORIAL);

  // ---------------------------------------------------------------- row 17
  await surface('row17-story-create', '/stories/create', DEFAULT, {
    assert: (p, c) => c('row17 counter shows 100/1000 min-max',
      /\/ 1000/.test(p.editors[0].counterText || '') && /100/.test(p.editors[0].counterText || ''),
      p.editors[0].counterText),
  });
  await surface('row17-story-edit', '/stories/le-crepuscule-des-as-1/edit', DEFAULT, {
    assert: (p, c) => c('row17 edit counter shows 100/1000',
      /\/ 1000/.test(p.editors[0].counterText || ''), p.editors[0].counterText),
  });

  // ---------------------------------------------------------------- row 18
  const chapAssert = (p, c) => {
    const note = p.editors.find((e) => JSON.stringify(e.declaredToolbar) === JSON.stringify(NARRATIVE));
    const content = p.editors.find((e) => JSON.stringify(e.declaredToolbar) === JSON.stringify(LINKS));
    c('row18 author note uses the narrative preset', !!note, p.editors.map((e) => e.id).join(', '));
    c('row18 spoiler button present in the author note toolbar', !!note && note.spoilerBtn, JSON.stringify(note && note.spoilerBtnStyled));
    c('row18 spoiler button styled from editor.css',
      !!note && note.spoilerBtnStyled &&
        (!note.spoilerBtnStyled.visible || note.spoilerBtnStyled.svgW === '18px') &&
        note.spoilerBtnStyled.bg === 'rgba(128, 128, 128, 0.25)',
      JSON.stringify(note && note.spoilerBtnStyled));
    c('row18 content uses the links preset', !!content, p.editors.map((e) => e.id).join(', '));
    c('row18 content editor keeps indentParagraphs', !!content && content.indent, `${content && content.indent}`);
    c('row18 editors are resizable', p.editors.every((e) => e.resizable === 'vertical' || e.resizable === 'both'),
      p.editors.map((e) => e.resizable).join(', '));
  };
  await surface('row18-chapter-create', '/stories/le-crepuscule-des-as-1/chapters/create', [NARRATIVE, LINKS], { assert: chapAssert });
  await surface('row18-chapter-edit', '/stories/le-crepuscule-des-as-1/chapters/chapitre-1/edit', [NARRATIVE, LINKS], { assert: chapAssert });

  // ---------------------------------------------------------------- row 19
  if (!only || only.startsWith('row19')) {
    await page.setViewportSize({ width: 375, height: 800 });
    await goto(page, '/stories/le-crepuscule-des-as-1/chapters/chapitre-1/edit');
    await page.waitForTimeout(1800);
    const m = await probe(page);
    const wrapped = await page.evaluate(() => {
      const t = document.querySelector('.ql-toolbar');
      if (!t) return null;
      const rows = new Set(Array.from(t.querySelectorAll('.ql-formats')).map((f) => Math.round(f.getBoundingClientRect().top)));
      const counter = document.querySelector('[id^="quill-counter-wrap-"]');
      return {
        toolbarW: Math.round(t.getBoundingClientRect().width),
        rows: rows.size,
        overflowsViewport: t.getBoundingClientRect().right > window.innerWidth + 1,
        counterVisible: counter ? counter.getBoundingClientRect().height > 0 : false,
      };
    });
    console.log('  row19 mobile  ' + JSON.stringify(wrapped));
    check('row19 mobile editors live', m.editors.length > 0 && m.editors.every((e) => e.live),
      JSON.stringify(m.editors.map((e) => e.live)));
    check('row19 toolbar wraps inside the 375px viewport', !!wrapped && !wrapped.overflowsViewport && wrapped.rows > 1,
      JSON.stringify(wrapped));
    check('row19 counter visible', !!wrapped && wrapped.counterVisible, JSON.stringify(wrapped));
    await shot(page, 'row19-mobile-chapter-edit');
    await page.setViewportSize({ width: 1280, height: 900 });
  }

  // ---------------------------------------------------------------- row 20
  if (!only || only.startsWith('row20')) {
    await goto(page, '/stories/le-crepuscule-des-as-1/chapters/chapitre-1/edit');
    await page.waitForTimeout(1500);
    // The app themes via <html data-appearance> / data-season (layouts/app.blade.php).
    const readTheme = () => page.evaluate(() => {
      const t = document.querySelector('.ql-toolbar');
      const e = document.querySelector('.ql-editor');
      const surface = e.closest('.surface-read');
      const btn = t.querySelector('button.ql-bold');
      const tip = document.querySelector('.ql-tooltip');
      return {
        appearance: document.documentElement.dataset.appearance,
        toolbarBg: getComputedStyle(t).backgroundColor,
        toolbarBorder: getComputedStyle(t).borderBottomColor,
        buttonFg: btn && getComputedStyle(btn).color,
        surfaceBg: surface && getComputedStyle(surface).backgroundColor,
        editorFg: getComputedStyle(e).color,
        tooltipBg: tip && getComputedStyle(tip).backgroundColor,
      };
    });
    const light = await readTheme();
    await shot(page, 'row20-theme-light');
    await page.evaluate(() => { document.documentElement.dataset.appearance = 'dark'; });
    await page.waitForTimeout(600);
    const dark = await readTheme();
    console.log(`  row20 theme\n    light=${JSON.stringify(light)}\n    dark=${JSON.stringify(dark)}`);
    check('row20 theme actually flipped', light.appearance !== dark.appearance,
      `${light.appearance} -> ${dark.appearance}`);
    check('row20 editor surface follows the theme', light.surfaceBg !== dark.surfaceBg,
      `${light.surfaceBg} -> ${dark.surfaceBg}`);
    check('row20 editor text follows the theme', light.editorFg !== dark.editorFg,
      `${light.editorFg} -> ${dark.editorFg}`);
    await shot(page, 'row20-theme-dark');
  }
}
