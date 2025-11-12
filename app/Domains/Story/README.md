# Story Module

This is the main and core module of the website. It handles :
- Story creation, update, deletion
- Chapter creation, update, deletion
- Chapter credit management (whether user can write new chapters or not)

A huge effort has been made to make this module as thin as possible, in order to avoid module growing too big and complex. In particular :
- [StoryRef](../StoryRef/README.md) handles all the reference data for the stories
- [Readlist](../ReadList/README.md) handles all the readlist data
- [Comments](../Comment/README.md) handles all the comments data
- [Calendar](../Calendar/README.md) handles all the activities around stories

This module still needs to plug onto comments, Notifications, Moderation modules.