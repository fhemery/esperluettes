/**
 * VERIFY — editor-domain: the *behavioural* halves of the checklist rows —
 * typing, counters blocking submit, the spoiler button inserting a spoiler,
 * multi-block add/delete + Media picker, and a real save that renders back.
 */

const type = async (page, editorId, text) => {
  const el = page.locator(`#${editorId} .ql-editor`);
  await el.click();
  await el.type(text, { delay: 4 });
  await page.waitForTimeout(400);
};

export default async function ({ page, helpers, args }) {
  const { login, goto, shot, check } = helpers;
  const only = args.find((a) => a.startsWith('--only='))?.split('=')[1];
  const run = (n) => !only || n.startsWith(only);
  await login(page);

  // -------------------------------------------------- row 10: min-char counter
  if (run('row10')) {
    // daniel has not posted a root comment on chapter 1, so the form renders.
    await goto(page, '/stories/le-crepuscule-des-as-1/chapters/chapitre-1');
    await page.waitForTimeout(1500);
    const has = await page.locator('#comment-body-editor').count();
    if (has === 0) {
      check('row10 new-comment form present', false,
        'canCreateRoot is false for this user on this chapter (author or already posted)');
    } else {
      const before = await page.evaluate(() => ({
        counter: document.getElementById('quill-counter-wrap-comment-body-editor').textContent.replace(/\s+/g, ' ').trim(),
        submitDisabled: document.querySelector('form[data-comment-draft="root"] button[type=submit]').disabled,
      }));
      await type(page, 'comment-body-editor', 'Trop court.');
      const short = await page.evaluate(() => ({
        counter: document.getElementById('quill-counter-wrap-comment-body-editor').textContent.replace(/\s+/g, ' ').trim(),
        submitDisabled: document.querySelector('form[data-comment-draft="root"] button[type=submit]').disabled,
      }));
      await type(page, 'comment-body-editor', ' '.padEnd(3) + 'x'.repeat(160));
      await page.waitForTimeout(500);
      const long = await page.evaluate(() => ({
        counter: document.getElementById('quill-counter-wrap-comment-body-editor').textContent.replace(/\s+/g, ' ').trim(),
        submitDisabled: document.querySelector('form[data-comment-draft="root"] button[type=submit]').disabled,
      }));
      console.log('  row10  ' + JSON.stringify({ before, short, long }));
      check('row10 min-characters hint uses the French plural', /caractères minimum/.test(before.counter), before.counter);
      check('row10 submit blocked under the minimum', short.submitDisabled === true, JSON.stringify(short));
      check('row10 submit allowed over the minimum', long.submitDisabled === false, JSON.stringify(long));
      await shot(page, 'row10-comment-min-counter');
    }
  }

  // -------------------------------------------------- row 17: story 100/1000
  if (run('row17')) {
    await goto(page, '/stories/create');
    await page.waitForTimeout(1800);
    await type(page, 'story-description-editor', 'Trop court pour valider.');
    const short = await page.evaluate(() => ({
      counter: document.getElementById('quill-counter-wrap-story-description-editor').textContent.replace(/\s+/g, ' ').trim(),
      submit: Array.from(document.querySelectorAll('button[type=submit]')).map((b) => b.disabled),
    }));
    await type(page, 'story-description-editor', ' ' + 'a'.repeat(120));
    const long = await page.evaluate(() => ({
      counter: document.getElementById('quill-counter-wrap-story-description-editor').textContent.replace(/\s+/g, ' ').trim(),
      submit: Array.from(document.querySelectorAll('button[type=submit]')).map((b) => b.disabled),
    }));
    console.log('  row17  ' + JSON.stringify({ short, long }));
    check('row17 counter counts typed characters', /^\d+ \/ 1000/.test(long.counter) && !/^0 /.test(long.counter), long.counter);
    check('row17 min hint says 100', /100 caractères minimum/.test(short.counter), short.counter);
    await shot(page, 'row17-story-create-counter');
    // The story form has no client-side disable (unchanged from main — the blade
    // is byte-identical); the 100-char minimum is enforced server-side.
    await goto(page, '/stories/create');
    await page.waitForTimeout(1800);
    await page.fill('input[name="title"]', 'QA Titre trop court');
    await type(page, 'story-description-editor', 'Beaucoup trop court.');
    await page.locator('button[type=submit]').first().click();
    await page.waitForTimeout(2000);
    const rejected = await page.evaluate(() => ({
      url: location.pathname,
      errors: Array.from(document.querySelectorAll('.text-red-600, [role=alert], .text-error'))
        .map((e) => e.textContent.trim()).filter(Boolean).slice(0, 5),
    }));
    console.log('  row17 short submit  ' + JSON.stringify(rejected));
    check('row17 submit blocked under 100 (server-side rejection)',
      rejected.url.includes('/stories') && rejected.errors.length > 0, JSON.stringify(rejected));
    await shot(page, 'row17-story-create-rejected');
  }

  // -------------------------------------------------- row 18: spoiler inserts
  if (run('row18')) {
    await goto(page, '/stories/le-crepuscule-des-as-1/chapters/chapitre-1/edit');
    await page.waitForTimeout(2000);
    const el = page.locator('#chapter-author-note-editor .ql-editor');
    await el.click();
    await page.keyboard.type('SPOILER-TEST');
    await page.keyboard.down('Shift');
    for (let i = 0; i < 12; i++) await page.keyboard.press('ArrowLeft');
    await page.keyboard.up('Shift');
    await page.locator('#chapter-author-note-editor').locator('xpath=../..').locator('button.ql-spoiler').first().click();
    await page.waitForTimeout(500);
    const res = await page.evaluate(() => {
      const ed = document.querySelector('#chapter-author-note-editor .ql-editor');
      const sp = ed.querySelector('.ql-spoiler');
      return {
        html: ed.innerHTML.slice(0, 200),
        hasSpoiler: !!sp,
        spoilerBg: sp && getComputedStyle(sp).backgroundColor,
        btnActive: !!document.querySelector('button.ql-spoiler.ql-active'),
      };
    });
    console.log('  row18 spoiler  ' + JSON.stringify(res));
    check('row18 spoiler button inserts a .ql-spoiler', res.hasSpoiler, res.html);
    check('row18 inserted spoiler uses the editing-side grey (editor.css)',
      res.spoilerBg === 'rgba(128, 128, 128, 0.25)', res.spoilerBg);
    await shot(page, 'row18-spoiler-inserted');
  }

  // -------------------------------------------------- row 14: multi blocks
  if (run('row14')) {
    await goto(page, '/admin/news/4/edit');
    await page.waitForTimeout(2500);
    const before = await page.evaluate(() => ({
      textBlocks: document.querySelectorAll('[id^="blocks__"]').length,
      total: document.querySelectorAll('[data-block-index], [x-data*="block"]').length,
      insertBtns: document.querySelectorAll('[data-test-id^="insert-"], button[title*="exte"], button[title*="mage"]').length,
    }));
    // The insert affordance is a "+" button opening a popover.
    await page.locator('button[aria-expanded]:has(span.material-symbols-outlined:text-is("add"))').last()
      .scrollIntoViewIfNeeded();
    await page.locator('button[aria-expanded]:has(span.material-symbols-outlined:text-is("add"))').last().click();
    await page.waitForTimeout(600);
    const addText = page.locator('button:visible', { hasText: 'Ajouter du texte' }).last();
    const addImage = page.locator('button:visible', { hasText: 'Ajouter une image' }).last();
    let added = null;
    if (await addText.count()) {
      await addText.click();
      await page.waitForTimeout(1200);
      added = await page.evaluate(() => ({
        textBlocks: document.querySelectorAll('[id^="blocks__"]').length,
        allLive: Array.from(document.querySelectorAll('[id^="blocks__"]')).every((h) => {
          const w = h.closest('.rich-content');
          return w && w.querySelector('.ql-editor') && w.querySelector('.ql-toolbar');
        }),
      }));
    }
    console.log('  row14  ' + JSON.stringify({ before, added }));
    check('row14 a text block can be added', !!added && added.textBlocks > before.textBlocks,
      JSON.stringify({ before: before.textBlocks, after: added && added.textBlocks }));
    check('row14 every text block (incl. the new one) has a live editor', !!added && added.allLive,
      JSON.stringify(added));
    await shot(page, 'row14-news-multi-add-block');

    // Media picker — reopen the insert popover on the *first* block this time.
    const plus2 = page.locator('button[aria-expanded]:visible:has(span.material-symbols-outlined:text-is("add"))').first();
    await plus2.scrollIntoViewIfNeeded();
    await plus2.click({ force: true });
    await page.waitForTimeout(800);
    if (await addImage.count()) {
      await addImage.click();
      await page.waitForTimeout(1500);
      const imageBlocks = await page.evaluate(() =>
        document.querySelectorAll('[data-block][data-type="image"]').length);
      check('row14 an image block can be added', imageBlocks > 2, `${imageBlocks} image block(s)`);
      await shot(page, 'row14-news-image-block-added');

      // <x-media::image-field> exposes a "reuse" affordance that opens the picker.
      const reuse = page.locator('button:visible', { hasText: /Réutiliser|Choisir|Bibliothèque|Parcourir/i }).last();
      if (await reuse.count()) {
        await reuse.scrollIntoViewIfNeeded();
        await reuse.click();
        await page.waitForTimeout(2000);
      }
      const picker = await page.evaluate(() => {
        const dlgs = Array.from(document.querySelectorAll('[role=dialog], .fixed.inset-0'))
          .filter((d) => d.getBoundingClientRect().height > 100 && getComputedStyle(d).display !== 'none');
        return { open: dlgs.length > 0, text: dlgs[0] ? dlgs[0].textContent.replace(/\s+/g, ' ').trim().slice(0, 140) : null };
      });
      console.log('  row14 media picker  ' + JSON.stringify(picker));
      check('row14 Media picker opens', picker.open, JSON.stringify(picker));
      await shot(page, 'row14-news-media-picker');
    }

    // close the picker before touching the page again
    await page.keyboard.press('Escape');
    await page.waitForTimeout(800);

    // delete the block we just added, to prove removal still works
    const del = page.locator('[data-block] button:visible:has(span.material-symbols-outlined:text-is("delete"))').last();
    if (await del.count()) {
      const before2 = await page.evaluate(() => document.querySelectorAll('[data-block]').length);
      await del.scrollIntoViewIfNeeded();
      await del.click();
      await page.waitForTimeout(800);
      const after2 = await page.evaluate(() => document.querySelectorAll('[data-block]').length);
      check('row14 a block can be deleted', after2 === before2 - 1, `${before2} -> ${after2}`);
    }
  }

  // -------------------------------------------------- row 13: save + render
  if (run('row13')) {
    await goto(page, '/admin/news/3/edit');
    await page.waitForTimeout(2200);
    const marker = 'QA-SAVE-' + Date.now();
    const id = await page.evaluate(() => document.querySelector('[data-toolbar]').id);
    await type(page, id, ' ' + marker);
    await page.locator('button[type=submit]', { hasText: /Enregistrer|Mettre à jour|Sauvegarder/i }).first().click();
    await page.waitForTimeout(2500);
    const afterUrl = page.url();
    await goto(page, '/news/nouveautes');
    const rendered = await page.evaluate((m) => document.body.textContent.includes(m), marker);
    console.log(`  row13 saved to ${afterUrl}; marker rendered = ${rendered}`);
    check('row13 news content saves and renders on the public page', rendered, marker);
    await shot(page, 'row13-news-saved-render');
  }
}
