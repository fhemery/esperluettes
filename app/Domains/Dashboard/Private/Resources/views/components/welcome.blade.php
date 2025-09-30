@props(['error' => null])

@if($error)
    <div class="surface-error text-on-surface p-4 w-full">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm">{{ $error }}</p>
            </div>
        </div>
    </div>
@else
    <div class="flex flex-col gap-4 surface-read text-on-surface p-6 w-full">
        <h2 class="text-xl font-semibold text-center">
            {{ __('dashboard::welcome.welcome_message') }} <span class="text-accent">{{$displayName}}</span>
        </h2>

        <div class="space-y-2 text-md text-center">
            <p>
                {{ __('dashboard::welcome.member_since') }}
                <span class="text-accent font-semibold" x-data x-text="DateUtils.formatDate(new Date('{{ $joinDate }}'))"></span>.
                {{ __('dashboard::welcome.role_label') }}
                <span class="text-accent font-semibold">{{ $roleLabel }}</span>
            </p>

            <p>
            {!! __('dashboard::welcome.activity_summary', [
                'stories' => trans_choice(
                    'dashboard::welcome.stories_count',
                    $storiesCount,
                    ['count' => '<span class="text-accent font-semibold">'.$storiesCount.'</span>']
                ),
                'comments' => trans_choice(
                    'dashboard::welcome.comments_count',
                    $commentsCount,
                    ['count' => '<span class="text-accent font-semibold">'.$commentsCount.'</span>']
                ),
            ]) !!}
            </p>
        </div>
    </div>
@endif
