<div class="surface-read text-on-surface p-4 rounded shadow" data-activity-component="jardino">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-accent">local_florist</span>
        <h3 class="font-semibold">{{ __('jardino::details.title') }}</h3>
    </div>
    <div class="text-sm text-fg/80 mt-2">
        <p>
            {{ __('jardino::details.description') }}. 
            <a href="{{ route('static.show', 'jardino') }}" class="text-accent hover:underline">{{ __('jardino::details.read_more') }}</a>
        </p>

        @isset($vm)
            @if ($vm->objective === null)
                <x-jardino::set-objective :stories="$vm->stories" />
            @endif
        @endisset
    </div>
</div>
