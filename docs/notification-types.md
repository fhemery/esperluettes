# Notification types catalog

> **Auto-generated** by `php artisan notifications:export-types-doc`. Do not edit by hand.

Generated at: 2026-05-10T18:41:42+00:00 (`locale`: `fr`)

## Group: Commentaires (`comments`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `story.chapter.comment` | `App\Domains\Story\Public\Notifications\ChapterCommentNotification` | J'ai reçu un commentaire sur l'une de mes histoires ou un de mes commentaires a reçu une réponse | no | yes |
| `story.chapter.root_comment` | `App\Domains\Story\Public\Notifications\ChapterRootCommentNotification` | J'ai reçu un commentaire sur l'une de mes histoires | no | no |
| `story.chapter.reply_comment` | `App\Domains\Story\Public\Notifications\ChapterReplyCommentNotification` | Un de mes commentaires a reçu une réponse | no | no |

## Group: Collaboration (`collaboration`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `story.coauthor.chapter.created` | `App\Domains\Story\Public\Notifications\CoAuthorChapterCreatedNotification` | Un·e de mes co-auteurices a créé un chapitre sur une de nos histoires | no | no |
| `story.coauthor.chapter.updated` | `App\Domains\Story\Public\Notifications\CoAuthorChapterUpdatedNotification` | Un·e de mes co-auteurices a modifié un chapitre sur une de nos histoires | no | no |
| `story.coauthor.chapter.deleted` | `App\Domains\Story\Public\Notifications\CoAuthorChapterDeletedNotification` | Un·e de mes co-auteurices a supprimé un chapitre sur une de nos histoires | no | no |
| `story.collaborator.role_given` | `App\Domains\Story\Public\Notifications\CollaboratorRoleGivenNotification` | Un rôle de collaborateurice (auteurice, bêta-lecteurice...) m'a été accordé | no | no |
| `story.collaborator.removed` | `App\Domains\Story\Public\Notifications\CollaboratorRemovedNotification` | Un rôle de collaborateurice (bêta-lecteurice...) m'a été retiré | no | no |
| `story.collaborator.left` | `App\Domains\Story\Public\Notifications\CollaboratorLeftNotification` | Un·e collaborateurice a quitté une histoire | no | no |

## Group: Pile à Lire (PAL) (`readlist`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `readlist.story.added` | `App\Domains\ReadList\Public\Notifications\ReadListAddedNotification` | Une de mes histoires a été ajoutée à une PAL | no | no |
| `readlist.chapter.published` | `App\Domains\ReadList\Public\Notifications\ReadListChapterPublishedNotification` | Un nouveau chapitre d'une histoire de ma PAL a été publié | no | no |
| `readlist.chapter.unpublished` | `App\Domains\ReadList\Public\Notifications\ReadListChapterUnpublishedNotification` | Un chapitre d'une histoire de ma PAL a été dépublié | no | no |
| `readlist.story.deleted` | `App\Domains\ReadList\Public\Notifications\ReadListStoryDeletedNotification` | Une histoire de ma PAL a été supprimée | no | no |
| `readlist.story.unpublished` | `App\Domains\ReadList\Public\Notifications\ReadListStoryUnpublishedNotification` | Une histoire de ma PAL a été dépubliée | no | no |
| `readlist.story.republished` | `App\Domains\ReadList\Public\Notifications\ReadListStoryRepublishedNotification` | Une histoire de ma PAL a été republiée | no | no |
| `readlist.story.completed` | `App\Domains\ReadList\Public\Notifications\ReadListStoryCompletedNotification` | Une histoire de ma PAL a été marquée comme terminée | no | no |

## Group: Actualités (`news`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `news.published` | `App\Domains\News\Public\Notifications\NewsPublishedNotification` | Une actualité d'être publiée sur le site | no | no |

## Group: Promotions & modération (`moderation`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `auth.promotion.accepted` | `App\Domains\Auth\Public\Notifications\PromotionAcceptedNotification` | Ma demande de promotion a été acceptée | yes | no |
| `auth.promotion.rejected` | `App\Domains\Auth\Public\Notifications\PromotionRejectedNotification` | Ma demande de promotion a été refusée | yes | no |

## Group: Suivi (`follow`)

| Type key | PHP class | User-facing label | Forced on website | Hidden in preferences UI |
| --- | --- | --- | --- | --- |
| `follow.new_follower` | `App\Domains\Follow\Private\Notifications\NewFollowerNotification` | Une Esperluette vous suit | no | no |
| `follow.new_story` | `App\Domains\Follow\Private\Notifications\NewStoryNotification` | Une Esperluette que vous suivez a publié une nouvelle histoire | no | no |

## Delivery channels

The built-in `website` channel is always available and is not part of the channel registry.

| Channel id | User-facing label | Default for new users | Feature-gated |
| --- | --- | --- | --- |
| `discord` | Discord | off | yes |

## Stored payload

Each notification row stores a `content_key` (the type key) and JSON from `NotificationContent::toData()`. See `toData()` and `fromData()` on each PHP class for field names and types.
