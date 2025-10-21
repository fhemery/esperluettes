<?php

declare(strict_types=1);

namespace App\Domains\Message\Private\Controllers;

use App\Domains\Auth\Public\Api\AuthPublicApi;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Message\Private\Models\MessageDelivery;
use App\Domains\Message\Private\Requests\ComposeMessageRequest;
use App\Domains\Message\Private\Requests\DeleteMessageRequest;
use App\Domains\Message\Private\Services\MessageDispatchService;
use App\Domains\Message\Private\Services\MessageQueryService;
use App\Domains\Message\Private\Services\UnreadCounterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Mews\Purifier\Facades\Purifier;

class MessageController extends Controller
{
    public function __construct(
        private UnreadCounterService $unreadCounter,
        private MessageDispatchService $dispatcher,
        private MessageQueryService $queryService,
        private AuthPublicApi $authApi
    ) {
    }

    /**
     * Display the list of messages for the authenticated user.
     */
    public function index(Request $request): View
    {
        $deliveries = $this->queryService->listForUser($request->user()->id);

        return view('message::pages.index', [
            'deliveries' => $deliveries,
            'selectedDelivery' => null,
            'canCompose' => $this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]),
        ]);
    }

    /**
     * Display a specific message and mark it as read.
     */
    public function show(Request $request, MessageDelivery $delivery): View
    {
        // Authorization: ensure delivery belongs to current user
        if ($delivery->user_id !== $request->user()->id) {
            abort(403);
        }

        // Mark as read if not already
        if (!$delivery->is_read) {
            $delivery->markAsRead();
            // Invalidate cache
            $this->unreadCounter->invalidateCache($request->user()->id);
        }

        // Get all deliveries for sidebar
        $deliveries = $this->queryService->listForUser($request->user()->id);

        return view('message::pages.index', [
            'deliveries' => $deliveries,
            'selectedDelivery' => $delivery,
            'canCompose' => $this->authApi->hasAnyRole([Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR]),
        ]);
    }

    /**
     * Delete a message delivery (only for the current user).
     */
    public function destroy(DeleteMessageRequest $request, MessageDelivery $delivery): RedirectResponse
    {
        // Authorization: ensure delivery belongs to current user
        if ($delivery->user_id !== $request->user()->id) {
            abort(403);
        }

        $delivery->delete();

        // Invalidate cache
        $this->unreadCounter->invalidateCache($request->user()->id);

        return redirect()->route('messages.index')
            ->with('status', __('message::messages.deleted'));
    }

    /**
     * Show the compose message form (admins only in v1).
     */
    public function compose(Request $request): View
    {       
        return view('message::pages.compose');
    }

    /**
     * Store a new composed message and dispatch to recipients.
     */
    public function store(ComposeMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Purify content with strict profile
        $content = Purifier::clean($data['content'], 'strict');

        // Resolve recipients
        $recipientIds = $this->dispatcher->resolveRecipients(
            userIds: $data['target_users'] ?? [],
            roles: $data['target_roles'] ?? [],
        );

        if (empty($recipientIds)) {
            return back()->withErrors(['recipients' => __('message::messages.no_recipients')]);
        }

        // Dispatch message
        $this->dispatcher->dispatch(
            sentById: $request->user()->id,
            title: $data['title'],
            content: $content,
            recipientIds: $recipientIds
        );

        return redirect()->route('messages.index')
            ->with('status', __('message::messages.sent'));
    }
}
