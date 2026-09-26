import { type Locator, type Page } from '@playwright/test';

/**
 * Settings → Notifications: one table, one row per notification type, one
 * toggle column per channel (Site first, then every active external channel —
 * Discord in the e2e world), one global save button.
 *
 * The markup has no test ids, so rows are found by their French label: in
 * this table the label *is* the row's identity, and nothing else on the page
 * repeats it (the tab strip shows the tab name, not type names).
 */
export class NotificationPreferencesPage {
  /** The full Settings page, notification tab active — `/settings/notification` is only the bare fragment Alpine swaps in. */
  static readonly PATH = '/settings?tab=notification';

  /** French labels, as rendered in the browser. */
  static readonly GROUP_MODERATION = 'Promotions & modération';
  static readonly ROW_PROMOTION_ACCEPTED = 'Ma demande de promotion a été acceptée';
  static readonly ROW_PROMOTION_REJECTED = 'Ma demande de promotion a été refusée';
  static readonly ROW_REPORT_SUBMITTED = 'Un nouveau signalement a été déposé';
  static readonly ROW_PROMOTION_REQUESTED = 'Une nouvelle demande de promotion a été déposée';

  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto(NotificationPreferencesPage.PATH);
  }

  get form(): Locator {
    return this.page.locator('form[action$="/notifications/preferences"]');
  }

  get table(): Locator {
    return this.form.locator('table');
  }

  /** The scroll wrapper around the table. */
  get scroller(): Locator {
    return this.form.locator('div.overflow-x-auto');
  }

  get headerCells(): Locator {
    return this.table.locator('thead th');
  }

  /** The Discord column header, including its "not linked" warning if any. */
  get discordHeader(): Locator {
    return this.headerCells.filter({ hasText: 'Discord' });
  }

  /** Every type row (group header rows excluded), in page order, as label text. */
  async typeLabels(): Promise<string[]> {
    const cells = this.table.locator('tbody tr:not(.bg-fg\\/3) > td:first-child');
    return (await cells.allInnerTexts()).map((t) => t.trim());
  }

  /** The type labels listed under one group header, up to the next group. */
  async labelsInGroup(groupTitle: string): Promise<string[]> {
    return this.table.locator('tbody tr').evaluateAll((rows, title) => {
      const out: string[] = [];
      let inGroup = false;
      for (const row of rows) {
        const isGroup = row.classList.contains('bg-fg/3');
        const text = (row.querySelector('td')?.textContent ?? '').trim();
        if (isGroup) {
          inGroup = text === title;
          continue;
        }
        if (inGroup) out.push(text);
      }
      return out;
    }, groupTitle);
  }

  row(label: string): Locator {
    return this.table.locator('tbody tr').filter({
      has: this.page.locator('td:first-child', { hasText: new RegExp(`^\\s*${escapeRegExp(label)}\\s*$`) }),
    });
  }

  /** `channel` is the column index after the label: 0 = Site, 1 = Discord. */
  checkbox(label: string, channel: 0 | 1): Locator {
    return this.row(label).locator('td').nth(channel + 1).locator('input[type="checkbox"]');
  }

  /**
   * The visible switch. The checkbox is an invisible overlay (`opacity-0`), so
   * click its `label`, as a user would.
   */
  toggle(label: string, channel: 0 | 1): Locator {
    return this.row(label).locator('td').nth(channel + 1).locator('label');
  }

  async save(): Promise<void> {
    // The form posts, then redirects back to this same URL — so wait for the
    // POST itself, not for a URL change that never happens.
    await Promise.all([
      this.page.waitForResponse(
        (r) => r.request().method() === 'POST' && r.url().endsWith('/notifications/preferences'),
      ),
      this.form.locator('button[type="submit"]').click(),
    ]);
    await this.page.waitForLoadState('networkidle');
  }
}

function escapeRegExp(s: string): string {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
