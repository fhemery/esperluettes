# StoryRef domain

This domain contains all referential data related to stories (curated lists used by the Story domain).

Examples: genres, types, statuses, audiences, trigger warnings, copyrights, feedback options.

# Architecture
* Low-churn reference tables with `is_active` flags and optional ordering.
* Exposed via services and used by admin resources; Story domain depends on StoryRef.