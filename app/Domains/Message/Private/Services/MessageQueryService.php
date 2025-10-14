<?php

declare(strict_types=1);

namespace App\Domains\Message\Private\Services;

use App\Domains\Message\Private\Models\MessageDelivery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageQueryService
{
    /**
     * Get paginated message deliveries for a user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return MessageDelivery::query()
            ->forUser($userId)
            ->with('message')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a specific delivery for a user.
     * Returns null if not found or doesn't belong to user.
     *
     * @param int $deliveryId
     * @param int $userId
     * @return MessageDelivery|null
     */
    public function findDeliveryForUser(int $deliveryId, int $userId): ?MessageDelivery
    {
        return MessageDelivery::query()
            ->where('id', $deliveryId)
            ->forUser($userId)
            ->with('message')
            ->first();
    }

    /**
     * Delete a delivery for a user.
     * Returns false if not found or doesn't belong to user.
     *
     * @param int $deliveryId
     * @param int $userId
     * @return bool
     */
    public function deleteDeliveryForUser(int $deliveryId, int $userId): bool
    {
        $delivery = $this->findDeliveryForUser($deliveryId, $userId);
        
        if (!$delivery) {
            return false;
        }

        $delivery->delete();
        return true;
    }
}
