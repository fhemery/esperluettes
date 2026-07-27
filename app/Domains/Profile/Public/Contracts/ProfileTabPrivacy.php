<?php

namespace App\Domains\Profile\Public\Contracts;

/**
 * Declares that a tab's visibility is driven by a user setting, so Profile can
 * show the owner whether the tab is exposed to others and link to the setting.
 *
 * Purely informative: the visibility decision itself stays in the tab's
 * ProfileTabVisibility implementation. The stored value is read as a boolean
 * where **true means hidden**, which is the convention shared by every profile
 * privacy setting (hide-comments-section, hide-following-tab, hide-quotes-tab).
 */
final class ProfileTabPrivacy
{
    public function __construct(
        public readonly string $settingsTabId,
        public readonly string $settingsKey,
    ) {
    }
}
