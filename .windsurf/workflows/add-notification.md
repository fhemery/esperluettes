---
description: Create a new notification to be sent to users
auto_execution_mode: 1
---

1) Read the @app/Domains/Notification/README.md to get an overview of the Notification system.
2) Ensure the source user and the target users are identified, as well as the fields needed for the notification and the translation. When displaying, the notification should never be allowed to go fetch data in the database (it should however dynamically generate route urls for links)
3) Determine if notification must be written in the service performing the action (when the domain emitting the notification is the one performing the action), or in an event listener (when domain emitting the notification is not the same as domain performing the action)
4) Create Notification and register it by their `ServiceProvider`. Translation for notification display should be PHP namespaced, in French only, creating if needed a notification.php file. HTML is allowed (and recommended for links).
5) If action is outside of domain, create the Listener and register it to Service Provider. Else, implement the notification in service
6) Write test to ensure the notification is sent (using @app/Domains/Notification/Tests/helpers.php to check notification content. Never target Notification DB directly). Tests should either call the full action or publish the event that the listener listens to (using @app/Domains/Events/Tests/helpers.php methods, in particular `dispatchEvent` to emit the event)