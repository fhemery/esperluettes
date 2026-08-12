import { AdminActivityFormPage } from '../../pages/AdminActivityFormPage';
import { SecretGiftActivityPage } from '../../pages/SecretGiftActivityPage';
import { GIFTS } from '../../support/fixtures';
import { expect, test } from '../../support/test';

/**
 * CORE — `<x-shared::confirm-modal>`.
 *
 * The modal guards every destructive action in the app that has one: deleting a
 * chapter, removing a collaborator, leaving a Secret Gift, re-running the
 * shuffle. It is pure Alpine plus a nested `<form>`, so a PHP test can only
 * assert that the markup was printed — whether the overlay actually opens,
 * whether *Annuler* closes it without submitting, and whether *Confirmer*
 * submits the modal's own method-spoofed form rather than the page's, exist
 * only after JavaScript has run.
 *
 * A silent break here either makes destructive actions unreachable everywhere,
 * or — worse — makes them fire on cancel. That is what earns it a place in core.
 *
 * Driven through the Secret Gift screens because they are the only surface
 * mounting both a `DELETE` and a `POST` instance; the assertions are about the
 * modal, not about Secret Gift.
 */

test('a DELETE modal: cancel changes nothing, confirm submits it', async ({ confirmed }) => {
  const activity = new SecretGiftActivityPage(confirmed, GIFTS.enrolled.slug);
  await activity.goto();

  // --- cancelled
  await activity.leaveButton.click();
  await expect(activity.modal.root).toBeVisible();
  await expect(activity.modal.title).toHaveText('Vous désinscrire de cette activité ?');

  await activity.modal.cancel();
  await expect(activity.modal.root).toHaveCount(0);
  await expect(activity.preferencesPanel).toBeVisible();

  // --- confirmed: a real navigation, and the DELETE the form spoofs
  await activity.leaveButton.click();
  await activity.modal.confirm();

  await expect(activity.flash("Vous n'êtes plus inscrit(e) à cette activité.")).toBeVisible();
  await expect(activity.joinPanel).toBeVisible();
});

test('a POST modal nested in another form: cancel changes nothing, confirm submits it', async ({ admin }) => {
  const form = new AdminActivityFormPage(admin);
  await form.gotoEditByName(GIFTS.toShuffle.name);

  // --- cancelled
  await expect(form.shufflePanel).toContainText("Le tirage n'a pas encore été effectué.");

  await form.shuffleButton.click();
  await expect(form.modal.root).toBeVisible();
  await expect(form.modal.title).toHaveText('Lancer le tirage ?');

  await form.modal.cancel();
  await expect(form.modal.root).toHaveCount(0);
  await expect(form.shufflePanel).toContainText("Le tirage n'a pas encore été effectué.");

  // --- confirmed: the modal's own form posts, not the activity form it sits beside
  await form.shuffleButton.click();
  await form.modal.confirm();

  await expect(form.flash('Le tirage a été effectué pour 3 participant(e)s.')).toBeVisible();
  await expect(form.shufflePanel).toContainText('Le tirage a déjà été effectué');
});
