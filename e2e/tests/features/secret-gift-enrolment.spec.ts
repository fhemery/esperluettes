import { AdminActivityFormPage } from '../../pages/AdminActivityFormPage';
import { SecretGiftActivityPage } from '../../pages/SecretGiftActivityPage';
import { ACCOUNTS, GIFT, GIFTS } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * FEATURE — Secret Gift enrolment (docs/Feature_Planning/secret-gift-enrolment).
 *
 * Only the claims a PHP test cannot make. Who may join, what is stored, what a
 * given state renders and what the write endpoints refuse are all asserted in
 * app/Domains/Calendar/Tests/Feature/SecretGift/ — far more cheaply.
 *
 * What is left, and why each is here:
 *   - the type panel appears on *selecting* the type (Alpine x-show), and
 *     survives a validation round-trip,
 *   - Quill boots on the preferences editor, with the starter template in it,
 *     and re-boots holding what was saved,
 *   - the leave and shuffle confirm modals (Alpine), including that cancel
 *     changes nothing,
 *   - the shuffle button is really inert, not merely styled as such,
 *   - the gift tab strip still switches after the enrolment work,
 *   - both surfaces at a 390 px viewport.
 */

const MOBILE = { width: 390, height: 844 };

// ---------------------------------------------------------------------------
// Admin — create form
// ---------------------------------------------------------------------------

test('the secret gift panel appears only once the type is picked, and carries no participant cap', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoCreate();

  // Present in the DOM for every type, shown for none until one is picked.
  await expect(form.giftConfigPanel).toBeHidden();

  await form.typeSelect.selectOption('secret-gift');
  await expect(form.giftConfigPanel).toBeVisible();
  await expect(form.registrationEndsAt).toBeVisible();

  // The shuffle block belongs to an activity that exists.
  await expect(form.shufflePanel).toHaveCount(0);

  // The two fields this task removed must be gone from the whole form.
  await expect(admin.getByText('Inscription requise')).toHaveCount(0);
  await expect(admin.getByText('Nombre max')).toHaveCount(0);
  await expect(admin.locator('[name="max_participants"], [name="requires_subscription"]')).toHaveCount(0);
});

test('a deadline before the preview start is refused, and the panel comes back filled in', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoCreate();

  const name = `Cadeau surprise E2E ${Date.now()}`;
  await form.typeSelect.selectOption('secret-gift');
  await form.nameInput.fill(name);
  await form.previewStartsAt.fill('2030-01-10T10:00');
  await form.activeStartsAt.fill('2030-02-10T10:00');
  await form.activeEndsAt.fill('2030-03-10T10:00');
  await form.registrationEndsAt.fill('2030-01-05T10:00');   // before the preview start

  await form.submitButton.click();

  // Back on the form: the panel must still be *shown*, which only happens if
  // Alpine restored the picked type from `old()`.
  await expect(form.giftConfigPanel).toBeVisible();
  await expect(form.registrationEndsAtError).toContainText(
    "La fin des inscriptions ne peut pas précéder l'ouverture de l'activité.",
  );

  // Every entered value survived the round-trip.
  await expect(form.nameInput).toHaveValue(name);
  await expect(form.typeSelect).toHaveValue('secret-gift');
  await expect(form.previewStartsAt).toHaveValue('2030-01-10T10:00');
  await expect(form.activeStartsAt).toHaveValue('2030-02-10T10:00');
  await expect(form.registrationEndsAt).toHaveValue('2030-01-05T10:00');
});

// ---------------------------------------------------------------------------
// Admin — edit form and shuffle
// ---------------------------------------------------------------------------

test('the edit form prefills the deadline and lists participants without their preferences', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.open.name);

  await expect(form.registrationEndsAt).not.toHaveValue('');
  await expect(form.shufflePanel).toBeVisible();
  await expect(form.shufflePanel).toContainText('3 inscrit(e)s');
  await expect(form.shuffleParticipantNames).toHaveText([
    ACCOUNTS.admin.displayName,
    ACCOUNTS.author.displayName,
    ACCOUNTS.moderator.displayName,
  ]);

  // `author` wrote these; only their assigned giver may ever read them.
  await expect(admin.locator('body')).not.toContainText(GIFT.secretPreferences);
  await expect(form.shuffleButton).toBeEnabled();
  await expect(form.shuffleBlockedReason).toHaveCount(0);
});

test('the shuffle button is genuinely inert below two participants', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.alone.name);

  await expect(form.shuffleBlockedReason).toHaveText('Il faut au moins 2 inscrit(e)s pour lancer le tirage.');
  await expect(form.shuffleButton).toBeDisabled();

  // A disabled button that still opened the modal would be a trap.
  await form.shuffleButton.click({ force: true });
  await expect(form.modal.root).toHaveCount(0);
});

test('the shuffle button is inert once the activity is running, with that reason', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.active.name);

  await expect(form.shuffleBlockedReason).toHaveText('L\'activité a commencé : le tirage ne peut plus être relancé.');
  await expect(form.shuffleButton).toBeDisabled();
});

test('cancelling the shuffle modal shuffles nothing', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.toShuffle.name);

  await expect(form.shufflePanel).toContainText("Le tirage n'a pas encore été effectué.");

  await form.shuffleButton.click();
  await expect(form.modal.root).toBeVisible();
  await expect(form.modal.title).toHaveText('Lancer le tirage ?');
  await expect(form.modal.root).toContainText('supprime toutes les attributions existantes');
  await expect(form.modal.root).toContainText('définitivement perdus');

  await form.modal.cancel();
  await expect(form.modal.root).toHaveCount(0);
  await expect(form.shufflePanel).toContainText("Le tirage n'a pas encore été effectué.");
});

test('confirming the shuffle pairs everyone and says so', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.toShuffle.name);

  await form.shuffleButton.click();
  await form.modal.confirm();

  await expect(form.flash('Le tirage a été effectué pour 3 participant(e)s.')).toBeVisible();
  await expect(form.shufflePanel).toContainText('Le tirage a déjà été effectué');
});

test('a moderator reaches the same panel and the same shuffle button', async ({ moderator }) => {
  const form = new AdminActivityFormPage(moderator);
  await form.gotoIndex();

  // The admin nav must carry them there, not just the URL.
  await expect(form.navActivitiesLink).toBeVisible();

  await form.rowEditLink(GIFTS.open.name).click();
  await moderator.waitForURL(/\/admin\/calendar\/activities\/\d+\/edit$/);

  await expect(form.giftConfigPanel).toBeVisible();
  await expect(form.shufflePanel).toBeVisible();
  await expect(form.shuffleButton).toBeEnabled();
});

// ---------------------------------------------------------------------------
// Activity page — the reader's side
// ---------------------------------------------------------------------------

test('the join form boots Quill with the starter template and no participant list', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.open.slug);
  await activity.goto();

  await expect(activity.joinPanel).toBeVisible();
  await activity.preferences.waitUntilReady();
  await expect(activity.preferences.toolbar).toBeVisible();
  await expect(activity.preferences.toolbar.locator('button')).not.toHaveCount(0);

  // Quill loaded the template, not an empty document.
  const body = activity.preferences.body;
  await expect(body).toContainText("Ce que j'aime");
  await expect(body).toContainText("Ce que je n'aime pas");
  await expect(body).toContainText('Fanart autorisé');
  await expect(body).toContainText('Genres préférés');
  await expect(body).toContainText('Autres informations');

  await expect(activity.participantList).toHaveCount(0);
  await expect(activity.leaveButton).toHaveCount(0);
});

test('joining, editing and leaving from the page', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.joinable.slug);
  await activity.goto();

  // --- join
  await activity.preferences.waitUntilReady();
  await activity.preferences.fill('Des chaussettes en laine de yak.');
  await activity.submitButton.click();

  await expect(activity.flash('Vous êtes bien inscrit(e) à cette activité !')).toBeVisible();
  await expect(activity.preferencesPanel).toBeVisible();
  await expect(activity.joinPanel).toHaveCount(0);
  await expect(activity.leaveButton).toBeVisible();

  // Quill re-booted holding what was stored, not the template again.
  await activity.preferences.waitUntilReady();
  await expect(activity.preferences.body).toContainText('chaussettes en laine de yak');
  await expect(activity.preferences.body).not.toContainText("Ce que j'aime");

  // The list appeared, and names the others.
  await expect(activity.participantList).toBeVisible();
  await expect(activity.participantNames).toHaveText([
    ACCOUNTS.admin.displayName,
    ACCOUNTS.author.displayName,
    ACCOUNTS.confirmed.displayName,
  ]);

  // --- edit
  await activity.preferences.fill('Finalement, du thé fumé.');
  await activity.submitButton.click();
  await expect(activity.flash('Vos préférences ont bien été enregistrées.')).toBeVisible();
  await activity.preferences.waitUntilReady();
  await expect(activity.preferences.body).toContainText('thé fumé');

  // --- leave, cancelled
  await activity.leaveButton.click();
  await expect(activity.modal.root).toBeVisible();
  await expect(activity.modal.title).toHaveText('Vous désinscrire de cette activité ?');
  await activity.modal.cancel();
  await expect(activity.modal.root).toHaveCount(0);
  await expect(activity.preferencesPanel).toBeVisible();

  // --- leave, confirmed
  await activity.leaveButton.click();
  await activity.modal.confirm();
  await expect(activity.flash("Vous n'êtes plus inscrit(e) à cette activité.")).toBeVisible();
  await expect(activity.joinPanel).toBeVisible();
  await expect(activity.participantList).toHaveCount(0);
});

test('the participant list carries names and nothing else', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.enrolled.slug);
  await activity.goto();

  await expect(activity.participantNames).toHaveText([
    ACCOUNTS.admin.displayName,
    ACCOUNTS.author.displayName,
    ACCOUNTS.confirmed.displayName,
    ACCOUNTS.moderator.displayName,
  ]);

  const list = await activity.participantList.innerText();
  expect(list).not.toContain('chats');            // this caller's own preferences
  expect(list).not.toContain('offre');            // no pairing wording of any kind
  expect(list).not.toContain('destinataire');
});

test('a lone participant reads a sentence, not an empty box', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.alone.slug);
  await activity.goto();

  await expect(activity.aloneLine).toHaveText('Vous êtes pour le moment la seule personne inscrite.');
  await expect(activity.participantNames).toHaveCount(0);
});

test('once the deadline has passed a participant sees their preferences read-only', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.closed.slug);
  await activity.goto();

  await expect(activity.closedParticipantPanel).toContainText(
    'Les inscriptions sont fermées : vos préférences ne sont plus modifiables.',
  );
  await expect(activity.closedParticipantPanel).toContainText('les livres anciens');

  await expect(activity.preferencesPanel).toHaveCount(0);
  await expect(activity.leaveButton).toHaveCount(0);
  await expect(activity.preferences.body).toHaveCount(0);
  await expect(activity.participantList).toBeVisible();
});

test('once the deadline has passed an outsider is told so and sees no list', async ({ author }) => {
  const activity = new SecretGiftActivityPage(author, GIFTS.closed.slug);
  await activity.goto();

  await expect(activity.closedOutsiderPanel).toContainText('Les inscriptions sont fermées.');
  await expect(activity.joinPanel).toHaveCount(0);
  await expect(activity.participantList).toHaveCount(0);
});

test('a shuffle closes registration before the deadline it was given', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.shuffled.slug);
  await activity.goto();

  await expect(activity.closedParticipantPanel).toContainText('Les inscriptions sont fermées');
  await expect(activity.preferencesPanel).toHaveCount(0);
  await expect(activity.leaveButton).toHaveCount(0);
  await expect(activity.participantList).toBeVisible();
});

test('the running gift UI still switches tabs and leaked no enrolment control', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.active.slug);
  await activity.goto();

  await expect(activity.giftTab('prepare')).toHaveAttribute('aria-selected', 'true');
  await expect(activity.giftPreparePanel).toBeVisible();

  // `confirmed` gives to `author`, whose preferences are theirs alone to read.
  await expect(activity.giftPreparePanel).toContainText(ACCOUNTS.author.displayName);
  await expect(activity.giftPreparePanel).toContainText(GIFT.recipientPreferences);

  await activity.giftTab('received').click();
  await expect(activity.giftTab('received')).toHaveAttribute('aria-selected', 'true');
  await expect(activity.giftReceivedPanel).toBeVisible();
  await expect(activity.giftPreparePanel).toBeHidden();

  await expect(activity.joinPanel).toHaveCount(0);
  await expect(activity.preferencesPanel).toHaveCount(0);
  await expect(activity.leaveButton).toHaveCount(0);
});

// ---------------------------------------------------------------------------
// Mobile
// ---------------------------------------------------------------------------

test('the join form fits a phone', async ({ confirmed }) => {
  await confirmed.setViewportSize(MOBILE);
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.open.slug);
  await activity.goto();

  await activity.preferences.waitUntilReady();
  await expect(activity.preferences.toolbar).toBeVisible();

  // Nothing in the enrolment block may spill sideways.
  expect(await activity.clippedElements(), 'clipped by the viewport').toEqual([]);

  for (const el of [activity.joinPanel, activity.preferences.toolbar, activity.submitButton]) {
    const box = await el.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.x).toBeGreaterThanOrEqual(0);
    expect(box!.x + box!.width).toBeLessThanOrEqual(MOBILE.width);
  }
});

test('the enrolled panel and participant list fit a phone', async ({ confirmed }) => {
  await confirmed.setViewportSize(MOBILE);
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.enrolled.slug);
  await activity.goto();

  await activity.preferences.waitUntilReady();
  await expect(activity.participantList).toBeVisible();
  await expect(activity.leaveButton).toBeVisible();

  expect(await activity.clippedElements(), 'clipped by the viewport').toEqual([]);

  for (const el of [activity.participantList, activity.leaveButton]) {
    const box = await el.boundingBox();
    expect(box).not.toBeNull();
    expect(box!.x + box!.width).toBeLessThanOrEqual(MOBILE.width);
  }

  // The leave modal has to be usable there too.
  await activity.leaveButton.click();
  await expect(activity.modal.root).toBeVisible();
  await expect(activity.modal.confirmButton).toBeInViewport();
  const modalBox = await activity.modal.panel.boundingBox();
  expect(modalBox!.x + modalBox!.width).toBeLessThanOrEqual(MOBILE.width);
});

test('the shuffle panel and its modal fit a phone', async ({ admin }) => {
  await admin.setViewportSize(MOBILE);
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.open.name);

  await expect(form.shufflePanel).toBeVisible();
  await expect(form.shuffleParticipantNames.first()).toBeVisible();

  const panelBox = await form.shufflePanel.boundingBox();
  expect(panelBox!.x + panelBox!.width).toBeLessThanOrEqual(MOBILE.width);

  await form.shuffleButton.click();
  await expect(form.modal.root).toBeVisible();
  await expect(form.modal.confirmButton).toBeInViewport();
  await expect(form.modal.cancelButton).toBeInViewport();
  const modalBox = await form.modal.panel.boundingBox();
  expect(modalBox!.x + modalBox!.width).toBeLessThanOrEqual(MOBILE.width);
});
