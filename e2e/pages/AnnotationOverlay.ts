import { expect, type Locator, type Page, type Request } from '@playwright/test';
import path from 'node:path';
import { RichTextEditor } from './RichTextEditor';

const POSITION_TOLERANCE_PX = 4;

/** The overlay's grouping band (`gutter.js`). */
const GROUP_PX = 20;

/**
 * Component object for the in-chapter annotation overlay
 * (`<x-comment::annotation-overlay>`): the tint (CSS Custom Highlight API —
 * not in the DOM, read through `CSS.highlights`), the md+ gutter avatars, the
 * popover they open (teleported to `<body>`) and the author's filter menu.
 */
export class AnnotationOverlay {
  private readonly inEntity: Request[] = [];

  constructor(private readonly page: Page) {
    page.on('request', (request) => {
      if (request.url().includes('/comments/annotations/in-entity')) this.inEntity.push(request);
    });
  }

  /**
   * `.env.e2e` sets `APP_URL` to :8081 while the suite serves :8080, so
   * `asset()` URLs (stored avatars) point nowhere. Cosmetic only — rewrite them
   * so evidence screenshots show real avatars. Call before `goto()`.
   */
  async rewriteAssetHost(): Promise<void> {
    await this.page.context().route('http://localhost:8081/**', (route) =>
      route.continue({ url: route.request().url().replace('localhost:8081', 'localhost:8080') }));
  }

  /** How many `GET in-entity` the page has sent since this object was built. */
  get inEntityRequests(): number {
    return this.inEntity.length;
  }

  get root(): Locator {
    return this.page.locator('[data-annotation-overlay]');
  }

  /** Text of every range in the named highlight, in no particular order. */
  async highlighted(name: 'annotation' | 'annotation-processed' = 'annotation'): Promise<string[]> {
    return this.page.evaluate((key) => {
      const highlight = CSS.highlights.get(key);
      return highlight ? [...highlight].map((range) => range.toString()) : [];
    }, name);
  }

  /** Waits until the named highlight holds exactly `texts` (order ignored). */
  async expectHighlighted(texts: readonly string[], name: 'annotation' | 'annotation-processed' = 'annotation'): Promise<void> {
    await expect.poll(async () => (await this.highlighted(name)).sort(), { message: `highlight ${name}` })
      .toEqual([...texts].sort());
  }

  /** Every gutter avatar button. Hidden ones (below md, heat on) included. */
  get avatars(): Locator {
    return this.page.locator('[data-annotation-group]');
  }


  /**
   * The gutter avatar of `passage`'s group. The overlay does not tie a button
   * to a text in the DOM, so this goes by rank: the passage's line among the
   * tinted lines (bands of `GROUP_PX`, as the overlay groups them) is the
   * avatar's rank top-down. Rank, not distance, so that an avatar misplaced
   * by a constant offset is still found — alignment has its own assertion.
   */
  async avatarFor(passage: string): Promise<Locator> {
    const index = await this.page.evaluate(([text, band]) => {
      const ranges = [...(CSS.highlights.get('annotation') ?? []), ...(CSS.highlights.get('annotation-processed') ?? [])] as Range[];
      const target = ranges.find((r) => r.toString() === text);
      if (!target) return -1;
      const topOf = (r: Range) => r.getClientRects()[0]?.top ?? NaN;
      const tops = ranges.map(topOf).filter((t) => !Number.isNaN(t)).sort((a, b) => a - b);
      const lines: number[] = [];
      for (const top of tops) if (lines.length === 0 || top - lines[lines.length - 1]! > Number(band)) lines.push(top);
      const rank = lines.filter((line) => line <= topOf(target) + 0.5).length - 1;
      const buttons = [...document.querySelectorAll('[data-annotation-group]')];
      const byTop = buttons.map((b, i) => [b.getBoundingClientRect().top, i] as const).sort((a, b) => a[0] - b[0]);
      return byTop[rank]?.[1] ?? -1;
    }, [passage, GROUP_PX] as const);
    expect(index, `no gutter avatar for « ${passage} »`).toBeGreaterThanOrEqual(0);
    return this.avatars.nth(index);
  }

  /**
   * For each placed passage, the vertical gap to the nearest gutter avatar.
   * Every gap ≤ tolerance means each passage has its avatar beside it.
   */
  async avatarGaps(): Promise<Record<string, number>> {
    return this.page.evaluate(() => {
      const tops = [...document.querySelectorAll('[data-annotation-group]')]
        .map((b) => b.getBoundingClientRect().top);
      const gaps: Record<string, number> = {};
      for (const range of CSS.highlights.get('annotation') ?? []) {
        const top = (range as Range).getClientRects()[0]?.top ?? NaN;
        gaps[range.toString()] = Math.round(Math.min(...tops.map((t) => Math.abs(t - top))));
      }
      return gaps;
    });
  }

  /** Every tinted passage has an avatar level with its first line; the failure lists the gaps in px. */
  async expectAvatarsAligned(): Promise<void> {
    try {
      await expect.poll(async () => Object.values(await this.avatarGaps()).every((gap) => gap <= POSITION_TOLERANCE_PX), {
        timeout: 3_000,
      }).toBe(true);
    } catch {
      throw new Error(`avatars not level with their passages (px to the nearest avatar): ${JSON.stringify(await this.avatarGaps())}`);
    }
  }

  /** Viewport top of a tinted passage's first line. */
  async passageTop(passage: string): Promise<number> {
    const top = await this.page.evaluate((text) => {
      const range = [...(CSS.highlights.get('annotation') ?? [])].find((r) => r.toString() === text) as Range | undefined;
      return range?.getClientRects()[0]?.top ?? null;
    }, passage);
    expect(top, `« ${passage} » is not tinted`).not.toBeNull();
    return top!;
  }

  /** The underline colour a highlight gives the chapter's paragraphs. */
  async underlineColour(name: 'annotation' | 'annotation-processed'): Promise<string> {
    return this.page.locator('[data-quote-article] p').first().evaluate(
      (p, key) => getComputedStyle(p, `::highlight(${key})`).textDecorationColor, name);
  }

  /** Clicks (taps) the middle of a tinted passage's first line. */
  async clickPassage(passage: string): Promise<void> {
    const point = await this.page.evaluate((text) => {
      const range = [...(CSS.highlights.get('annotation') ?? [])].find((r) => r.toString() === text);
      if (!range) return null;
      range.startContainer.parentElement?.scrollIntoView({ block: 'center' });
      const rect = (range as Range).getClientRects()[0];
      return rect ? { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 } : null;
    }, passage);
    expect(point, `« ${passage} » is not tinted`).not.toBeNull();
    await this.page.mouse.click(point!.x, point!.y);
  }

  /** The « +N » badge of an avatar. */
  more(avatar: Locator): Locator {
    return avatar.locator('span');
  }

  avatarImage(avatar: Locator): Locator {
    return avatar.locator('img');
  }

  // ---------------------------------------------------------------- popover

  get popover(): Locator {
    return this.page.locator('[data-annotation-popover-panel]');
  }

  async open(passage: string): Promise<void> {
    await (await this.avatarFor(passage)).click();
    await expect(this.popover).toBeVisible();
  }

  get popoverPosition(): Locator {
    return this.popover.locator('[aria-live="polite"]');
  }

  get popoverAuthor(): Locator {
    return this.popover.locator('span.font-medium').first();
  }

  get popoverBody(): Locator {
    return this.popover.locator('.comment-body').first();
  }

  get popoverReplies(): Locator {
    return this.popover.getByTestId('annotation-popover-reply');
  }

  get previousButton(): Locator {
    return this.popover.getByRole('button', { name: 'Annotation précédente', exact: true });
  }

  get nextButton(): Locator {
    return this.popover.getByRole('button', { name: 'Annotation suivante', exact: true });
  }

  get closeButton(): Locator {
    return this.popover.getByRole('button', { name: 'Fermer', exact: true });
  }

  popoverButton(name: 'Modifier' | 'Supprimer' | 'Répondre' | 'Marquer comme traitée' | 'Marquer comme non traitée'): Locator {
    return this.popover.getByRole('button', { name, exact: true });
  }

  /** The popover's own reply editor (not the « N annotations » pop-up's). */
  get replyEditor(): RichTextEditor {
    return new RichTextEditor(this.page, 'annotation-overlay-reply-editor');
  }

  get sendReply(): Locator {
    return this.popover.getByRole('button', { name: 'Envoyer', exact: true });
  }

  get popoverError(): Locator {
    return this.popover.getByRole('alert').filter({ visible: true });
  }

  // ---------------------------------------------------------------- filter menu

  get filterMenu(): Locator {
    return this.page.locator('[x-data="annotationFilterMenu"]');
  }

  get filterButton(): Locator {
    return this.page.getByRole('button', { name: 'Filtrer les annotations' });
  }

  get filterPanel(): Locator {
    return this.page.locator('#annotation-filter-panel');
  }

  get filterCommenterNames(): Locator {
    return this.filterPanel.locator('li span');
  }

  async openFilter(): Promise<void> {
    if (!(await this.filterPanel.isVisible())) await this.filterButton.click();
    await expect(this.filterPanel).toBeVisible();
  }

  async toggleCommenter(name: string): Promise<void> {
    await this.openFilter();
    await this.filterPanel.locator('li label').filter({ hasText: name }).locator('input').click();
  }

  async toggleShowProcessed(): Promise<void> {
    await this.openFilter();
    await this.filterPanel.getByLabel('Afficher les annotations traitées').click();
  }

  // ---------------------------------------------------------------- evidence

  /**
   * Evidence screenshot, only when `E2E_SHOTS_DIR` is set (VERIFY), so later
   * suite runs take none.
   */
  async shot(name: string): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (!dir) return;
    await this.page.screenshot({ path: path.join(dir, `${name}.png`) });
  }
}
