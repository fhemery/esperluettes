<?php

namespace App\Domains\Profile\Public\Contracts;

use App\Domains\Profile\Public\Visibility\AlwaysVisible;

/**
 * A tab on the public profile page, registered by the domain that owns its
 * content. Profile owns rendering and routing; the registering domain owns the
 * label, the visibility rule and the component.
 */
final class ProfileTabDefinition
{
    /**
     * @param  string  $key  URL segment and tab identifier, e.g. 'quotes'.
     * @param  int  $order  Position in the tab strip; 10-point spacing by convention.
     * @param  string  $component  Blade class component, e.g. 'quote::profile-tab'.
     *                             It receives exactly two props: ownerUserId and isOwn.
     * @param  string  $labelKey  Translation key used when viewing someone else's profile.
     * @param  string|null  $ownLabelKey  Translation key used on your own profile;
     *                                    falls back to $labelKey when null.
     * @param  string|null  $icon  Material Symbols icon name.
     * @param  string  $visibility  FQCN implementing ProfileTabVisibility.
     * @param  ProfileTabPrivacy|null  $privacy  Owner-facing visibility indicator.
     * @param  bool  $isDefault  Landing tab of the profile page; exactly one tab may set it.
     */
    public function __construct(
        public readonly string $key,
        public readonly int $order,
        public readonly string $component,
        public readonly string $labelKey,
        public readonly ?string $ownLabelKey = null,
        public readonly ?string $icon = null,
        public readonly string $visibility = AlwaysVisible::class,
        public readonly ?ProfileTabPrivacy $privacy = null,
        public readonly bool $isDefault = false,
    ) {
    }

    /**
     * Translation key to use for a given viewer.
     */
    public function labelKeyFor(bool $isOwn): string
    {
        return $isOwn ? ($this->ownLabelKey ?? $this->labelKey) : $this->labelKey;
    }
}
