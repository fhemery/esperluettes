<?php

namespace App\Domains\Events\Private\Controllers\Admin;

use App\Domains\Events\Private\Models\StoredDomainEvent;
use App\Domains\Events\Private\Services\DomainEventFactory;
use App\Domains\Shared\Contracts\ProfilePublicApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DomainEventController extends Controller
{
    public function __construct(
        private readonly DomainEventFactory $factory,
        private readonly ProfilePublicApi $profileApi,
    ) {}

    public function index(Request $request): View
    {
        $nameFilter = $request->get('name_filter');
        $userId = $request->get('user_id');
        $occurredAfter = $request->get('occurred_after');
        $occurredBefore = $request->get('occurred_before');

        $query = StoredDomainEvent::query()->orderBy('occurred_at', 'desc');

        if ($nameFilter) {
            $query->where('name', 'like', '%' . $nameFilter . '%');
        }

        if ($userId) {
            $query->where('triggered_by_user_id', (int) $userId);
        }

        if ($occurredAfter) {
            $query->where('occurred_at', '>=', $occurredAfter);
        }

        if ($occurredBefore) {
            $query->where('occurred_at', '<=', $occurredBefore);
        }

        $events = $query->paginate(20)->withQueryString();

        $profileIds = $events->pluck('triggered_by_user_id')->filter()->unique();
        if ($userId) {
            $profileIds->push((int) $userId);
        }
        $profiles = $this->profileApi->getPublicProfiles($profileIds->values()->all());
        $filterUserDisplayName = $userId ? ($profiles[(int) $userId]?->display_name ?? null) : null;

        $summaries = [];
        foreach ($events as $event) {
            try {
                $domainEvent = $this->factory->make($event->name, $event->payload ?? []);
                $summaries[$event->id] = $domainEvent?->summary();
            } catch (\Throwable) {
                $summaries[$event->id] = null;
            }
        }

        return view('events::pages.admin.domain-events.index', compact(
            'events', 'profiles', 'summaries',
            'nameFilter', 'userId', 'filterUserDisplayName', 'occurredAfter', 'occurredBefore'
        ));
    }

    public function show(StoredDomainEvent $domainEvent): View
    {
        $profile = null;
        if ($domainEvent->triggered_by_user_id) {
            $profile = $this->profileApi->getPublicProfile($domainEvent->triggered_by_user_id);
        }

        $summary = null;
        try {
            $event = $this->factory->make($domainEvent->name, $domainEvent->payload ?? []);
            $summary = $event?->summary();
        } catch (\Throwable) {
        }

        return view('events::pages.admin.domain-events.show', compact('domainEvent', 'profile', 'summary'));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:events_domain,id'],
        ]);

        StoredDomainEvent::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('events.admin.domain-events.index')
            ->with('success', __('events::admin.domain_events.bulk_deleted', ['count' => count($validated['ids'])]));
    }

    public function destroy(StoredDomainEvent $domainEvent): RedirectResponse
    {
        $domainEvent->delete();

        return redirect()->route('events.admin.domain-events.index')
            ->with('success', __('events::admin.domain_events.deleted'));
    }
}
