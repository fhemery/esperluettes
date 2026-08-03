# Notification types catalog

> **Auto-generated** by `php artisan notifications:export-types-doc`. Do not edit by hand.

Generated at: 2026-08-03T07:18:37+00:00 (`locale`: `fr`)

## Group: Commentaires (`comments`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `story.chapter.comment` | `App\Domains\Story\Public\Notifications\ChapterCommentNotification` | J'ai reçu un commentaire sur l'une de mes histoires ou un de mes commentaires a reçu une réponse | `comment_id`, `author_name`, `author_slug`, `chapter_title`, `story_slug`, `chapter_slug`, `is_reply`, `story_name` | no | yes |
| `story.chapter.root_comment` | `App\Domains\Story\Public\Notifications\ChapterRootCommentNotification` | J'ai reçu un commentaire sur l'une de mes histoires | `comment_id`, `author_name`, `author_slug`, `chapter_title`, `story_slug`, `chapter_slug`, `story_name` | no | no |
| `story.chapter.reply_comment` | `App\Domains\Story\Public\Notifications\ChapterReplyCommentNotification` | Un de mes commentaires a reçu une réponse | `comment_id`, `author_name`, `author_slug`, `chapter_title`, `story_slug`, `chapter_slug`, `story_name` | no | no |

## Group: Collaboration (`collaboration`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `story.coauthor.chapter.created` | `App\Domains\Story\Public\Notifications\CoAuthorChapterCreatedNotification` | Un·e de mes co-auteurices a créé un chapitre sur une de nos histoires | `user_name`, `user_slug`, `story_title`, `story_slug`, `chapter_title`, `chapter_slug` | no | no |
| `story.coauthor.chapter.updated` | `App\Domains\Story\Public\Notifications\CoAuthorChapterUpdatedNotification` | Un·e de mes co-auteurices a modifié un chapitre sur une de nos histoires | `user_name`, `user_slug`, `story_title`, `story_slug`, `chapter_title`, `chapter_slug` | no | no |
| `story.coauthor.chapter.deleted` | `App\Domains\Story\Public\Notifications\CoAuthorChapterDeletedNotification` | Un·e de mes co-auteurices a supprimé un chapitre sur une de nos histoires | `user_name`, `user_slug`, `story_title`, `story_slug`, `chapter_title` | no | no |
| `story.collaborator.role_given` | `App\Domains\Story\Public\Notifications\CollaboratorRoleGivenNotification` | Un rôle de collaborateurice (auteurice, bêta-lecteurice...) m'a été accordé | `user_name`, `user_slug`, `story_title`, `story_slug`, `role` | no | no |
| `story.collaborator.removed` | `App\Domains\Story\Public\Notifications\CollaboratorRemovedNotification` | Un rôle de collaborateurice (bêta-lecteurice...) m'a été retiré | `user_name`, `user_slug`, `story_title`, `story_slug` | no | no |
| `story.collaborator.left` | `App\Domains\Story\Public\Notifications\CollaboratorLeftNotification` | Un·e collaborateurice a quitté une histoire | `user_name`, `user_slug`, `story_title`, `story_slug` | no | no |

## Group: Publication (`publication`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `story.chapter.scheduled_published` | `App\Domains\Story\Public\Notifications\ChapterScheduledPublishedNotification` | Un de mes chapitres a été publié automatiquement à la date planifiée | `story_title`, `story_slug`, `chapter_title`, `chapter_slug` | no | no |

## Group: Pile à Lire (PAL) (`readlist`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `readlist.story.added` | `App\Domains\ReadList\Public\Notifications\ReadListAddedNotification` | Une de mes histoires a été ajoutée à une PAL | `reader_name`, `reader_slug`, `story_title`, `story_slug` | no | no |
| `readlist.chapter.published` | `App\Domains\ReadList\Public\Notifications\ReadListChapterPublishedNotification` | Un nouveau chapitre d'une histoire de ma PAL a été publié | `author_name`, `author_slug`, `story_title`, `story_slug`, `chapter_title`, `chapter_slug` | no | no |
| `readlist.chapter.unpublished` | `App\Domains\ReadList\Public\Notifications\ReadListChapterUnpublishedNotification` | Un chapitre d'une histoire de ma PAL a été dépublié | `author_name`, `author_slug`, `story_title`, `story_slug`, `chapter_title`, `chapter_slug` | no | no |
| `readlist.story.deleted` | `App\Domains\ReadList\Public\Notifications\ReadListStoryDeletedNotification` | Une histoire de ma PAL a été supprimée | `author_name`, `author_slug`, `story_title` | no | no |
| `readlist.story.unpublished` | `App\Domains\ReadList\Public\Notifications\ReadListStoryUnpublishedNotification` | Une histoire de ma PAL a été dépubliée | `author_name`, `author_slug`, `story_title` | no | no |
| `readlist.story.republished` | `App\Domains\ReadList\Public\Notifications\ReadListStoryRepublishedNotification` | Une histoire de ma PAL a été republiée | `author_name`, `author_slug`, `story_title`, `story_slug` | no | no |
| `readlist.story.completed` | `App\Domains\ReadList\Public\Notifications\ReadListStoryCompletedNotification` | Une histoire de ma PAL a été marquée comme terminée | `author_name`, `author_slug`, `story_title`, `story_slug` | no | no |

## Group: Actualités (`news`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `news.published` | `App\Domains\News\Public\Notifications\NewsPublishedNotification` | Une actualité d'être publiée sur le site | `news_title`, `news_slug` | no | no |

## Group: Promotions & modération (`moderation`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `auth.promotion.accepted` | `App\Domains\Auth\Public\Notifications\PromotionAcceptedNotification` | Ma demande de promotion a été acceptée | `user_name` | yes | no |
| `auth.promotion.rejected` | `App\Domains\Auth\Public\Notifications\PromotionRejectedNotification` | Ma demande de promotion a été refusée | `user_name` | yes | no |

## Group: Suivi (`follow`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `follow.new_follower` | `App\Domains\Follow\Private\Notifications\NewFollowerNotification` | Une Esperluette vous suit | `follower_id`, `follower_name`, `follower_slug` | no | no |
| `follow.new_story` | `App\Domains\Follow\Private\Notifications\NewStoryNotification` | Une Esperluette que vous suivez a publié une nouvelle histoire | `author_id`, `author_name`, `author_slug`, `story_id`, `story_title`, `story_slug` | no | no |

## Group: Citations (`quote`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `quote.chapter_quoted` | `App\Domains\Quote\Public\Notifications\ChapterQuotedNotification` | Quelqu'un a cité un passage de votre chapitre | `quoter_id`, `quoter_name`, `quoter_slug`, `chapter_id`, `chapter_title`, `chapter_slug`, `story_id`, `story_title`, `story_slug` | no | no |

## Group: Calendrier (`calendar`)

| Type key | PHP class | User-facing label | Payload fields | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- | --- |
| `calendar.quote_contest.entry_removed` | `App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\EntryRemovedNotification` | Une de mes citations a été retirée d'un concours par la modération | `category_title`, `activity_slug`, `activity_name` | no | no |
| `calendar.quote_contest.submissions_open` | `App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsOpenNotification` | Les soumissions d'un concours de citations sont ouvertes | `activity_name`, `activity_slug` | no | no |
| `calendar.quote_contest.submissions_closing` | `App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\SubmissionsClosingNotification` | Les soumissions d'un concours de citations ferment bientôt | `activity_name`, `activity_slug` | no | no |
| `calendar.quote_contest.votes_open` | `App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesOpenNotification` | Les votes d'un concours de citations sont ouverts | `activity_name`, `activity_slug` | no | no |
| `calendar.quote_contest.votes_closing` | `App\Domains\Calendar\Private\Activities\QuoteContest\Notifications\VotesClosingNotification` | Les votes d'un concours de citations ferment bientôt | `activity_name`, `activity_slug` | no | no |

## Delivery channels

The built-in `website` channel is always available and is not part of the channel registry.

| Channel id | User-facing label | Default for new users | Feature-gated |
| --- | --- | --- | --- |
| `discord` | Discord | off | yes |

## Stored payload

Each notification row stores a `content_key` (the type key) and JSON from `NotificationContent::toData()`. The "Payload fields" column above lists the keys each type stores; see `toData()` and `fromData()` on the PHP class for value types.

External delivery channels receive this payload **verbatim**. In particular, the Discord bot API (`GET /api/discord/notifications/pending`) returns it unchanged as the `data` object, so the key names below are part of that endpoint's contract — renaming one is a breaking change for the bot.
