import { type Locator, type Page } from '@playwright/test';
import { ConfirmModal } from './ConfirmModal';
import { RichTextEditor } from './RichTextEditor';

/**
 * The reader-facing calendar activity page for a *Cadeau surprise*.
 *
 * The page shows exactly one enrolment panel at a time — join, edit, or one of
 * the two closed states — so each is exposed as its own locator and a spec
 * asserts on which of them exists.
 */
export class SecretGiftActivityPage {
  readonly modal: ConfirmModal;

  constructor(private readonly page: Page, private readonly slug: string) {
    this.modal = new ConfirmModal(page);
  }

  async goto(): Promise<number> {
    const response = await this.page.goto(`/activities/${this.slug}`);
    return response?.status() ?? 0;
  }

  /** Registration open, caller not enrolled. */
  get joinPanel(): Locator {
    return this.page.getByTestId('sg-join-panel');
  }

  /** Registration open, caller enrolled. */
  get preferencesPanel(): Locator {
    return this.page.getByTestId('sg-preferences-panel');
  }

  /** Registration closed, caller enrolled: preferences read-only. */
  get closedParticipantPanel(): Locator {
    return this.page.getByTestId('sg-closed-participant');
  }

  /** Registration closed, caller not enrolled. */
  get closedOutsiderPanel(): Locator {
    return this.page.getByTestId('sg-closed-outsider');
  }

  /**
   * The preferences editor. Both the join and the edit panel render it with the
   * same id, and only one of the two is ever on the page.
   */
  get preferences(): RichTextEditor {
    return new RichTextEditor(this.page, 'sg_preferences');
  }

  /**
   * The submit of whichever enrolment form is showing.
   *
   * Direct child only: the leave confirm-modal lives inside the same panel and
   * carries a submit of its own.
   */
  get submitButton(): Locator {
    return this.page.locator(
      '[data-testid="sg-join-panel"] > form button[type="submit"], [data-testid="sg-preferences-panel"] > form button[type="submit"]',
    );
  }

  get leaveButton(): Locator {
    return this.page.getByTestId('sg-leave-button');
  }

  get participantList(): Locator {
    return this.page.getByTestId('sg-participants');
  }

  /** The display names shown in the participant list, in render order. */
  get participantNames(): Locator {
    return this.participantList.locator('li span');
  }

  /** The *you are alone for now* line, shown instead of a one-name list. */
  get aloneLine(): Locator {
    return this.participantList.locator('p');
  }

  /**
   * The two gift tabs, which only exist once the activity is active and
   * shuffled. `aria-selected` is bound by Alpine, so it is also the proof that
   * the tab strip is alive rather than merely rendered.
   */
  giftTab(key: 'prepare' | 'received'): Locator {
    return this.page.locator(`#tabs-tab-${key}`);
  }

  get giftPreparePanel(): Locator {
    return this.page.locator('#tabs-panel-prepare');
  }

  get giftReceivedPanel(): Locator {
    return this.page.locator('#tabs-panel-received');
  }

  /** Rendered twice on some layouts (responsive duplicates) — always `.first()`. */
  flash(text: string | RegExp): Locator {
    return this.page.getByText(text).first();
  }

  /**
   * Everything inside the enrolment block that sticks out of the viewport.
   *
   * Scoped to the block on purpose: the page header above it belongs to base
   * Calendar and has a clipping quirk of its own, which is not this feature's
   * to assert on.
   */
  async clippedElements(): Promise<string[]> {
    return this.page.evaluate(() => {
      const width = document.documentElement.clientWidth;
      const root = document.querySelector('.secret-gift-activity');
      if (!root) return ['no secret-gift block on the page'];

      return Array.from(root.querySelectorAll('*'))
        .filter((el) => {
          const box = el.getBoundingClientRect();
          if (box.width === 0 && box.height === 0) return false;
          return box.right > width + 0.5 || box.left < -0.5;
        })
        .map((el) => `${el.tagName}.${String(el.className).slice(0, 60)}`);
    });
  }
}
