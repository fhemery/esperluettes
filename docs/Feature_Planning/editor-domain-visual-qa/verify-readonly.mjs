/**
 * VERIFY — editor-domain, rows 1-3: read-only surfaces, driven as a GUEST.
 *
 * The point of these rows: after the CSS/JS split, a page that only *renders*
 * stored content must load neither `editor-bundle*.js` nor `editor*.css`, and
 * every stored-content style (align, indent, spoiler, lists, links, emoji,
 * .rich-content typography, ce-block spacing) must still apply.
 */

const EDITOR_ASSET = /editor(-bundle)?-[A-Za-z0-9_]+\.(js|css)$/;

function trackAssets(page) {
  const seen = [];
  const handler = (r) => {
    const u = new URL(r.url()).pathname;
    if (EDITOR_ASSET.test(u)) seen.push(u);
  };
  page.on('response', handler);
  return {
    reset: () => (seen.length = 0),
    list: () => [...seen],
    off: () => page.off('response', handler),
  };
}

export default async function ({ ctx, helpers }) {
  const { goto, shot, check } = helpers;

  // Fresh guest context — no login at all.
  const page = await ctx.newPage();
  const assets = trackAssets(page);

  // ---------------------------------------------------------------- row 1
  assets.reset();
  const s1 = await goto(page, '/stories/le-crepuscule-des-as-1/chapters/qa-editor-domain-24');
  check('row1 chapter 200', s1 === 200, `status ${s1}`);
  check('row1 no editor asset', assets.list().length === 0, assets.list().join(', ') || 'none');

  const geom = await page.evaluate(() => {
    const find = (marker) =>
      Array.from(document.querySelectorAll('p, h2, h3, blockquote, li, div')).find((e) =>
        e.textContent.trim().startsWith(marker) && e.children.length < 5);
    const cs = (el) => (el ? getComputedStyle(el) : null);
    const c = find('QA-CENTER'), r = find('QA-RIGHT'), j = find('QA-JUSTIFY'),
      p = find('QA-PLAIN'), ind = find('QA-INDENT');
    const sp = document.querySelector('.ql-spoiler');
    const spCs = sp ? getComputedStyle(sp) : null;
    const emoji = document.querySelector('.ql-custom-emoji');
    const emCs = emoji ? getComputedStyle(emoji) : null;
    const link = Array.from(document.querySelectorAll('.rich-content a')).find((a) => a.textContent.includes('lien externe'));
    return {
      center: cs(c)?.textAlign, right: cs(r)?.textAlign, justify: cs(j)?.textAlign,
      plain: cs(p)?.textAlign,
      indentText: cs(ind)?.textIndent,
      indentParaText: (() => { const el = find('QA-INDENT'); return el ? getComputedStyle(el).textIndent : null; })(),
      spoiler: spCs && { color: spCs.color, bg: spCs.backgroundColor, fill: spCs.webkitTextFillColor },
      spoilerCount: document.querySelectorAll('.ql-spoiler').length,
      emoji: emCs && { display: emCs.display, w: emCs.width, bgImg: emCs.backgroundImage !== 'none' },
      linkDeco: link ? getComputedStyle(link).textDecorationLine : null,
      ulStyle: (() => { const li = find('QA-UL'); return li ? getComputedStyle(li.parentElement).listStyleType : null; })(),
      olStyle: (() => { const li = find('QA-OL'); return li ? getComputedStyle(li.parentElement).listStyleType : null; })(),
      richContentLineHeight: (() => { const rc = document.querySelector('.rich-content'); return rc ? getComputedStyle(rc).lineHeight : null; })(),
      h2Color: (() => { const h = find('QA-H2'); return h ? getComputedStyle(h).color : null; })(),
    };
  });
  console.log('  row1 geom  ' + JSON.stringify(geom, null, 1));
  check('row1 center aligned', geom.center === 'center', geom.center);
  check('row1 right aligned', geom.right === 'right', geom.right);
  check('row1 justified', geom.justify === 'justify', geom.justify);
  check('row1 indent 2rem', geom.indentParaText && geom.indentParaText !== '0px', geom.indentParaText);
  check('row1 spoiler hidden (text colour == bg colour)',
    !!geom.spoiler && geom.spoiler.color === geom.spoiler.bg, JSON.stringify(geom.spoiler));
  check('row1 emoji styled', !!geom.emoji && geom.emoji.display === 'inline-block' && geom.emoji.bgImg,
    JSON.stringify(geom.emoji));
  check('row1 link underlined', geom.linkDeco === 'underline', geom.linkDeco);
  await shot(page, 'row1-chapter-readonly');

  // spoiler reveals on click
  await page.locator('.ql-spoiler').first().click();
  await page.waitForTimeout(300);
  const revealed = await page.evaluate(() => {
    const sp = document.querySelector('.ql-spoiler');
    return { cls: sp.className, color: getComputedStyle(sp).color, bg: getComputedStyle(sp).backgroundColor };
  });
  check('row1 spoiler reveals on click',
    revealed.cls.includes('revealed') && revealed.color !== revealed.bg, JSON.stringify(revealed));
  check('row1 still no editor asset after click', assets.list().length === 0, assets.list().join(', '));
  await shot(page, 'row1-chapter-spoiler-revealed');

  // ---------------------------------------------------------------- row 2
  assets.reset();
  const s2 = await goto(page, '/news/deuxieme-nouveaute');
  check('row2 news 200', s2 === 200, `status ${s2}`);
  check('row2 no editor asset', assets.list().length === 0, assets.list().join(', ') || 'none');
  const news = await page.evaluate(() => {
    const blocks = Array.from(document.querySelectorAll('.ce-block'));
    return {
      count: blocks.length,
      kinds: blocks.map((b) => b.className.replace(/\s+/g, ' ').trim()),
      margins: blocks.map((b) => { const c = getComputedStyle(b); return `${c.marginTop}/${c.marginBottom}`; }),
      imgs: Array.from(document.querySelectorAll('.ce-block--image img, .ce-block--image picture img')).map((i) => ({
        w: i.getBoundingClientRect().width, maxW: getComputedStyle(i).maxWidth, srcset: !!i.srcset,
      })),
      figcaptions: Array.from(document.querySelectorAll('.ce-block--image figcaption')).map((f) => getComputedStyle(f).fontSize),
    };
  });
  console.log('  row2 blocks  ' + JSON.stringify(news, null, 1));
  check('row2 has ce-block--text and ce-block--image',
    news.kinds.some((k) => k.includes('ce-block--text')) && news.kinds.some((k) => k.includes('ce-block--image')),
    news.kinds.join(' | '));
  check('row2 images responsive', news.imgs.length > 0 && news.imgs.every((i) => i.w > 0), JSON.stringify(news.imgs));
  await shot(page, 'row2-news-blocks');

  // ---------------------------------------------------------------- row 3
  assets.reset();
  const s3 = await goto(page, '/qui-sommes-nous');
  check('row3 static page 200', s3 === 200, `status ${s3}`);
  check('row3 static: no editor asset', assets.list().length === 0, assets.list().join(', ') || 'none');
  const sp = await page.evaluate(() => {
    const rc = document.querySelector('.static-content, .rich-content');
    if (!rc) return null;
    const c = getComputedStyle(rc);
    const h2 = rc.querySelector('h2'), a = rc.querySelector('a'), li = rc.querySelector('li');
    return {
      lineHeight: c.lineHeight, fontSize: c.fontSize,
      h2: h2 && getComputedStyle(h2).color,
      link: a && getComputedStyle(a).textDecorationLine,
      list: li && getComputedStyle(li.parentElement).listStyleType,
      html: rc.innerHTML.length,
    };
  });
  console.log('  row3 static  ' + JSON.stringify(sp));
  check('row3 static .rich-content present and non-empty', !!sp && sp.html > 50, JSON.stringify(sp));
  await shot(page, 'row3-static-page');

  // Story description is the guest-visible `.rich-content` surface; the profile
  // "À propos" tab is AuthenticatedOnly, so it is covered in the logged-in flow.
  assets.reset();
  const s3b = await goto(page, '/stories/le-crepuscule-des-as-1');
  check('row3 story 200', s3b === 200, `status ${s3b}`);
  check('row3 story: no editor asset', assets.list().length === 0, assets.list().join(', ') || 'none');
  // The story description wrapper is `.prose`, not `.rich-content` (unchanged by
  // this refactor); `.rich-content` read-only typography is covered by row 1.
  const st = await page.evaluate(() => {
    const rc = document.querySelector('.prose');
    if (!rc) return null;
    const c = getComputedStyle(rc);
    return { lineHeight: c.lineHeight, textIndent: c.textIndent, len: rc.innerHTML.length };
  });
  console.log('  row3 story description  ' + JSON.stringify(st));
  check('row3 story description renders', !!st && st.len > 50, JSON.stringify(st));
  await shot(page, 'row3-story-rich-content');

  // The profile "À propos" tab is AuthenticatedOnly, so it is exercised in
  // verify-authenticated.mjs; a guest is redirected to /login here.
  assets.reset();
  const s4 = await goto(page, '/profile/qa-admin/about');
  check('row3 profile (guest redirected, no editor asset)',
    assets.list().length === 0, `status ${s4}, assets: ${assets.list().join(', ') || 'none'}`);
  const pr = await page.evaluate(() => {
    const find = (m) => Array.from(document.querySelectorAll('p,h2,li'))
      .find((e) => e.textContent.trim().startsWith(m));
    const rc = document.querySelector('.rich-content');
    const em = document.querySelector('.ql-custom-emoji');
    const link = Array.from(document.querySelectorAll('a')).find((a) => a.textContent.trim() === 'un lien');
    const c = find('QA-PROF-CENTER'), li = find('QA-PROF-UL'), h2 = find('QA-PROF-H2');
    return {
      richContent: !!rc,
      lineHeight: rc && getComputedStyle(rc).lineHeight,
      center: c && getComputedStyle(c).textAlign,
      list: li && getComputedStyle(li.parentElement).listStyleType,
      h2Color: h2 && getComputedStyle(h2).color,
      linkDeco: link && getComputedStyle(link).textDecorationLine,
      emoji: em && {
        display: getComputedStyle(em).display,
        w: getComputedStyle(em).width,
        bgImg: getComputedStyle(em).backgroundImage !== 'none',
      },
    };
  });
  console.log('  row3 profile (guest)  ' + JSON.stringify(pr));

  // For the record: what stylesheets/scripts these pages DO load.
  const loaded = await page.evaluate(() => ({
    css: Array.from(document.querySelectorAll('link[rel=stylesheet]')).map((l) => new URL(l.href).pathname),
    js: Array.from(document.querySelectorAll('script[src]')).map((s) => new URL(s.src).pathname),
  }));
  console.log('  row3 assets on page  ' + JSON.stringify(loaded, null, 1));

  assets.off();
}
