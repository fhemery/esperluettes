# Domain-Driven Design Architecture

This document outlines the architecture and folder structure for the application, following Domain-Driven Design (DDD) principles.

More details can be found inside the different modules README.
- [Admin](../app/Domains/Admin/README.md)
- [Auth](../app/Domains/Auth/README.md)
- [Dashboard](../app/Domains/Dashboard/README.md)
- [Home](../app/Domains/Home/README.md)
- [News](../app/Domains/News/README.md)
- [Profile](../app/Domains/Profile/README.md)
- [StaticPage](../app/Domains/StaticPage/README.md)
- [Shared](../app/Domains/Shared/README.md)
- [StoryRef](../app/Domains/StoryRef/README.md)

To understand code organization, check [Domain Structure](./Domain_Structure.md)

**Important:**:  The Domains must have one-way dependency (we cannot have Auth -> Profile -> Auth)

This has two consequences :
- To avoid messing up accidentally dependency, we have setup a tool called [Deptrac](https://github.com/deptrac/deptrac).

- When we need to send a command in the wrong direction, we use **Event-Driven Architecture**. Check below for more details. 

## Event-Driven Architecture
TODO: FIX with another example !

Event-Driven Architecture is a way to send events so that other domains can react in consequence.

Let's take an example with the Profile URL

### The problem
- The profile URL is generated from the username. 
- The username is defined in Auth module
- But the profile URL is computed in Profile module.

Thus when user updates his/her name, we need to update the Profile URL.

Problem : the `UserAccountController` (in **Auth** module) cannot warn the **Profile** module, because **Profile relies heavily on Auth**, so we cannot have Auth import Profile.

### The solution: event driven architecture
Because the `UserAccountController` cannot warn the **Profile** module, it is going to send an event "in the wild".

```php
if ($request->user()->wasChanged('name')) {
  event(new UserNameUpdated(
      userId: $request->user()->id,
      oldName: (string) $originalName,
      newName: (string) $request->user()->name,
      changedAt: now(),
  ));
}
```

And we're done !

## Deptrac architectural rules

We follow a DDD layout under `app/Domains/` and enforce boundaries with Deptrac.
Current rules (see `deptrac.yaml`):

- Shared: foundational (no dependencies on other domains)
- Auth: may depend on Shared
- Profile: may depend on Shared and Auth
- Admin: may depend on Shared, Auth, and Profile

Run manually:

```
./vendor/bin/sail composer deptrac
# or if Sail is not used
composer deptrac
```
