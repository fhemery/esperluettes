# Quote contest — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Now that we have define the quote system, the users have access to a list of quotes they made on the different chapters. We would like to create a new activity in the calendar that enables to shortlist some quotes in some categories, for which the users will later enable to vote.

## Why

This is a nice activity to share the best quotes and push people to read more. They can also discover stories they might like and add them later to their Readlist.

## Constraints or ideas I already have
- The contest is only opened to Confirmed users
- Before event start, the admin should be able to setup a description for the event and categories into which users will be able to submit quotes. A category is composed of a title  and a small description.
- Admin also configure an "end of submission date", a "vote start date". The "end date" will represent the vote end date.
  Ideally, the form displays "start of submission date" field as well, greyed, readonly version of the activity start date, and "end of vote date" field, greyed, readonly version of the activity end date
- When event starts, users have the ability to submit quotes from their quote list. The quotes must:
	- Not be private (Public or community)
	- Not belong to a story that is excluded from events
- User must select the quote and assign it to a category. User can only submit one quote per category: if they try to submit another one, system should tell them which ones they already submitted and propose to replace.
- User can submit the same quote on two categories
- Ideally, if the quote is submitted in a category, it should be mentioned in the quote list.
- IMPORTANT: the quote list mentioned here, from where user does submit, is not the profile quote list. It must be a dedicated list belonging to the activity.
- End of submission should be displayed on the quote list, as well, if possible, as the list of categories (with their description and the possible submitted quote in popover)
- Submissions are closed once end of submission is reached. The tab is still reachable, but readonly.
- During voting date, all users see all the categories, and whether they already voted for them or not. When they select a category, they have access to all the quotes. Users can cast one vote per category. Users can change their vote until voting period ends.
- Quotes must always display the story title and link, the chapter title and link, the authors names. Reuse of the quote component from the quote module is allowed.
- Admins and moderators have permanent access to one additional tab, that enables them to see, for each category, all the quotes that are present and how many votes there are. They can also delete a quote (for example to eliminate duplicates).
- The admin tab might be the same one a the vote tab if it eases development, but two tabs are fine (I actually prefer two tabs).
- When event ends, the vote screen is readonly, but results are not displayed to users. Admin and moderators can still access the result screen, so that they can announce the winners in a news. Admin must be able to see who quoted.

## Explicitly out of scope

<what you already know you do not want>
