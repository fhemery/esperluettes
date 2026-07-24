{{--
    Media Image Field — editable single-image control.

    Shows the current image, uploads a new one (on form submit), removes it,
    or picks an existing image from the scope library. Emits, for a base `name`:
      {name}[path]     hidden — stored path (reuse or kept current; empty if none/new)
      {name}[file]     file   — a new upload (server stores it, overriding path)
      {name}[alt]      text   — alt (when showAlt)
      {name}[caption]  text   — caption (when showCaption)

    Props: name, path, alt, caption, scope (required), showUsage, usageCount,
           showAlt, showCaption, altRequired, maxSize (KB), accept, label, helpText.
--}}
@props([
    'name',
    'path' => null,
    'alt' => '',
    'caption' => '',
    'scope',
    'showUsage' => false,
    'usageCount' => null,
    'showAlt' => true,
    'showCaption' => true,
    'altRequired' => true,
    'maxSize' => 2048,
    'accept' => 'image/jpeg,image/png,image/webp',
    'label' => null,
    'helpText' => null,
])

@php
    $uid = 'media-field-' . Str::random(8);
    $api = app(\App\Domains\Media\Public\Api\MediaPublicApi::class);
    $currentUrl = $path ? $api->variantUrl($path, 400, 'webp') : null;
    if ($showUsage && $usageCount === null && $path) {
        $usageCount = $api->countUsages($path);
    }
    $maxSizeMB = round($maxSize / 1024, 1);
@endphp

<div
    x-data="mediaImageField({
        path: @js($path),
        currentUrl: @js($currentUrl),
        scope: @js($scope),
        libraryUrl: @js(route('media.library')),
        maxSizeKb: @js($maxSize),
        sizeErrorMessage: @js(__('media::image-field.size_error', ['max' => $maxSizeMB])),
    })"
    {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}
>
    @if($label)
        <x-shared::input-label :for="$uid">{{ $label }}</x-shared::input-label>
    @endif

    {{-- Preview / empty state --}}
    <div class="relative">
        <template x-if="previewUrl">
            <div class="relative inline-block">
                <img :src="previewUrl" alt="{{ __('media::image-field.preview_alt') }}"
                     class="max-w-full max-h-48 rounded-lg object-contain shadow-sm" />
                <button type="button" x-on:click="remove()"
                    class="absolute surface-read top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full border border-border text-error hover:bg-error hover:text-white transition-colors"
                    title="{{ __('media::image-field.remove') }}">
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </div>
        </template>

        <template x-if="!previewUrl">
            <div class="relative border-2 border-dashed border-border rounded-lg p-6 transition-colors cursor-pointer"
                :class="{ 'border-primary bg-primary/5': isDragging, 'hover:border-primary/50 hover:bg-primary/5': !isDragging }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                x-on:click="$refs.fileInput.click()">
                <div class="flex flex-col items-center gap-3 text-fg/60">
                    <span class="material-symbols-outlined text-[48px]">add_photo_alternate</span>
                    <div class="text-center">
                        <p class="text-sm font-medium">{{ __('media::image-field.drop_or_click') }}</p>
                        <p class="text-xs mt-1">{{ __('media::image-field.max_size', ['size' => $maxSizeMB]) }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3 flex-wrap">
        <button type="button" x-on:click="$refs.fileInput.click()"
            class="text-sm text-primary hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">upload</span>
            {{ __('media::image-field.upload') }}
        </button>
        <button type="button" x-on:click="openPicker()"
            class="text-sm text-primary hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[18px]">photo_library</span>
            {{ __('media::image-field.choose_existing') }}
        </button>
        @if($showUsage)
            <span class="text-xs text-fg/60" x-show="path"
                  title="{{ __('media::image-field.usage_hint') }}">
                {{ __('media::image-field.used_in_places', ['count' => (int) ($usageCount ?? 0)]) }}
            </span>
        @endif
    </div>

    {{-- Hidden path + file inputs --}}
    <input type="hidden" name="{{ $name }}[path]" x-model="path" />
    <input type="file" name="{{ $name }}[file]" x-ref="fileInput" accept="{{ $accept }}"
           class="hidden" x-on:change="handleFileSelect($event)" />

    @if($showAlt)
        <div class="flex flex-col gap-1">
            <x-shared::input-label :for="$uid.'-alt'">
                {{ __('media::image-field.alt_label') }}@if($altRequired) <span class="text-error">*</span>@endif
            </x-shared::input-label>
            <input type="text" id="{{ $uid }}-alt" name="{{ $name }}[alt]" value="{{ $alt }}"
                   @if($altRequired) x-bind:required="!!path || isNewFile" @endif
                   class="border border-border rounded-md px-3 py-2 text-sm"
                   placeholder="{{ __('media::image-field.alt_placeholder') }}" />
        </div>
    @endif

    @if($showCaption)
        <div class="flex flex-col gap-1">
            <x-shared::input-label :for="$uid.'-caption'">{{ __('media::image-field.caption_label') }}</x-shared::input-label>
            <input type="text" id="{{ $uid }}-caption" name="{{ $name }}[caption]" value="{{ $caption }}"
                   class="border border-border rounded-md px-3 py-2 text-sm"
                   placeholder="{{ __('media::image-field.caption_placeholder') }}" />
        </div>
    @endif

    @if($helpText)
        <p class="text-xs text-fg/60">{{ $helpText }}</p>
    @endif
    <p x-show="sizeError" x-cloak class="text-sm text-error" x-text="sizeError"></p>

    {{-- Picker modal --}}
    <div x-show="pickerOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         x-on:click.self="pickerOpen = false" x-on:keydown.escape.window="pickerOpen = false">
        <div class="surface-read bg-white rounded-lg max-w-3xl w-full max-h-[80vh] overflow-auto p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-medium">{{ __('media::image-field.picker_title') }}</h3>
                <button type="button" x-on:click="pickerOpen = false"
                        class="material-symbols-outlined text-fg/60 hover:text-fg">close</button>
            </div>
            <template x-if="library.length === 0 && !loading">
                <p class="text-sm text-fg/60 py-8 text-center">{{ __('media::image-field.picker_empty') }}</p>
            </template>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                <template x-for="item in library" :key="item.path">
                    <button type="button" x-on:click="chooseExisting(item)"
                        class="aspect-square border border-border rounded-md overflow-hidden hover:ring-2 hover:ring-primary">
                        <img :src="item.url" alt="" class="w-full h-full object-cover" loading="lazy" />
                    </button>
                </template>
            </div>
            <div class="mt-3 text-center" x-show="hasMore">
                <button type="button" x-on:click="loadMore()" class="text-sm text-primary hover:underline">
                    {{ __('media::image-field.picker_more') }}
                </button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mediaImageField', ({ path, currentUrl, scope, libraryUrl, maxSizeKb, sizeErrorMessage }) => ({
            path: path || '',
            previewUrl: currentUrl || null,
            isNewFile: false,
            isDragging: false,
            sizeError: null,
            pickerOpen: false,
            loading: false,
            library: [],
            page: 1,
            hasMore: false,

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.acceptFile(file);
            },
            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    if (!this.checkSize(file)) return;
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.fileInput.files = dt.files;
                    this.setPreview(file);
                }
            },
            acceptFile(file) {
                if (!this.checkSize(file)) return;
                this.setPreview(file);
            },
            checkSize(file) {
                if (maxSizeKb && file.size > maxSizeKb * 1024) {
                    this.sizeError = sizeErrorMessage;
                    this.$refs.fileInput.value = '';
                    return false;
                }
                this.sizeError = null;
                return true;
            },
            setPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.isNewFile = true;
                    this.path = ''; // a new upload replaces any reused path
                };
                reader.readAsDataURL(file);
            },
            remove() {
                this.path = '';
                this.previewUrl = null;
                this.isNewFile = false;
                this.$refs.fileInput.value = '';
            },
            async openPicker() {
                this.pickerOpen = true;
                this.page = 1;
                this.library = [];
                await this.fetchPage();
            },
            async loadMore() {
                this.page += 1;
                await this.fetchPage();
            },
            async fetchPage() {
                this.loading = true;
                try {
                    const res = await fetch(`${libraryUrl}?scope=${encodeURIComponent(scope)}&page=${this.page}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.library = this.page === 1 ? data.items : [...this.library, ...data.items];
                    this.hasMore = data.hasMore;
                } finally {
                    this.loading = false;
                }
            },
            chooseExisting(item) {
                this.path = item.path;
                this.previewUrl = item.url;
                this.isNewFile = false;
                this.$refs.fileInput.value = '';
                this.pickerOpen = false;
            },
        }));
    });
</script>
@endpush
@endonce
