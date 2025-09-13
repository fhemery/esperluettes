---
description: Create a new event and its consumers
auto_execution_mode: 1
---

1) Validate the event domain definition in @docs/Feature_Planning/EventsToImplement.md, and ensure the event name and event class name, the data, the consumers.
2) Create class under the emitting domain `Events/` folder. (using PHP namespaced translations for summary, in French only, creating if needed an events.php file)
3) Register with `EventBus::registerEvent()` in the domain’s `ServiceProvider::boot()`.
4) Emit from the relevant domain service once the operation succeeds.
5) Write tests to ensure event is emitted properly in all cases.
6) Create consumers and subscribe in their `ServiceProvider`.
7) Write tests to ensure consumer is called properly and processing all cases.
8) Mark as [I] inside @docs/Feature_Planning/EventsToImplement.md here once implemented.