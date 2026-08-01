/**
 * The seeded world, mirrored in TypeScript.
 *
 * Every value here is written by a domain's `E2e*Seeder` and rebuilt from
 * scratch before each run — keep the two sides in step:
 *
 *   app/Domains/Auth/Database/Seeders/E2eAccountsSeeder.php
 *   app/Domains/Story/Database/Seeders/E2eStorySeeder.php
 *   app/Domains/News/Database/Seeders/E2eNewsSeeder.php
 *
 * Specs must read fixtures from here rather than hard-coding slugs, so that a
 * change to a seeder breaks compilation instead of a selector three files away.
 */

export type RoleName = 'admin' | 'moderator' | 'author' | 'confirmed' | 'user';

export interface Account {
  readonly email: string;
  readonly password: string;
  readonly displayName: string;
  readonly profileSlug: string;
}

const password = 'password';

export const ACCOUNTS: Record<RoleName, Account> = {
  admin: { email: 'admin@e2e.test', password, displayName: 'E2E Admin', profileSlug: 'e2e-admin' },
  moderator: { email: 'moderator@e2e.test', password, displayName: 'E2E Moderator', profileSlug: 'e2e-moderator' },
  author: { email: 'author@e2e.test', password, displayName: 'E2E Author', profileSlug: 'e2e-author' },
  confirmed: { email: 'confirmed@e2e.test', password, displayName: 'E2E Confirmed', profileSlug: 'e2e-confirmed' },
  user: { email: 'user@e2e.test', password, displayName: 'E2E User', profileSlug: 'e2e-user' },
};

export const ROLES = Object.keys(ACCOUNTS) as RoleName[];

/**
 * Stories and chapters are addressed by slug-with-id ('mon-histoire-1'); the
 * app 301s any other spelling to that exact string.
 */
export const STORY = {
  slug: 'histoire-e2e-1',
  title: 'Histoire E2E',
  publishedChapter: { slug: 'chapitre-publie-1', title: 'Chapitre publié' },
  draftChapter: { slug: 'chapitre-brouillon-2', title: 'Chapitre brouillon' },
  /** Simple mode, six paragraphs — the typography reference. Never mutated. */
  simpleChapter: { slug: 'chapitre-simple-3', title: 'Chapitre simple' },
  /** The same six paragraphs, split 2/2/2 across three text blocks. Never mutated. */
  advancedChapter: { slug: 'chapitre-avance-4', title: 'Chapitre avancé' },
  /** Used only for the no-op-conversion word-count check. */
  countedChapter: { slug: 'chapitre-compte-5', title: 'Chapitre compté' },
} as const;

/** Story 2: co-authored by `author` and `confirmed`. */
export const COAUTHORED_STORY = {
  slug: 'histoire-coecrite-2',
  title: 'Histoire coécrite E2E',
  chapter: { slug: 'chapitre-coecrit-6', title: 'Chapitre coécrit' },
} as const;

export const NEWS = { slug: 'actualite-e2e', title: 'Actualité E2E' } as const;

/** Where auth.setup.ts parks each role's cookies. */
export function storageStatePath(role: RoleName): string {
  return `e2e/.auth/${role}.json`;
}
