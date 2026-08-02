@if($canView)
<div class="relative"
     data-quote-author-heat
     x-data="quoteAuthorHeat({
        markerLabelOne: {!! e(json_encode($markerLabels['one'])) !!},
        markerLabelOther: {!! e(json_encode($markerLabels['other'])) !!},
        tintLabelOne: {!! e(json_encode($tintLabels['one'])) !!},
        tintLabelOther: {!! e(json_encode($tintLabels['other'])) !!}
     })"
     @quote:focus-passage.window="$nextTick(() => focusGroup($event.detail.groupKey))">

    {{-- Below md the gutter is display:none, so no marker is ever built. --}}
    <div x-ref="gutter"
         class="hidden md:block absolute inset-y-0 right-0 w-6 translate-x-full pointer-events-none"></div>

    {{ $slot }}

    <x-quote::author-passage-panel />
</div>

@once
    @push('head-scripts')
        @vite('app/Domains/Quote/Resources/js/quote/index.js')
    @endpush
@endonce
@else
{{ $slot }}
@endif
