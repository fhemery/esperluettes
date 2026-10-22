{{-- The viewer fragment (loaded on click) carries report/delete/moderation controls according to the viewer's rights. --}}
<section data-chapter-images class="flex flex-col gap-2 surface-read text-on-surface p-2 sm:p-8"
         aria-label="{{ __('fanart::ui.chapter.images') }}">
    <x-shared::title tag="h2">{{ __('fanart::ui.chapter.images') }}</x-shared::title>
    @include('fanart::fragments.group', ['fanarts' => $fanarts])
    <x-fanart::viewer-modal />
</section>
