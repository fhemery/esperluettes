<?php

namespace App\Domains\Events\Contracts;

/**
 * Marker interface: when an EventDTO implements this, the EventBus will persist
 * additional request/user context (ip, user agent, url, user_id) alongside the payload.
 */
interface AuditableEvent
{
}
