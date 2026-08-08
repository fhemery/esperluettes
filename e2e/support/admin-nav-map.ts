/**
 * Expected admin sidebar `data-nav-key` values per staff role.
 *
 * Hardcoded keys (`dashboard`, `back-to-site`) are invented for the two links
 * outside AdminNavigationRegistry. Every other key is a `registerPage` key —
 * update this map in the same change when a domain adds, removes, or
 * re-permissions an admin nav page.
 */

export type StaffRole = 'moderator' | 'admin' | 'tech_admin';

const SHARED = ['dashboard', 'back-to-site'] as const;

const MODERATOR_PAGES = [
  'calendar.activities',
  'moderation.reasons',
  'moderation.reports',
  'moderation.admin.user-management',
  'story.admin.moderation',
  'news.management',
  'news.pinned',
  'auth.promotion_requests',
  'events.admin.domain-events',
] as const;

const ADMIN_EXTRA = [
  'config.parameters',
  'config.feature-toggles',
  'statistics.admin',
  'static.pages',
  'story_ref.audiences',
  'story_ref.copyrights',
  'story_ref.feedbacks',
  'story_ref.genres',
  'story_ref.statuses',
  'story_ref.trigger_warnings',
  'story_ref.types',
  'auth.users',
  'auth.roles',
  'auth.activation_codes',
  'faq.categories',
  'faq.questions',
] as const;

const TECH_ADMIN_EXTRA = ['maintenance', 'logs'] as const;

function sortedUnique(keys: readonly string[]): string[] {
  return [...new Set(keys)].sort();
}

export const ADMIN_NAV_KEYS: Record<StaffRole, readonly string[]> = {
  moderator: sortedUnique([...SHARED, ...MODERATOR_PAGES]),
  admin: sortedUnique([...SHARED, ...MODERATOR_PAGES, ...ADMIN_EXTRA]),
  tech_admin: sortedUnique([...SHARED, ...MODERATOR_PAGES, ...ADMIN_EXTRA, ...TECH_ADMIN_EXTRA]),
};
