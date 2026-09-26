<?php

namespace App\Domains\Notification\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Dto\RoleDto;
use App\Domains\Notification\Private\Exceptions\NotificationTypeNotVisibleException;
use App\Domains\Notification\Private\Repositories\NotificationPreferencesRepository;
use App\Domains\Notification\Public\Contracts\NotificationTypeDefinition;
use App\Domains\Notification\Public\Services\NotificationChannelRegistry;
use App\Domains\Notification\Public\Services\NotificationFactory;

class NotificationPreferencesService
{
    public const WEBSITE_CHANNEL = 'website';
    public const WEBSITE_DEFAULT_ENABLED = true;

    public function __construct(
        private NotificationPreferencesRepository $repository,
        private NotificationFactory $factory,
        private NotificationChannelRegistry $channelRegistry,
        private AuthPublicApi $authApi,
    ) {}

    /**
     * Returns all notification preferences for a user.
     *
     * Only non-hidden types visible to the user's current roles are included.
     * Groups with no visible types are skipped.
     * The website channel is always present. External channels are the currently active ones.
     *
     * Format: [type => [channelId => ['enabled' => bool, 'isDefault' => bool, 'forced' => bool]]]
     */
    public function getPreferencesForUser(int $userId): array
    {
        $storedPrefs = $this->repository->getForUser($userId);
        $channels    = $this->channelRegistry->getActiveChannels();
        $roleSlugs   = $this->roleSlugsForUser($userId);
        $result      = [];

        foreach ($this->factory->getGroups() as $group) {
            foreach ($this->factory->getTypesForGroup($group->id) as $typeDef) {
                if (!$typeDef->isVisibleTo($roleSlugs)) {
                    continue;
                }

                $typeResult = [];

                // Website channel
                $forced  = $typeDef->forcedOnWebsite;
                $stored  = $storedPrefs->get($typeDef->type . '##' . self::WEBSITE_CHANNEL);
                $enabled = $forced ? true : (bool) ($stored?->enabled ?? self::WEBSITE_DEFAULT_ENABLED);
                $typeResult[self::WEBSITE_CHANNEL] = [
                    'enabled'   => $enabled,
                    'isDefault' => $forced || $stored === null,
                    'forced'    => $forced,
                ];

                // External channels
                foreach ($channels as $channel) {
                    $stored  = $storedPrefs->get($typeDef->type . '##' . $channel->id);
                    $enabled = (bool) ($stored?->enabled ?? $channel->defaultEnabled);
                    $typeResult[$channel->id] = [
                        'enabled'   => $enabled,
                        'isDefault' => $stored === null,
                        'forced'    => false,
                    ];
                }

                $result[$typeDef->type] = $typeResult;
            }
        }

        return $result;
    }

    /**
     * Set a single preference.
     * Applies sparse storage: if the value matches the channel default, the row is deleted.
     * Does NOT enforce forcedOnWebsite — that is the controller's responsibility.
     *
     * @throws NotificationTypeNotVisibleException when the type exists but is invisible to the user
     */
    public function set(int $userId, string $type, string $channel, bool $enabled): void
    {
        $typeDef = $this->factory->getTypeDefinition($type);
        if ($typeDef !== null && !$this->isTypeVisibleToUser($typeDef, $userId)) {
            throw new NotificationTypeNotVisibleException(
                "Notification type '{$type}' is not visible to user {$userId}.",
            );
        }

        if ($enabled === $this->defaultEnabled($channel)) {
            $this->repository->deletePreference($userId, $type, $channel);
        } else {
            $this->repository->setPreference($userId, $type, $channel, $enabled);
        }
    }

    /**
     * Bulk-set all non-hidden types for a channel.
     * Skips forcedOnWebsite types when channel is 'website'.
     * Skips types invisible to the user.
     */
    public function setAll(int $userId, string $channel, bool $enabled): void
    {
        $roleSlugs = $this->roleSlugsForUser($userId);
        $types = [];
        foreach ($this->factory->getGroups() as $group) {
            foreach ($this->factory->getTypesForGroup($group->id) as $typeDef) {
                if (!$typeDef->isVisibleTo($roleSlugs)) {
                    continue;
                }
                if ($channel === self::WEBSITE_CHANNEL && $typeDef->forcedOnWebsite) {
                    continue;
                }
                $types[] = $typeDef->type;
            }
        }
        $this->applyBulk($userId, $channel, $enabled, $types);
    }

    /**
     * Bulk-set all non-hidden types in a group for a channel.
     * Skips forcedOnWebsite types when channel is 'website'.
     * Skips types invisible to the user.
     */
    public function setGroup(int $userId, string $groupId, string $channel, bool $enabled): void
    {
        $roleSlugs = $this->roleSlugsForUser($userId);
        $types = [];
        foreach ($this->factory->getTypesForGroup($groupId) as $typeDef) {
            if (!$typeDef->isVisibleTo($roleSlugs)) {
                continue;
            }
            if ($channel === self::WEBSITE_CHANNEL && $typeDef->forcedOnWebsite) {
                continue;
            }
            $types[] = $typeDef->type;
        }
        $this->applyBulk($userId, $channel, $enabled, $types);
    }

    public function defaultEnabled(string $channel): bool
    {
        if ($channel === self::WEBSITE_CHANNEL) {
            return self::WEBSITE_DEFAULT_ENABLED;
        }
        return $this->channelRegistry->get($channel)?->defaultEnabled ?? false;
    }

    private function applyBulk(int $userId, string $channel, bool $enabled, array $types): void
    {
        if (empty($types)) {
            return;
        }

        if ($enabled === $this->defaultEnabled($channel)) {
            $this->repository->deleteForUserAndChannel($userId, $channel, $types);
        } else {
            $this->repository->setForUserAndChannel($userId, $channel, $enabled, $types);
        }
    }

    /**
     * @return list<string>
     */
    private function roleSlugsForUser(int $userId): array
    {
        $rolesByUser = $this->authApi->getRolesByUserIds([$userId]);

        return array_map(
            fn (RoleDto $role): string => $role->slug,
            $rolesByUser[$userId] ?? [],
        );
    }

    private function isTypeVisibleToUser(NotificationTypeDefinition $typeDef, int $userId): bool
    {
        return $typeDef->isVisibleTo($this->roleSlugsForUser($userId));
    }
}
