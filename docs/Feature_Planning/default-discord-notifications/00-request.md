# Default Discord notifications setting — request

Add a setting that enables new Discord notifications by default. When this option is checked, any new notification is sent to Discord as well, not only into the website notifications.

## What I want

A new user setting that, when enabled, causes all new notifications to be automatically forwarded to Discord in addition to the website notifications. This provides users with a convenient way to receive notifications across both channels without manual intervention.

## Why

The original system works with Discord notifications off by default, which means users must opt-in individually. This creates friction for users who want their Discord and website notifications synchronized. By providing a default-enable setting, we improve the onboarding experience for users who prefer consolidated Discord notifications.

## Constraints or ideas I already have

The challenge is that the current system architecture requires notifying Discord when new notification types are added or when users enable this setting retroactively. We need to ensure:
- Existing users who activate this setting get proper notification propagation
- New notification types added after the setting exists are handled correctly
- The system gracefully handles any new notifications in the pipeline

## Explicitly out of scope

- Changing the default behavior across all users (this is a per-user opt-in setting)
- Modifying the core Discord bot integration architecture
- Retroactively sending historical notifications to Discord
