/**
 * VERIFY — editor-domain, rows 3(profile) and 4-8: the AJAX-fragment risk (R1),
 * asset de-duplication, and the SecretGift call site.
 *
 * Driven as fred@hemit.fr (tech-admin + user-confirmed).
 */

const EDITOR_ASSET = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;

function trackAssets(page) {
  let seen = [];
  const handler = (r) => {
    const u = new URL(r.url()).pathname;
    if (EDITOR_ASSET.test(u)) seen.push(`${u}`);
  };
  page.on('response', handler);
  return { reset: () => (seen = []), list: () => [...seen] };
}

/** What the *document* declares, regardless of HTTP cache. */
const declaredEditorAssets = (page) => page.evaluate(() => {
  const re = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;
  const css = Array.from(document.querySelectorAll('link[rel=stylesheet]'))
    .map((l) => new URL(l.href).pathname).filter((p) => re.test(p));
  const js = Array.from(document.querySelectorAll('script[src]'))
    .map((s) => new URL(s.src).pathname).filter((p) => re.test(p));
  return { css, js };
});

/** A Quill instance is live when the container has a .ql-editor and a .ql-toolbar sibling. */
const editorState = (page, id) => page.evaluate((eid) => {
  const host = document.getElementById(eid);
  if (!host) return { found: false };
  const root = host.closest('.rich-content') || host.parentElement;
  const qlEditor = root.querySelector('.ql-editor');
  const toolbar = root.querySelector('.ql-toolbar');
  const btns = toolbar ? Array.from(toolbar.querySelectorAll('button, .ql-picker'))
    .map((b) => (b.className.match(/ql-([a-z-]+)/) || [])[1]).filter(Boolean) : [];
  const tCs = toolbar ? getComputedStyle(toolbar) : null;
  return {
    found: true,
    hasEditor: !!qlEditor,
    hasToolbar: !!toolbar,
    tokens: [...new Set(btns)],
    // If editor.css never loaded, the toolbar exists but Quill's own snow theme
    // border/padding come from the bundle's CSS; check both are non-trivial.
    toolbarBorder: tCs && tCs.borderBottomWidth,
    toolbarPadding: tCs && tCs.padding,
    editorMinHeight: qlEditor && getComputedStyle(qlEditor).minHeight,
  };
}, id);

export default async function ({ page, helpers }) {
  const { login, goto, shot, check } = helpers;
  const assets = trackAssets(page);
  await login(page);

  const CHAPTER = '/stories/le-crepuscule-des-as-1/chapters/chapitre-1';

  // ------------------------------------------------- row 3 (profile, logged in)
  await goto(page, '/profile/qa-admin/about');
  const pr = await page.evaluate(() => {
    const find = (m) => Array.from(document.querySelectorAll('p,h2,li'))
      .find((e) => e.textContent.trim().startsWith(m));
    const rc = document.querySelector('.rich-content');
    const em = document.querySelector('.ql-custom-emoji');
    const link = Array.from(document.querySelectorAll('a')).find((a) => a.textContent.trim() === 'un lien');
    const c = find('QA-PROF-CENTER'), li = find('QA-PROF-UL');
    return {
      rich: !!rc, lineHeight: rc && getComputedStyle(rc).lineHeight,
      center: c && getComputedStyle(c).textAlign,
      list: li && getComputedStyle(li.parentElement).listStyleType,
      linkDeco: link && getComputedStyle(link).textDecorationLine,
      emoji: em && { d: getComputedStyle(em).display, bg: getComputedStyle(em).backgroundImage !== 'none' },
    };
  });
  console.log('  row3 profile  ' + JSON.stringify(pr));
  const decl3 = await declaredEditorAssets(page);
  check('row3 profile: no editor asset in the document',
    decl3.css.length === 0 && decl3.js.length === 0, JSON.stringify(decl3));
  check('row3 profile .rich-content typography', pr.rich && pr.lineHeight !== 'normal', pr.lineHeight);
  check('row3 profile centred line', pr.center === 'center', pr.center);
  check('row3 profile list marker', pr.list === 'disc', pr.list);
  check('row3 profile link underlined', pr.linkDeco === 'underline', pr.linkDeco);
  check('row3 profile emoji has background-image', !!pr.emoji && pr.emoji.bg, JSON.stringify(pr.emoji));
  await shot(page, 'row3-profile-about');

  // ------------------------------------------------- rows 4-6: chapter comments
  //
  // Risk R1: a @push executed while rendering an AJAX fragment is discarded, so
  // the assets must come from the page-level render. Run this flow BOTH as a
  // user who can create a root comment (page-level editor present) and as one
  // who cannot (story author, or anyone who already posted a root comment).
  assets.reset();
  const cs = await goto(page, CHAPTER);
  check('rows4-6 chapter 200', cs === 200, `status ${cs}`);

  // the comment list arrives through an x-intersect sentinel at the bottom
  for (let i = 0; i < 12; i++) {
    await page.evaluate(() => window.scrollBy(0, document.body.scrollHeight));
    await page.waitForTimeout(350);
  }
  await page.waitForTimeout(1500);

  const state = await page.evaluate(() => {
    const re = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;
    const declared = Array.from(document.querySelectorAll('script[src],link[rel=stylesheet]'))
      .map((e) => new URL(e.src || e.href).pathname).filter((p) => re.test(p));
    return {
      canCreateRoot: !!document.getElementById('comment-body-editor'),
      items: document.querySelectorAll('#comment-list ul > li').length,
      fragmentEditors: Array.from(document.querySelectorAll('[id^="edit-editor-"],[id^="reply-editor-"]')).map((e) => e.id).length,
      declared,
      bundleJs: declared.filter((p) => /editor-bundle-[A-Za-z0-9_]+\.js$/.test(p)).length,
      editorCss: declared.filter((p) => /\/editor-[A-Za-z0-9_]+\.css$/.test(p)).length,
      quill: typeof window.Quill,
      initQuillEditor: typeof window.initQuillEditor,
    };
  });
  console.log(`  [${process.env.APP_USER}] rows4-6 state  ` + JSON.stringify(state));
  check('rows4-6 the AJAX comment fragment loaded', state.items > 0, `${state.items} items`);
  check('rows4-6 the fragment contains edit/reply editor containers', state.fragmentEditors > 0,
    `${state.fragmentEditors}`);

  // THE row-4/5 assertion: whatever the root-comment permission, a page that
  // shows edit/reply affordances must have the editor runtime available.
  check('rows4-5 editor runtime available for the fragment editors',
    state.initQuillEditor === 'function' && state.quill === 'function',
    JSON.stringify({ canCreateRoot: state.canCreateRoot, quill: state.quill, initQuillEditor: state.initQuillEditor, declared: state.declared }));

  // row 6: whatever happens, never more than one copy of each asset.
  check('row6 editor-bundle.js declared at most once', state.bundleJs <= 1, `${state.bundleJs}`);
  check('row6 editor.css declared at most once', state.editorCss <= 1, `${state.editorCss}`);
  await shot(page, `row4-comment-list-${state.canCreateRoot ? 'can-root' : 'cannot-root'}`);

  // ---- row 5: open a reply form inside the fragment
  await page.locator('#comment-list button', { hasText: 'Répondre' }).first().click();
  await page.waitForTimeout(2000);
  const reply = await page.evaluate(() => {
    const c = document.querySelector('[id^="reply-editor-"]');
    const root = c && (c.closest('.rich-content') || c.parentElement);
    const tb = root && root.querySelector('.ql-toolbar');
    return {
      container: !!c,
      qlEditor: !!(root && root.querySelector('.ql-editor')),
      qlToolbar: !!tb,
      toolbarPadding: tb && getComputedStyle(tb).padding,
      toolbarsOnPage: document.querySelectorAll('.ql-toolbar').length,
    };
  });
  console.log(`  [${process.env.APP_USER}] row5 reply  ` + JSON.stringify(reply));
  check('row5 fragment REPLY editor initialises', reply.qlEditor && reply.qlToolbar, JSON.stringify(reply));
  check('row5 fragment REPLY toolbar is styled (editor.css applied)',
    !!reply.toolbarPadding && reply.toolbarPadding !== '0px', reply.toolbarPadding);
  await shot(page, `row5-comment-reply-${state.canCreateRoot ? 'can-root' : 'cannot-root'}`);

  // ---- row 4: open an edit form inside the fragment (own comment only)
  const editBtn = page.locator('#comment-list button', { hasText: 'Éditer' }).first();
  if (await editBtn.count()) {
    await editBtn.click();
    await page.waitForTimeout(2000);
    const edit = await page.evaluate(() => {
      const c = document.querySelector('[id^="edit-editor-"]');
      const root = c && (c.closest('.rich-content') || c.parentElement);
      return {
        container: !!c,
        qlEditor: !!(root && root.querySelector('.ql-editor')),
        qlToolbar: !!(root && root.querySelector('.ql-toolbar')),
      };
    });
    console.log(`  [${process.env.APP_USER}] row4 edit  ` + JSON.stringify(edit));
    check('row4 fragment EDIT editor initialises', edit.qlEditor && edit.qlToolbar, JSON.stringify(edit));
    await shot(page, `row4-comment-edit-${state.canCreateRoot ? 'can-root' : 'cannot-root'}`);
  } else {
    check('row4 fragment EDIT editor initialises', false, 'no own comment to edit for this user');
  }

  // ---- row 6: two live editors at once, assets still once each
  const live = await page.evaluate(() => document.querySelectorAll('.ql-toolbar').length);
  console.log(`  [${process.env.APP_USER}] row6 live toolbars=${live}`);
  check('row6 two or more editors live at once', live >= 2, `${live} .ql-toolbar`);
  console.log('  row6 network fetches  ' + JSON.stringify(assets.list()));
  await shot(page, `row6-two-editors-${state.canCreateRoot ? 'can-root' : 'cannot-root'}`);
}
