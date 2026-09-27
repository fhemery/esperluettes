import type { Locator, Page } from '@playwright/test';

/**
 * `/admin/statistics` — number tiles, the Cumulé / Par semaine switch, and the
 * Chart.js graphs of the three tabs.
 *
 * Chart.js is bundled into the page's own module and never exposed, and it
 * draws on a canvas, so neither the chart type nor the tooltip is in the DOM.
 * `recordCharts()` installs a recorder on the 2D context before any page
 * script runs; it keeps, per canvas, what the last frame drew:
 *
 * - `bars`: rectangles filled inside the chart-area clip (Chart.js draws bars
 *   with `rect()` + `fill()`, clipped to the chart area; legend boxes are
 *   clipped to the much shorter legend strip, so they are left out),
 * - `texts`: every `fillText` (axis ticks, legend, tooltip),
 * - `kinds`: the kind of every frame ever drawn ('bar' | 'line'), to catch a
 *   cumulative frame before the bars on reload.
 */

export type Bar = { x: number; y: number; w: number; h: number; fill: string };
export type Drawing = { bars: Bar[]; texts: string[]; kinds: ('bar' | 'line')[] };

export type GraphMode = 'cumulative' | 'weekly';
export type StatisticsTab = 'users' | 'content' | 'comments';

const MODE_LABELS: Record<GraphMode, string> = { cumulative: 'Cumulé', weekly: 'Par semaine' };

/** Runs in the page, before any script. Must be self-contained. */
function installCanvasRecorder(): void {
  type Rect = { x: number; y: number; w: number; h: number };
  type Frame = { bars: Bar[]; texts: string[]; hasContent: boolean };
  type Log = { frame: Frame; kinds: ('bar' | 'line')[]; pending: Rect[]; clip: Rect | null; clipStack: (Rect | null)[] };
  type Bar = Rect & { fill: string };

  const MIN_CHART_AREA_HEIGHT = 80;
  const logs = new WeakMap<HTMLCanvasElement, Log>();
  const newFrame = (): Frame => ({ bars: [], texts: [], hasContent: false });
  const logOf = (ctx: CanvasRenderingContext2D): Log => {
    let log = logs.get(ctx.canvas);
    if (!log) {
      log = { frame: newFrame(), kinds: [], pending: [], clip: null, clipStack: [] };
      logs.set(ctx.canvas, log);
    }
    return log;
  };
  const closeFrame = (log: Log): void => {
    if (log.frame.hasContent) {
      log.kinds.push(log.frame.bars.length > 0 ? 'bar' : 'line');
    }
  };

  const proto = CanvasRenderingContext2D.prototype;
  const wrap = <K extends keyof CanvasRenderingContext2D>(name: K, before: (log: Log, args: any[], ctx: CanvasRenderingContext2D) => void) => {
    const original = proto[name] as unknown as (...args: any[]) => any;
    (proto as any)[name] = function (this: CanvasRenderingContext2D, ...args: any[]) {
      before(logOf(this), args, this);
      return original.apply(this, args);
    };
  };

  wrap('clearRect', (log, [x, y, w, h], ctx) => {
    if (x === 0 && y === 0 && w >= ctx.canvas.width && h >= ctx.canvas.height) {
      closeFrame(log);
      log.frame = newFrame();
    }
  });
  wrap('beginPath', (log) => {
    log.pending = [];
  });
  wrap('rect', (log, [x, y, w, h]) => {
    log.pending.push({ x, y, w, h });
  });
  wrap('save', (log) => {
    log.clipStack.push(log.clip);
  });
  wrap('restore', (log) => {
    log.clip = log.clipStack.pop() ?? null;
  });
  wrap('clip', (log) => {
    log.clip = log.pending[0] ?? log.clip;
  });
  wrap('fill', (log, _args, ctx) => {
    log.frame.hasContent = true;
    // The legend clips to its own strip too; only the chart area is taller.
    if (log.clip && log.clip.h > MIN_CHART_AREA_HEIGHT && log.pending.length > 0) {
      for (const r of log.pending) {
        log.frame.bars.push({ ...r, fill: String(ctx.fillStyle) });
      }
    }
  });
  wrap('stroke', (log) => {
    log.frame.hasContent = true;
  });
  wrap('fillText', (log, [text]) => {
    log.frame.texts.push(String(text));
  });

  (window as any).__e2eCanvas = (canvas: HTMLCanvasElement): Drawing | null => {
    const log = logs.get(canvas);
    if (!log) return null;
    return {
      bars: log.frame.bars.map((b) => ({ ...b })),
      texts: [...log.frame.texts],
      kinds: [...log.kinds, ...(log.frame.hasContent ? [log.frame.bars.length > 0 ? 'bar' : 'line'] : [])] as ('bar' | 'line')[],
    };
  };
}

export class AdminStatisticsPage {
  constructor(private readonly page: Page) {}

  /** Call before `goto()`: the recorder must be in place before Chart.js draws. */
  async recordCharts(): Promise<void> {
    await this.page.addInitScript(installCanvasRecorder);
  }

  async goto(): Promise<void> {
    await this.page.goto('/admin/statistics');
  }

  get modeSwitch(): Locator {
    return this.page.locator('[data-segmented-control][data-name="statistics-graph-mode"]');
  }

  modeOption(mode: GraphMode): Locator {
    return this.modeSwitch.getByRole('radio', { name: MODE_LABELS[mode] });
  }

  async selectMode(mode: GraphMode): Promise<void> {
    await this.modeOption(mode).click();
  }

  get tabList(): Locator {
    return this.page.getByRole('tablist');
  }

  async openTab(tab: StatisticsTab): Promise<void> {
    await this.page.locator(`#tabs-tab-${tab}`).click();
  }

  panel(tab: StatisticsTab): Locator {
    return this.page.locator(`#tabs-panel-${tab}`);
  }

  /** Chart canvases of a tab, in page order. */
  charts(tab: StatisticsTab): Locator {
    return this.panel(tab).locator('[data-statistics-line-chart] canvas, [data-statistics-multi-line-chart] canvas');
  }

  /** Every chart canvas of the page, all tabs. */
  get allCharts(): Locator {
    return this.page.locator('[data-statistics-line-chart] canvas, [data-statistics-multi-line-chart] canvas');
  }

  get emptyCharts(): Locator {
    return this.page.locator('.stat-line-chart-empty');
  }

  /** The number tiles above the switch, as displayed text. */
  async tilesText(): Promise<string[]> {
    return this.page.locator('.stat-summary, .comment-summary').allInnerTexts();
  }

  /** What the canvas drew in its last frame (see the class comment). */
  async drawing(canvas: Locator): Promise<Drawing> {
    const drawing = await canvas.evaluate((el) => (window as any).__e2eCanvas?.(el) ?? null);
    if (!drawing) {
      throw new Error('Canvas recorder not installed or nothing drawn — call recordCharts() before goto()');
    }
    return drawing;
  }

  async canvasSize(canvas: Locator): Promise<{ width: number; height: number }> {
    return canvas.evaluate((el: HTMLCanvasElement) => ({ width: el.clientWidth, height: el.clientHeight }));
  }

  /** Hover the middle of the i-th of `slots` equal category slots of a bar chart. */
  async hoverSlot(canvas: Locator, index: number, slots: number): Promise<void> {
    await canvas.scrollIntoViewIfNeeded();
    const drawing = await this.drawing(canvas);
    const xs = [...new Set(drawing.bars.map((b) => Math.round(b.x + b.w / 2)))].sort((a, b) => a - b);
    const box = await canvas.boundingBox();
    if (!box) throw new Error('Canvas not visible');
    // Bars of a slot share their centre; a zero-height bar may be missing, so
    // fall back to an even split of the chart width.
    const x = xs.length === slots ? xs[index] : box.width * ((index + 0.5) / slots);
    await this.page.mouse.move(box.x + x, box.y + box.height / 2);
  }

  async storedMode(): Promise<string | null> {
    return this.page.evaluate(() => localStorage.getItem('statistics.admin.graph-mode'));
  }
}
