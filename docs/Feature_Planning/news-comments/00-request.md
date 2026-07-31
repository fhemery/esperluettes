# News comments — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want
I want any logged user (user and confirmed user, with the exception of users that are not compliant) to be able to add comments in news.
<the ask, in your own words>

## Why

Users might want to react to a news, asking questions or just cheering for the work that has been done. 

## Constraints or ideas I already have
Functional constraints:
- Comments are threadable
- Root Comments do not create notifications, only reply to comments. This is a new kind of notification, that should be added to the settings as well
- Comments follow the same convention as chapter comments regarding the editor toolbar
- Comments minimum length is 20 characters.
- The author of the news can post comments as well
- Anyone can edit their own comments
- Comments can be reported like for chapters, and moderated the same ways. Ideally, the moderation reasons should be different, but this might not be doable with the current system.
Technical constraints:
-  News should be responsible of declaring the comments configuration, registering notifications, etc.... Comments should be touched only to add generic features that could be reused by chapter comments.

## Explicitly out of scope

<what you already know you do not want>
