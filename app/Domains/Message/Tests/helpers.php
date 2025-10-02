<?php

use App\Domains\Message\Private\Models\Message;
use App\Domains\Message\Private\Models\MessageDelivery;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\TestCase;

/**
 * Send a message through the HTTP controller endpoint.
 * Returns the created Message model.
 */
function sendMessage(
    TestCase $t,
    Authenticatable $sender,
    string $title,
    string $content,
    array $recipientUserIds = [],
    array $recipientRoles = [],
    bool $everyone = false
): Message {
    $t->actingAs($sender);
    
    $payload = [
        'title' => $title,
        'content' => $content,
        'target_users' => $recipientUserIds,
        'target_roles' => $recipientRoles,
        'target_everyone' => $everyone,
    ];
    
    $t->post('/messages', $payload)->assertRedirect();
    
    return Message::query()->latest('id')->firstOrFail();
}

/**
 * Send a message to specific users.
 */
function sendMessageToUsers(TestCase $t, Authenticatable $sender, string $title, string $content, array $recipientUserIds): Message
{
    return sendMessage($t, $sender, $title, $content, recipientUserIds: $recipientUserIds);
}

/**
 * Send a message to users with specific roles.
 */
function sendMessageToRoles(TestCase $t, Authenticatable $sender, string $title, string $content, array $roles): Message
{
    return sendMessage($t, $sender, $title, $content, recipientRoles: $roles);
}

/**
 * Send a broadcast message to everyone.
 */
function sendMessageToEveryone(TestCase $t, Authenticatable $sender, string $title, string $content): Message
{
    return sendMessage($t, $sender, $title, $content, everyone: true);
}

/**
 * Get delivery for a specific user and message.
 */
function getDeliveryForUser(int $messageId, int $userId): ?MessageDelivery
{
    return MessageDelivery::where('message_id', $messageId)
        ->where('user_id', $userId)
        ->first();
}
