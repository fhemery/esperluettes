import { type Locator, type Page } from '@playwright/test';
import { ConfirmModal } from './ConfirmModal';

/**
 * The calendar activity admin form, create and edit.
 *
 * The type-specific panel is contributed by the activity type: on create every
 * type's panel is in the DOM and Alpine shows the one matching the picked type,
 * on edit only the activity's own is rendered. The shuffle block lives outside
 * the `<form>` (nested forms are illegal), pushed to a stack the page prints
 * after it — so it is reached from the page, not from the form.
 */
export class AdminActivityFormPage {
  readonly modal: ConfirmModal;

  constructor(private readonly page: Page) {
    this.modal = new ConfirmModal(page);
  }

  async gotoCreate(): Promise<number> {
    const response = await this.page.goto('/admin/calendar/activities/create');
    return response?.status() ?? 0;
  }

  /**
   * Reach an activity's edit form the way an admin does — from the list.
   * Ids are not pinned by the seeder, and the name is what the list shows.
   */
  async gotoEditByName(activityName: string): Promise<void> {
    await this.gotoIndex();
    await this.rowEditLink(activityName).click();
    await this.page.waitForURL(/\/admin\/calendar\/activities\/\d+\/edit$/);
  }

  /** The admin nav entry that leads to the activity list. */
  get navActivitiesLink(): Locator {
    return this.page.locator('a[href$="/admin/calendar/activities"]').first();
  }

  async gotoIndex(): Promise<number> {
    const response = await this.page.goto('/admin/calendar/activities');
    return response?.status() ?? 0;
  }

  /** The edit link of the row naming this activity. */
  rowEditLink(activityName: string): Locator {
    return this.page.locator('tr', { hasText: activityName }).locator('a[href*="/edit"]').first();
  }

  get typeSelect(): Locator {
    return this.page.locator('select#activity_type');
  }

  get nameInput(): Locator {
    return this.page.locator('input#name');
  }

  get previewStartsAt(): Locator {
    return this.page.locator('input#preview_starts_at');
  }

  get activeStartsAt(): Locator {
    return this.page.locator('input#active_starts_at');
  }

  get activeEndsAt(): Locator {
    return this.page.locator('input#active_ends_at');
  }

  // --- Secret Gift's own panel -------------------------------------------

  get giftConfigPanel(): Locator {
    return this.page.getByTestId('sg-config-panel');
  }

  get registrationEndsAt(): Locator {
    return this.page.locator('input#sg_registration_ends_at');
  }

  /**
   * The error `<x-shared::input-error>` renders under the deadline field — a
   * `<ul>`, not a `<p>`. Anchoring on `text-error` instead would match the red
   * `*` the required-field label prints.
   */
  get registrationEndsAtError(): Locator {
    return this.giftConfigPanel.locator('ul.text-red-600');
  }

  get shufflePanel(): Locator {
    return this.page.getByTestId('sg-shuffle-panel');
  }

  get shuffleButton(): Locator {
    return this.page.getByTestId('sg-shuffle-button');
  }

  /** The sentence explaining why the button is inert, when it is. */
  get shuffleBlockedReason(): Locator {
    return this.page.getByTestId('sg-shuffle-blocked');
  }

  get shuffleParticipantNames(): Locator {
    return this.shufflePanel.locator('li span');
  }

  get submitButton(): Locator {
    return this.page.locator('form button[type="submit"]').first();
  }

  /** Rendered twice on admin pages — the layout prints one and the page another. */
  flash(text: string | RegExp): Locator {
    return this.page.getByText(text).first();
  }
}
