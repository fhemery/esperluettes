# New fanart / artwork module — request

*Written by the user. Free form, may be three lines. Everything below is optional prompting, not a form to fill.*

## What I want

The idea is to add a feature enabling both the author and the users to contribute images and sounds to stories.

## Why

There are two reasons here:
- people have been regularly contributing fanart drawings on the discord, and putting it next to the text would be awesome
- there also has been some projects of audio recording of the books, and people are willing to do it.

## Constraints or ideas I already have
Fanart and artwork are the same idea, the difference being artwork is posted by the authors. From now on for simplicity sake I'll use fanart.

- Fanart feature is reserved to confirmed users
- Each author can decide whether they accept fanarts or not. There are three modes: yes, no, and only to designated fanartists. There are two new settings: one for audio contributions, on for image contributions. This should ideally go in the Story tab. Default is on for images, only to designated fanartists on audio
- In each story, next to beta reader and author, user can grant a new "Fanartiste" role to confirmed members. The role grants access to the ability to contribute fanarts if the user has selected "only to designated fanartists" 
- Fanart can be submitted to both public or communities stories, but are only visible to confirmed users
- Fanart or artwork cannot be added to private stories, because they would not be visible and thus hard to moderate.
- If user has access to fanarts submission, they get a button in the story details, next to Chapters list, that says "Soumettre un fanart". If user is the author, they instead have "Ajouter un artwork".
- The button opens a dedicated page with two tabs, one for image contributions, one for audio contributions.
- for image, fanartist can upload a drawing, optionally select a chapter, give a title and an optional description to the drawing. Usual limitations about weight apply (~2MB if I remember well). It also has a checkbox stipulating the image is not image generated and fanartist owns the full rights over it.
- the image is not yet available once the fanartist uploaded. A notification is sent to the authors so that they know a drawing is pending. 
- the list of fanarts is accessible to both authors and readers as a separate tab to the chapter list. They are grouped by chapter, hidden in collapsible areas for the chapters the reader has not read with a warning saying this might spoil them. Another collapsible area contains the elements not assigned to any chapter.
- the miniatures can be expanded, in which case the drawing is showed in full width or height (whatever limits first), with the title and the description 
- for the authors, on tops are the images pending validation, visible only by the authors. The miniature is shown, the author can expand, view title, and accept of decline the fanart. upon clicking on the button, a pop-up requires a comment.
- if declined, the image is simply removed. if accepted, the image is visible to all confirmed users in the chapter (or the "toute l'oeuvre" section if no chapter was assigned).
- in any case, the fanartist receives a notification with the value of the comment left by the author in it.
- from this screen, the authors can also delete any fanart anytime. This sends a notification to the fanartist.
- fanarts submitted by fanartists cost nothing
- artwork (fanart submitted by the author) cost one chapter credit. A warning should explain this to the author. If author does not have the credit, fanart cannot be uploaded. of course, no approval is requested in this case.
- author may update its own artwork, including title and description, with no extra cost. This is useful for example for updating a map at no new cost.
- the image fanarts block is additionally displayed at the bottom of a chapter page for the corresponding chapter (if there is any fanart). It is the same block as the chapter section of the previously described page, with miniatures. No action can be performed from this block except viewing full height

- audio deposit works the same, except chapter is mandatory and the cap for audio is higher (let's say 15MB). There can be only one audio per chapter, so accepting an audio on a chapter that already has one overrides it.
- to show that an audio is present on the chapter, the chapter list gains a new "play" icon that launches the audio in streaming. Ideally, reaching the end of a chapter audio marks it as read and moves to the next if any.
- audio is also visible as a widget on the chapter page, below the chapter title. Clicking on it launches again in streaming.

- Now, although these pages create already a complete ecosystem, this is not enough, because the fanartist cannot view or delete their work. As a consequence, the fanartist has access to a new tab on their profile, with two sections : image and sound.
- the tab is private by default but can be opened to community via a settings (probably in profile section of the settings).
- For each section, system should list, by story, by chapter, the fanarts that have been released by the artist. Lazy-loading and collapsible panels are the norm to avoid spoilers once again.
- The behaviour for displaying is the same for images as the chapter list on the story page, the only difference being the fanartist can now delete their fanart.
For sounds, I guess we want the same display as for images, but with a player widget enabling to stream the sound.

- If a fanartist removes his account of gets blocked, it's fanarts must be destroyed or soft deleted. 
- If an author gets blocked, fanarts are to be softdeleted
-  If an author deletes the story, the chapter or their account, the fanart is removed and fanartist should be notified
- If the author changes the story to private, fanarts are still accessible, but they can no longer be uploaded. This is to prevent uploading illegal content

- People should be able to report a fanart, and this represents a new category. Reporting fanart can be done from the story page, with a button next to each fanart, and next to the media player. Separate reasons for fanart moderations are expected.
- System should save a copy of the fanart for review by moderators. Upon approval, the fanart should be deleted and the authors and fanartist notified.


From an architectural point of view, the fanart is big and interloped with Story, but I highly advise to create a separate module. I am ready to make a few compromises to increase the isolation if it helps.

From an implementation standpoint, this is a huge epic and is probably best split into small tasks.

## Explicitly out of scope

<!-- Not provided yet. -->
