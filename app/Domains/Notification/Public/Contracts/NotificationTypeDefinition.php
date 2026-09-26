<?php

namespace App\Domains\Notification\Public\Contracts;

final class NotificationTypeDefinition
{
    public function __construct(
        public readonly string $type,
        /** @var class-string<NotificationContent> */
        public readonly string $class,
        public readonly string $groupId,
        public readonly string $nameKey,
        public readonly bool $forcedOnWebsite = false,
        public readonly bool $hideInSettings = false,
        /** @var list<string>|null */
        public readonly ?array $visibleToRoles = null,
    ) {}

    /**
     * True when the type has no audience restriction, or the viewer holds at least one listed role.
     *
     * @param list<string> $roleSlugs
     */
    public function isVisibleTo(array $roleSlugs): bool
    {
        if ($this->visibleToRoles === null || $this->visibleToRoles === []) {
            return true;
        }

        foreach ($this->visibleToRoles as $role) {
            if (in_array($role, $roleSlugs, true)) {
                return true;
            }
        }

        return false;
    }
}
