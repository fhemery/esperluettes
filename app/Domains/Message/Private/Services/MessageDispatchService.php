<?php

namespace App\Domains\Message\Private\Services;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Message\Private\Models\Message;
use App\Domains\Message\Private\Models\MessageDelivery;
use Illuminate\Support\Facades\DB;

class MessageDispatchService
{
    public function __construct(
        private UnreadCounterService $counterService,
        private AuthPublicApi $authApi,
    ) {}

    /**
     * Create and dispatch a message to target recipients.
     *
     * @param int $sentById The user ID sending the message
     * @param string $title Message title (max 150 chars)
     * @param string $content Message content (purified, max 1000 chars)
     * @param array $recipientIds Array of user IDs to receive the message
     * @param int|null $replyToId Optional parent message ID
     * @return Message The created message
     */
    public function dispatch(
        int $sentById,
        string $title,
        string $content,
        array $recipientIds,
        ?int $replyToId = null
    ): Message {
        return DB::transaction(function () use ($sentById, $title, $content, $recipientIds, $replyToId) {
            // Create the message
            $message = Message::create([
                'title' => $title,
                'content' => $content,
                'sent_by_id' => $sentById,
                'sent_at' => now(),
                'reply_to_id' => $replyToId,
            ]);

            // Create deliveries for all recipients (batch insert for performance)
            $deliveries = [];
            foreach (array_unique($recipientIds) as $userId) {
                $deliveries[] = [
                    'message_id' => $message->id,
                    'user_id' => $userId,
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($deliveries)) {
                MessageDelivery::insert($deliveries);
                
                // Invalidate unread count cache for all recipients
                foreach (array_unique($recipientIds) as $userId) {
                    $this->counterService->invalidateCache($userId);
                }
            }

            return $message;
        });
    }

    /**
     * Resolve recipient user IDs from various target criteria.
     *
     * @param array $userIds Explicit user IDs
     * @param array $roles Role slugs to target
     * @return array Array of unique user IDs
     */
    public function resolveRecipients(array $userIds = [], array $roles = []): array
    {
        $recipientIds = [];

        // Add explicit user IDs
        $recipientIds = array_merge($recipientIds, $userIds);

        // Add users by role
        if (!empty($roles)) {
            $roleUsers = $this->authApi->getUserIdsByRoles($roles);
            $recipientIds = array_merge($recipientIds, $roleUsers);
        }

        return array_unique(array_filter($recipientIds));
    }
}
