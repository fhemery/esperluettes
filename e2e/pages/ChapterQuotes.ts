import { expect, type Locator, type Page } from '@playwright/test';
import { STORY } from '../support/fixtures';

type Edge = 'start' | 'end';

/**
 * The quote layer of a chapter page: the reader's selection toolbar and
 * mini-form, the reader's own highlights, and the author's heat, gutter
 * markers, badge and summary.
 *
 * Text positions are addressed by a needle that must sit inside a single text
 * node of the chapter (captions included), so a selection can be made with a
 * real mouse drag rather than a synthetic Range.
 */
export class ChapterQuotes {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string = STORY.slug,
    private readonly chapterSlug: string = STORY.illustratedChapter.slug,
  ) {}

  get path(): string {
    return `/stories/${this.storySlug}/chapters/${this.chapterSlug}`;
  }

  /** The quote zone. */
  get article(): Locator {
    return this.page.locator('[data-quote-article]');
  }

  get paragraphs(): Locator {
    return this.article.locator('p');
  }

  get caption(): Locator {
    return this.article.locator('figcaption');
  }

  get image(): Locator {
    return this.article.locator('figure');
  }

  /** The selection toolbar, cloned into <body> on first use. */
  get toolbar(): Locator {
    return this.page.locator('#comment-toolbar-active');
  }

  get citeButton(): Locator {
    return this.toolbar.locator('[data-requires-selection-within]');
  }

  get miniForm(): Locator {
    return this.page.locator('[role="dialog"][aria-labelledby="quote-mini-form-title"]');
  }

  get miniFormQuote(): Locator {
    return this.miniForm.locator('blockquote');
  }

  get miniFormError(): Locator {
    return this.miniForm.locator('p.text-red-600');
  }

  get miniFormSave(): Locator {
    return this.miniForm.getByRole('button', { name: 'Enregistrer' });
  }

  /** The reader's own highlights. */
  get tints(): Locator {
    return this.article.locator('mark.quote-tint');
  }

  /** The author heat. */
  get heat(): Locator {
    return this.article.locator('mark.quote-heat');
  }

  get markers(): Locator {
    return this.page.locator('[data-quote-marker]');
  }

  get badge(): Locator {
    return this.page.locator('[data-quote-author-badge]');
  }

  get heatToggle(): Locator {
    return this.badge.locator('button[aria-pressed]');
  }

  get summaryRows(): Locator {
    return this.page.locator('[data-quote-author-summary] > li');
  }

  async goto(): Promise<void> {
    const response = await this.page.goto(this.path);
    expect(response?.status(), `GET ${this.path}`).toBe(200);
    // The illustration is lazy and unsized: let it settle before measuring.
    await this.page.waitForLoadState('load');
  }

  /** Viewport point on the left edge of the needle's first char, or the right edge of its last. */
  async pointAt(needle: string, edge: Edge, scroll = true): Promise<{ x: number; y: number }> {
    const point = await this.article.evaluate(
      (root, [n, e, s]) => {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) {
          const node = walker.currentNode as Text;
          const at = node.data.indexOf(n);
          if (at < 0) continue;
          const offset = e === 'start' ? at : at + n.length - 1;
          const range = document.createRange();
          range.setStart(node, offset);
          range.setEnd(node, offset + 1);
          if (s) {
            // Near the top, but clear of the sticky top bar.
            (node.parentElement as HTMLElement).scrollIntoView({ block: 'start' });
            window.scrollBy(0, -150);
          }
          const rect = range.getBoundingClientRect();
          return { x: e === 'start' ? rect.left + 1 : rect.right - 1, y: rect.top + rect.height / 2 };
        }
        return null;
      },
      [needle, edge, scroll] as const,
    );
    expect(point, `"${needle}" is not inside one text node of the chapter`).not.toBeNull();
    return point!;
  }

  /** A real mouse drag from the start of `from` to the end of `to`. */
  async dragSelect(from: string, to: string): Promise<void> {
    // Scroll the start to the top once, then measure both ends in place:
    // a drag cannot rely on auto-scroll, so both must be on screen.
    const start = await this.pointAt(from, 'start');
    const end = await this.pointAt(to, 'end', false);
    const height = this.page.viewportSize()?.height ?? 0;
    expect(end.y, `"${to}" is below the viewport once "${from}" is on screen`).toBeLessThan(height);
    await this.page.mouse.move(start.x, start.y);
    await this.page.mouse.down();
    await this.page.mouse.move((start.x + end.x) / 2, (start.y + end.y) / 2, { steps: 5 });
    await this.page.mouse.move(end.x, end.y, { steps: 5 });
    await this.page.mouse.up();
  }

  async tripleClick(needle: string): Promise<void> {
    const at = await this.pointAt(needle, 'start');
    await this.page.mouse.click(at.x, at.y, { clickCount: 3 });
  }

  /**
   * Touch devices: select the needle programmatically, then end a touch on it,
   * which is what the toolbar listens to after a long-press. Playwright cannot
   * drive the native long-press selection itself.
   */
  async touchSelect(from: string, to: string): Promise<void> {
    await this.article.evaluate(
      (root, [a, b]) => {
        const find = (n: string) => {
          const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
          while (walker.nextNode()) {
            const node = walker.currentNode as Text;
            const at = node.data.indexOf(n);
            if (at >= 0) return { node, at };
          }
          throw new Error(`"${n}" not found in one text node`);
        };
        const s = find(a);
        const e = find(b);
        const range = document.createRange();
        range.setStart(s.node, s.at);
        range.setEnd(e.node, e.at + b.length);
        (s.node.parentElement as HTMLElement).scrollIntoView({ block: 'center' });
        const selection = window.getSelection()!;
        selection.removeAllRanges();
        selection.addRange(range);
        s.node.parentElement!.dispatchEvent(new TouchEvent('touchend', { bubbles: true }));
      },
      [from, to] as const,
    );
  }

  async selectionText(): Promise<string> {
    return this.page.evaluate(() => window.getSelection()?.toString() ?? '');
  }

  /** Where the current selection starts and ends, for diagnosing anchoring. */
  async rangeEnds(): Promise<string> {
    return this.page.evaluate(() => {
      const r = window.getSelection()!.getRangeAt(0);
      const describe = (n: Node, o: number) => {
        const el = n.nodeType === 3 ? n.parentElement! : (n as Element);
        const kind = n.nodeType === 3 ? `#text "${(n.textContent ?? '').slice(0, 20)}"` : n.nodeName;
        return `${kind} in <${el.nodeName.toLowerCase()} class="${el.className}">, offset ${o}`;
      };
      return `start: ${describe(r.startContainer, r.startOffset)} | end: ${describe(r.endContainer, r.endOffset)}`;
    });
  }

  /** Bottom of the current selection and top of the toolbar, in page pixels. */
  async selectionAndToolbarBox(): Promise<{ selectionBottom: number; toolbarTop: number }> {
    return this.page.evaluate(() => {
      const rect = window.getSelection()!.getRangeAt(0).getBoundingClientRect();
      const toolbar = document.getElementById('comment-toolbar-active')!;
      return {
        selectionBottom: rect.bottom + window.scrollY,
        toolbarTop: toolbar.getBoundingClientRect().top + window.scrollY,
      };
    });
  }

  /** The text of each highlight, whole marks of one quote joined in DOM order. */
  async tintTexts(): Promise<string[]> {
    return this.tints.evaluateAll(marks => {
      const byId = new Map<string, string>();
      for (const mark of marks as HTMLElement[]) {
        const id = mark.dataset.quoteId ?? '';
        byId.set(id, (byId.get(id) ?? '') + mark.textContent);
      }
      return [...byId.values()];
    });
  }

  /** Heat marks sitting inside anything that is not a text block (e.g. a figure). */
  async heatOutsideTextBlocks(): Promise<number> {
    return this.heat.evaluateAll(marks => marks.filter(m => !m.closest('.ce-block--text')).length);
  }

  async showHeat(): Promise<void> {
    await this.heatToggle.click();
    await expect(this.heatToggle).toHaveAttribute('aria-pressed', 'true');
    await expect(this.heat.first()).toBeVisible();
  }

  /** Hover the badge so its popover — and the summary it lazily loads — opens. */
  async openSummary(): Promise<void> {
    await this.badge.locator('[x-ref="trigger"]').hover();
    await expect(this.summaryRows.first()).toBeVisible();
  }

  /** Paragraph metrics relative to the article: layout parity checks. */
  async paragraphMetrics(): Promise<{ top: number; indent: string; paddingBottom: string }[]> {
    return this.article.evaluate(root => {
      const origin = root.getBoundingClientRect().top;
      return Array.from(root.querySelectorAll('p')).map(p => {
        const style = getComputedStyle(p);
        return {
          top: Math.round(p.getBoundingClientRect().top - origin),
          indent: style.textIndent,
          paddingBottom: style.paddingBottom,
        };
      });
    });
  }

  /** Save a screenshot only when E2E_SHOTS_DIR is set (VERIFY evidence, not a test artefact). */
  async evidence(name: string): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (dir) await this.page.screenshot({ path: `${dir}/${name}.png` });
  }
}
