<?php

namespace App\Domains\Discord\Private\Controllers\Api;

use App\Domains\Discord\Private\Repositories\DiscordPendingNotificationRepository;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class NotificationsController extends BaseController
{
    public function __construct(
        private readonly DiscordPendingNotificationRepository $repository,
        private readonly NotificationPublicApi $notificationApi,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function pending(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('perPage', 100), 100);
        $page    = max((int) $request->query('page', 1), 1);

        $paginator = $this->repository->getPendingWithRecipients($perPage, $page);

        /** @var \App\Domains\Discord\Private\Models\DiscordPendingNotification[] $pendingItems */
        $pendingItems = $paginator->items();

        if (empty($pendingItems)) {
            return response()->json([
                'data'       => [],
                'pagination' => $this->paginationMeta($paginator),
            ]);
        }

        // Batch-fetch notification content
        $notificationIds = array_map(fn ($p) => (int) $p->notification_id, $pendingItems);
        $dtos            = $this->notificationApi->getNotificationsByIds($notificationIds);

        // Batch-fetch actor profiles for avatarUrl
        $sourceUserIds = [];
        foreach ($dtos as $dto) {
            if ($dto->sourceUserId !== null) {
                $sourceUserIds[] = $dto->sourceUserId;
            }
        }
        $profiles = !empty($sourceUserIds)
            ? $this->profileApi->getPublicProfiles(array_unique($sourceUserIds))
            : [];

        $data = [];
        foreach ($pendingItems as $pending) {
            $dto = $dtos[(int) $pending->notification_id] ?? null;
            if ($dto === null) {
                // Notification was deleted — skip; it will cascade-clean eventually
                continue;
            }

            $avatarUrl = null;
            if ($dto->sourceUserId !== null) {
                $profile   = $profiles[$dto->sourceUserId] ?? null;
                $avatarUrl = $profile?->avatar_url ?? null;
            }

            $recipients = $pending->recipients->pluck('discord_id')->values()->all();

            $data[] = [
                'id'          => $pending->id,
                'type'        => $dto->type,
                'data'        => [
                    'message' => strip_tags($dto->htmlDisplay),
                    'url'     => $dto->data['url'] ?? null,
                    'actor'   => $dto->data['actor'] ?? null,
                    'target'  => $dto->data['target'] ?? null,
                ],
                'avatarUrl'   => $avatarUrl,
                'defaultText' => $this->toDiscordMarkdown($dto->htmlDisplay),
                'recipients'  => $recipients,
                'createdAt'   => $pending->created_at->toIso8601String(),
            ];
        }

        return response()->json([
            'data'       => $data,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    public function markSent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notifications'                   => ['present', 'array'],
            'notifications.*.id'              => ['required', 'integer'],
            'notifications.*.failedRecipients' => ['sometimes', 'array'],
            'notifications.*.failedRecipients.*' => ['string'],
        ]);

        $markedCount = 0;
        foreach ($validated['notifications'] as $entry) {
            $id = (int) $entry['id'];
            if (array_key_exists('failedRecipients', $entry)) {
                $markedCount += $this->repository->markRecipientsDeliveredExcept(
                    $id,
                    $entry['failedRecipients']
                );
            } else {
                $markedCount += $this->repository->markAllRecipientsDelivered($id);
            }
        }

        return response()->json([
            'success'     => true,
            'markedCount' => $markedCount,
        ]);
    }

    /**
     * Convert website HTML to Discord markdown.
     * - <a href="url">text</a> → [text](url)
     * - <strong>/<b> → **text**
     * - <em>/<i> → *text*
     * - <br> → newline
     * - remaining tags stripped
     */
    private function toDiscordMarkdown(string $html): string
    {
        // Links: <a href="...">text</a> → [text](url)
        $result = preg_replace_callback(
            '/<a\s[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is',
            fn ($m) => '[' . strip_tags($m[2]) . '](' . $m[1] . ')',
            $html
        );

        // Bold
        $result = preg_replace('/<(strong|b)>(.*?)<\/(strong|b)>/is', '**$2**', $result);

        // Italic
        $result = preg_replace('/<(em|i)>(.*?)<\/(em|i)>/is', '*$2*', $result);

        // Line breaks
        $result = preg_replace('/<br\s*\/?>/i', "\n", $result);

        // Strip any remaining tags
        $result = strip_tags($result);

        return html_entity_decode(trim($result), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function paginationMeta(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'perPage'     => $paginator->perPage(),
            'total'       => $paginator->total(),
            'lastPage'    => $paginator->lastPage(),
            'hasMore'     => $paginator->hasMorePages(),
        ];
    }
}
