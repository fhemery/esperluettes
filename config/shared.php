<?php

return [
    // Enable/disable domain event auditing globally
    'event_auditing_enabled' => env('EVENT_AUDITING_ENABLED', true),

    // Retention days for pruning old events
    'event_auditing_retention_days' => env('EVENT_AUDITING_RETENTION_DAYS', 90),
];
