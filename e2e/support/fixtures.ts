/**
 * The seeded world, mirrored in TypeScript.
 *
 * Every value here is written by a domain's `E2e*Seeder` and rebuilt from
 * scratch before each run — keep the two sides in step:
 *
 *   app/Domains/Auth/Database/Seeders/E2eAccountsSeeder.php
 *   app/Domains/Story/Database/Seeders/E2eStorySeeder.php
 *   app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php
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
  /** Advanced: inline formatting, then a raw lazily-loaded image, then prose. */
  illustratedChapter: { slug: 'chapitre-illustre-7', title: 'Chapitre illustré' },
} as const;

/**
 * Story 2: co-authored by `author` and `confirmed`, beta-read by `moderator` —
 * the only way to prove that a collaborator who is not an author sees nothing
 * of the author view.
 */
export const COAUTHORED_STORY = {
  slug: 'histoire-coecrite-2',
  title: 'Histoire coécrite E2E',
  chapter: { slug: 'chapitre-coecrit-6', title: 'Chapitre coécrit' },
} as const;

/**
 * Reader quotes, written by `app/Domains/Quote/Database/Seeders/E2eQuotesSeeder.php`.
 * The author cannot quote their own story, so these can only come from a seeder.
 */
export const QUOTES = {
  /** Per chapter slug: how many quotes the badge must show. */
  countByChapter: {
    'chapitre-publie-1': 0,
    'chapitre-brouillon-2': 1,
    'chapitre-simple-3': 5,
    'chapitre-coecrit-6': 1,
    'chapitre-illustre-7': 2,
  },
  /** Quoted twice, by `confirmed` and `admin` — one summary row with a count of 2. */
  sharedPassage: 'La première phrase du premier bloc,',
  /** Overlaps the tail of `sharedPassage`, taking the tint to depth 3. */
  overlappingPassage: 'du premier bloc, assez longue',
  lonePassage: 'à comparer avec la précédente',
  /** Not in the chapter any more: counted, never tinted. */
  stalePassage: "Un passage qui n'existe plus dans ce chapitre",
  /** Spans `<em>` and `<strong>` inside one paragraph. */
  formattedPassage: "de l'italique et du gras",
  /** Below the illustrated chapter's lazily-loaded image. */
  belowImagePassage: "qui suit l'image",
} as const;

/**
 * One root comment on the published chapter, written by
 * `app/Domains/Comment/Database/Seeders/E2eCommentsSeeder.php`.
 */
export const COMMENTS = {
  chapterSlug: STORY.publishedChapter.slug,
  bodyMarker: 'Commentaire E2E pour éditeur',
  rootCommentId: 1,
} as const;

/**
 * News articles, written by `app/Domains/News/Database/Seeders/E2eNewsSeeder.php`.
 * `disposable` is meant to be deleted by a spec; nothing else may rely on it.
 */
export const NEWS = {
  slug: 'actualite-e2e',
  title: 'Actualité E2E',
  draft: { slug: 'actualite-e2e-brouillon', title: 'Actualité E2E brouillon' },
  disposable: { slug: 'actualite-e2e-a-supprimer', title: 'Actualité E2E à supprimer' },
} as const;

/**
 * The five *Concours de citations* activities, written by
 * `app/Domains/Calendar/Database/Seeders/E2eCalendarSeeder.php`.
 *
 * A contest's phase comes from the clock alone, so each phase needs its own
 * activity: there is no way to fast-forward one.
 */
export const CONTESTS = {
  beforeStart: { slug: 'concours-citations-avant' },
  submissions: { slug: 'concours-citations-soumissions' },
  interlude: { slug: 'concours-citations-entre-deux' },
  voting: { slug: 'concours-citations-votes' },
  ended: { slug: 'concours-citations-termine' },
} as const;

export const CONTEST = {
  /** Holds a quote of `confirmed` already — the replace / withdraw path. */
  filledCategory: 'Meilleure ouverture',
  /** Left empty on purpose — the first-submission path. */
  emptyCategory: 'Plus belle métaphore',
  /** The passage `confirmed` has already entered. */
  sittingPassage: 'La première phrase du premier bloc,',
  /** Renamed since the entry was written; the slug still resolves. */
  staleStoryTitle: "Ancien titre de l'histoire",
  /** Deleted since the entry was written; the link must 404, not crash. */
  deadChapterSlug: 'chapitre-supprime-99',
  /** What `Résultats` prints when the submitter no longer resolves. */
  unknownSubmitter: 'Compte supprimé',
  /** Quotes of `confirmed` the picker must list but refuse, with their reason. */
  ineligible: {
    privateStory: { passage: "Un passage tiré d'une histoire privée", reason: 'Histoire privée' },
    excluded: {
      passage: "Un passage tiré d'une histoire hors événements",
      reason: 'Histoire exclue des événements',
    },
  },
  /** `confirmed` owns 200 filler quotes; this one is singled out by the filter. */
  longBookNeedle: 'Passage numéro 137 du carnet',
  longBookSize: 200,
} as const;

/** Where auth.setup.ts parks each role's cookies. */
export function storageStatePath(role: RoleName): string {
  return `e2e/.auth/${role}.json`;
}
