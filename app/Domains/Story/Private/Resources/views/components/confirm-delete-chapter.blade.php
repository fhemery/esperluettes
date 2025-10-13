@props([
    'name',
    'storySlug',
    'chapterSlug',
    'chapterTitle',
    'confirm' => __('story::show.confirm_delete'),
    'cancel' => __('story::show.cancel'),
])

<x-shared::confirm-modal
    :name="$name"
    :title="__('story::chapters.actions.delete')"
    :cancel="$cancel"
    :confirm="$confirm"
    :action="route('chapters.destroy', ['storySlug' => $storySlug, 'chapterSlug' => $chapterSlug])"
    method="DELETE"
    maxWidth="md">
    <div class="flex flex-col gap-4">
        <p>{{ __('story::chapters.delete.confirm_warning', ['chapterTitle' => $chapterTitle]) }}</p>
        <p class="text-sm">{{ __('story::chapters.delete.no_refund') }}</p>
    </div>
</x-shared::confirm-modal>
