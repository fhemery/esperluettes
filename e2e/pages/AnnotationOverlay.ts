import { expect, type Locator, type Page } from '@playwright/test';

/** The overlay's grouping band (`gutter.js`). */
const GROUP_PX = 20;

/**
 * Component object for the in-chapter annotation overlay
 * (`<x-comment::annotation-overlay>`): the tint (CSS Custom Highlight API —
 * not in the DOM, read through `CSS.highlights`), the md+ gutter avatars, the
 * popover they open (teleported to `<body>`) and the author's filter menu.
 */
export class AnnotationOverlay {
  constructor(private readonly page: Page) {}

  /**
   * `.env.e2e` sets `APP_URL` to :8081 while the suite serves :8080, so
   * `asset()` URLs (stored avatars) point nowhere. Cosmetic only — rewrite them
   * so avatars load. Call before `goto()`.
   */
  async rewriteAssetHost(): Promise<void> {
    await this.page.context().route('http://localhost:8081/**', (route) =>
      route.continue({ url: route.request().url().replace('localhost:8081', 'localhost:8080') }));
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
   * avatar's rank top-down.
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

  // ---------------------------------------------------------------- popover

  get popover(): Locator {
    return this.page.locator('[data-annotation-popover-panel]');
  }

  async open(passage: string): Promise<void> {
    await (await this.avatarFor(passage)).click();
    await expect(this.popover).toBeVisible();
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

  async openFilter(): Promise<void> {
    if (!(await this.filterPanel.isVisible())) await this.filterButton.click();
    await expect(this.filterPanel).toBeVisible();
  }

  async toggleCommenter(name: string): Promise<void> {
    await this.openFilter();
    await this.filterPanel.locator('li label').filter({ hasText: name }).locator('input').click();
  }
}
