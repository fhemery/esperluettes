<?php

namespace App\Domains\Profile\Public\Contracts;

/**
 * Decides whether a profile tab is visible to a given viewer.
 *
 * Implementations are resolved from the container, so they may inject their own
 * domain services. Access is binary: a tab the viewer cannot access is absent
 * from the tab strip and unreachable by URL. A tab the viewer *can* access but
 * that happens to have no content is still shown — emptiness is the component's
 * business, not the registry's, so visibility must never run counting queries.
 */
interface ProfileTabVisibility
{
    /**
     * @param  int  $ownerUserId  The user whose profile is being displayed.
     * @param  int|null  $viewerId  The viewer, or null when the viewer is a guest.
     */
    public function isVisible(int $ownerUserId, ?int $viewerId): bool;
}
