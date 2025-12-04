<x-admin::layout>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <x-shared::title>{{ __('auth::admin.promotion.title') }}</x-shared::title>
        </div>

        <!-- Filters -->
        <form action="{{ route('auth.admin.promotion-requests.index') }}" method="GET" class="surface-read p-4 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <x-shared::input-label for="search">{{ __('auth::admin.promotion.filter.search') }}</x-shared::input-label>
                <x-shared::text-input
                    type="text"
                    id="search"
                    name="search"
                    class="mt-1 block w-full"
                    :value="$filters['search'] ?? ''"
                    placeholder="{{ __('auth::admin.promotion.filter.search_placeholder') }}"
                />
            </div>
            <div class="w-40">
                <x-shared::input-label for="status">{{ __('auth::admin.promotion.filter.status') }}</x-shared::input-label>
                <select name="status" id="status" class="mt-1 block w-full form-control">
                    <option value="pending" @selected(($filters['status'] ?? 'pending') === 'pending')>{{ __('auth::admin.promotion.status.pending') }}</option>
                    <option value="all" @selected(($filters['status'] ?? '') === 'all')>{{ __('auth::admin.promotion.filter.all') }}</option>
                    <option value="accepted" @selected(($filters['status'] ?? '') === 'accepted')>{{ __('auth::admin.promotion.status.accepted') }}</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>{{ __('auth::admin.promotion.status.rejected') }}</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <div>
                    <x-shared::button type="submit" color="primary">
                        {{ __('auth::admin.promotion.filter.apply') }}
                    </x-shared::button>
                </div>
                <a href="{{ route('auth.admin.promotion-requests.index') }}">
                    <x-shared::button type="button" color="neutral" :outline="true">
                        {{ __('auth::admin.promotion.filter.reset') }}
                    </x-shared::button>
                </a>
            </div>
        </form>

        <!-- Requests table -->
        <div class="surface-read text-on-surface p-4 overflow-x-auto">
            <table class="w-full text-sm admin">
                <thead>
                    <tr class="border-b border-border text-left">
                        <th class="p-3">{{ __('auth::admin.promotion.table.user') }}</th>
                        <th class="p-3">{{ __('auth::admin.promotion.table.requested_at') }}</th>
                        <th class="p-3">{{ __('auth::admin.promotion.table.waiting') }}</th>
                        <th class="p-3">{{ __('auth::admin.promotion.table.comments') }}</th>
                        <th class="p-3">{{ __('auth::admin.promotion.table.status') }}</th>
                        <th class="p-3">{{ __('auth::admin.promotion.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        @php
                            $profile = $profiles[$request->user_id] ?? null;
                            $waitingDays = (int) now()->diffInDays($request->requested_at);
                        @endphp
                        <tr class="border-b border-border/50 hover:bg-surface-read/50">
                            <td class="p-3">
                                @if ($profile)
                                    <a href="{{ route('profile.show', ['profile' => $profile->slug]) }}" 
                                       class="text-primary hover:underline font-medium">
                                        {{ $profile->display_name }}
                                    </a>
                                @else
                                    <span class="text-fg/50">{{ __('auth::admin.promotion.unknown_user') }}</span>
                                @endif
                            </td>
                            <td class="p-3">
                                {{ $request->requested_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-3">
                                @if ($request->isPending())
                                    <span class="{{ $waitingDays > 7 ? 'text-warning font-medium' : '' }}">
                                        {{ trans_choice('auth::admin.promotion.days_waiting', $waitingDays, ['count' => $waitingDays]) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                {{ $request->comment_count }}
                            </td>
                            <td class="p-3">
                                @if ($request->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-warning/20 text-warning rounded">
                                        {{ __('auth::admin.promotion.status.pending') }}
                                    </span>
                                @elseif ($request->status === 'accepted')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-success/20 text-success rounded">
                                        {{ __('auth::admin.promotion.status.accepted') }}
                                    </span>
                                @elseif ($request->status === 'rejected')
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-error/20 text-error rounded" 
                                          title="{{ $request->rejection_reason }}">
                                        {{ __('auth::admin.promotion.status.rejected') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($request->isPending())
                                    <div class="flex gap-2 items-center">
                                        <form action="{{ route('auth.admin.promotion-requests.accept', $request) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('{{ __('auth::admin.promotion.accept_confirm') }}')">
                                            @csrf
                                            <button type="submit" 
                                                    class="text-success hover:text-success/80"
                                                    title="{{ __('auth::admin.promotion.accept_button') }}">
                                                <span class="material-symbols-outlined">check_circle</span>
                                            </button>
                                        </form>

                                        <button type="button"
                                                class="text-error hover:text-error/80"
                                                title="{{ __('auth::admin.promotion.reject_button') }}"
                                                x-data
                                                @click="$dispatch('open-reject-modal', { requestId: {{ $request->id }} })">
                                            <span class="material-symbols-outlined">cancel</span>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-fg/30">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-fg/50">
                                {{ __('auth::admin.promotion.no_requests') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($requests->hasPages())
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <!-- Rejection Modal -->
    <div x-data="{ 
            open: false, 
            requestId: null,
            reason: ''
         }"
         @open-reject-modal.window="open = true; requestId = $event.detail.requestId; reason = ''"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">
        
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        
        <!-- Modal -->
        <div class="relative surface-read p-6 rounded-lg shadow-xl max-w-md w-full mx-4" @click.stop>
            <h3 class="text-lg font-semibold mb-4">{{ __('auth::admin.promotion.reject_title') }}</h3>
            
            <form :action="`/admin/auth/promotion-requests/${requestId}/reject`" method="POST">
                @csrf
                
                <div class="mb-4">
                    <x-shared::input-label for="rejection_reason">{{ __('auth::admin.promotion.reject_reason_label') }}</x-shared::input-label>
                    <textarea 
                        name="rejection_reason" 
                        id="rejection_reason"
                        x-model="reason"
                        class="mt-1 block w-full form-control"
                        rows="4"
                        required
                        placeholder="{{ __('auth::admin.promotion.reject_reason_placeholder') }}"
                    ></textarea>
                </div>
                
                <div class="flex justify-end gap-2">
                    <x-shared::button type="button" color="neutral" :outline="true" @click="open = false">
                        {{ __('auth::admin.promotion.cancel') }}
                    </x-shared::button>
                    <x-shared::button type="submit" color="error">
                        {{ __('auth::admin.promotion.reject_confirm') }}
                    </x-shared::button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layout>
