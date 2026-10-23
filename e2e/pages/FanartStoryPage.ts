import { expect, type Locator, type Page } from '@playwright/test';

/** The fanart viewer modal, as opened from a thumbnail, a group list or a deep link. */
export class FanartViewer {
  constructor(private readonly page: Page) {}

  get dialog(): Locator {
    return this.page.locator('#fanart-viewer-modal');
  }

  get content(): Locator {
    return this.dialog.locator('[data-fanart-viewer]');
  }

  get image(): Locator {
    return this.content.locator('img');
  }

  get heading(): Locator {
    return this.content.getByRole('heading');
  }

  get closeButton(): Locator {
    return this.dialog.getByRole('button', { name: 'Fermer' });
  }

  async expectOpen(): Promise<void> {
    await expect(this.dialog).toBeVisible();
    await expect(this.content).toBeVisible();
  }
}

/** The story page's Chapitres / Fanarts tabs and what they hold. */
export class FanartStoryPage {
  readonly viewer: FanartViewer;

  constructor(
    private readonly page: Page,
    private readonly storySlug: string,
  ) {
    this.viewer = new FanartViewer(page);
  }

  get path(): string {
    return `/stories/${this.storySlug}`;
  }

  async goto(query = ''): Promise<void> {
    await this.page.goto(this.path + query);
  }

  get fanartsTab(): Locator {
    return this.page.getByRole('tab', { name: 'Fanarts' });
  }

  audioIcon(chapterId: number): Locator {
    return this.page.locator(`[data-chapter-audio-icon="${chapterId}"]`);
  }

  get player(): Locator {
    return this.page.locator('[data-fanart-player]');
  }

  group(key: string | number): Locator {
    return this.page.locator(`[data-group-key="${key}"]`);
  }

  get groups(): Locator {
    return this.page.locator('[data-group-key]');
  }

  thumbnail(id: number): Locator {
    return this.page.locator(`[data-fanart-id="${id}"]`).first();
  }

  async openFanartsTab(): Promise<void> {
    await this.fanartsTab.click();
    await expect(this.fanartsTab).toHaveAttribute('aria-selected', 'true');
  }
}

/** « Proposer un fanart » form. */
export class FanartCreatePage {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string,
  ) {}

  async goto(): Promise<void> {
    await this.page.goto(`/stories/${this.storySlug}/fanarts/create/image`);
  }

  get title(): Locator {
    return this.page.locator('#fanart-title');
  }

  get imageInput(): Locator {
    return this.page.locator('input[type="file"][name*="image"]').first();
  }

  get attestation(): Locator {
    return this.page.locator('input[name="attestation"]');
  }

  get submit(): Locator {
    return this.page.locator('form button[type="submit"]').last();
  }
}

/** « Proposer un fanart » form: the optional message to the authors. */
export class FanartMessageForm {
  constructor(
    private readonly page: Page,
    private readonly storySlug: string,
  ) {}

  async goto(kind: 'image' | 'audio'): Promise<void> {
    await this.page.goto(`/stories/${this.storySlug}/fanarts/create/${kind}`);
  }

  get message(): Locator {
    return this.page.locator('#fanart-message');
  }

  get label(): Locator {
    return this.page.locator('label[for="fanart-message"]');
  }

  get help(): Locator {
    return this.page.locator('#fanart-message-help');
  }

  get title(): Locator {
    return this.page.locator('#fanart-title');
  }

  get chapter(): Locator {
    return this.page.locator('#fanart-chapter');
  }

  get attestation(): Locator {
    return this.page.locator('input[name="attestation"]');
  }

  get submit(): Locator {
    return this.page.locator('form button[type="submit"]').last();
  }

  get messageError(): Locator {
    return this.message.locator('xpath=ancestor::div[1]').locator('ul.text-red-600');
  }

  get form(): Locator {
    return this.page.locator('form[action*="/fanarts"]');
  }

  async attachImage(file: string): Promise<void> {
    await this.page.locator('input[type="file"][name*="image"]').first().setInputFiles(file);
  }

  async attachSound(file: string): Promise<void> {
    await this.page.locator('input[type="file"][name*="sound"]').first().setInputFiles(file);
  }

  /** Evidence shot, only when E2E_SHOTS_DIR is set. */
  async shot(name: string): Promise<void> {
    const dir = process.env.E2E_SHOTS_DIR;
    if (dir) await this.page.screenshot({ path: `${dir}/${name}.png`, fullPage: true });
  }
}
