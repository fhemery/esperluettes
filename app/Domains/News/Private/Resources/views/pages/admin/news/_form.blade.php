@props(['news' => null])

@php
    $isEdit = $news !== null;
@endphp

<div class="flex flex-col gap-6">
    {{-- Title --}}
    <div>
        <x-shared::input-label for="title" :required="true">
            {{ __('news::admin.form.title') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="title"
            name="title"
            class="mt-1 block w-full rounded-md"
            :value="old('title', $news?->title ?? '')"
            required
            maxlength="200"
            x-data
            x-on:blur="if (!document.getElementById('slug').value) { 
                document.getElementById('slug').value = $el.value
                    .toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }"
        />
        <x-shared::input-error :messages="$errors->get('title')" class="mt-1" />
    </div>

    {{-- Slug --}}
    <div>
        <x-shared::input-label for="slug" :required="true">
            {{ __('news::admin.form.slug') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="slug"
            name="slug"
            class="mt-1 block w-full rounded-md font-mono"
            :value="old('slug', $news?->slug ?? '')"
            required
            maxlength="255"
            pattern="[a-z0-9\-]+"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('news::admin.form.slug_help') }}</p>
        <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    {{-- Header Image --}}
    <div>
        <x-media::image-field
            name="header_image"
            scope="news"
            :path="old('header_image.path', $news?->header_image_path)"
            :show-alt="false"
            :show-caption="false"
            :show-usage="false"
            :label="__('news::admin.form.header_image')"
            :help-text="__('news::admin.form.header_image_help')"
        />
        <x-shared::input-error :messages="$errors->get('header_image.file')" class="mt-1" />
    </div>

    {{-- Summary --}}
    <div>
        <x-shared::input-label for="summary" :required="true">
            {{ __('news::admin.form.summary') }}
        </x-shared::input-label>
        <textarea
            id="summary"
            name="summary"
            rows="3"
            class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface"
            required
            maxlength="500"
        >{{ old('summary', $news?->summary ?? '') }}</textarea>
        <p class="text-xs text-fg/60 mt-1">{{ __('news::admin.form.summary_help') }}</p>
        <x-shared::input-error :messages="$errors->get('summary')" class="mt-1" />
    </div>

    {{-- Content (Rich Editor) --}}
    <div>
        <x-shared::input-label for="content" :required="true">
            {{ __('news::admin.form.content') }}
        </x-shared::input-label>
        @php
            // On a failed validation re-render, rebuild the advanced blocks from old input
            // (uploaded files are not repopulated by the browser and must be re-picked).
            $meBlocks = $news?->content_blocks ?? [];
            if (old('mode') === 'advanced' && is_array(old('blocks'))) {
                $meBlocks = [];
                foreach (array_filter(explode(',', (string) old('blocks_order', ''))) as $uid) {
                    $ob = old('blocks')[$uid] ?? null;
                    if (!is_array($ob)) { continue; }
                    $meBlocks[] = [
                        'type' => $ob['type'] ?? 'text',
                        'html' => $ob['html'] ?? '',
                        'path' => $ob['path'] ?? null,
                        'alt' => $ob['alt'] ?? '',
                        'caption' => $ob['caption'] ?? '',
                        'keep_original' => !empty($ob['keep_original']),
                    ];
                }
            }
            $meMode = old('mode', ($news?->content_blocks ? 'advanced' : 'simple'));
        @endphp
        <x-editor::multi
            name="blocks"
            content-name="content"
            :content-value="old('content', $news?->content ?? '')"
            :blocks="$meBlocks"
            :mode="$meMode"
            scope="news"
            toolbar="editorial"
            class="mt-1"
        />
        <x-shared::input-error :messages="$errors->get('content')" class="mt-1" />
        <x-shared::input-error :messages="$errors->get('blocks')" class="mt-1" />
    </div>

    <hr class="border-border" />

    {{-- Status & Settings --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Status --}}
        <div>
            <x-shared::input-label for="status" :required="true">
                {{ __('news::admin.form.status') }}
            </x-shared::input-label>
            <select
                id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface"
                required
            >
                <option value="draft" @selected(old('status', $news?->status ?? 'draft') === 'draft')>
                    {{ __('news::admin.status.draft') }}
                </option>
                <option value="published" @selected(old('status', $news?->status) === 'published')>
                    {{ __('news::admin.status.published') }}
                </option>
            </select>
            <x-shared::input-error :messages="$errors->get('status')" class="mt-1" />
        </div>

        {{-- Is Pinned --}}
        <div class="flex items-end pb-2">
            <x-shared::toggle
                name="is_pinned"
                :checked="old('is_pinned', $news?->is_pinned ?? false)"
                :label="__('news::admin.form.is_pinned')"
            />
        </div>
    </div>

    {{-- Meta Description --}}
    <div>
        <x-shared::input-label for="meta_description">
            {{ __('news::admin.form.meta_description') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="meta_description"
            name="meta_description"
            class="mt-1 block w-full rounded-md"
            :value="old('meta_description', $news?->meta_description ?? '')"
            maxlength="160"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('news::admin.form.meta_description_help') }}</p>
        <x-shared::input-error :messages="$errors->get('meta_description')" class="mt-1" />
    </div>

    {{-- Submit --}}
    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">
            {{ $isEdit ? __('news::admin.form.update') : __('news::admin.form.create') }}
        </x-shared::button>
        <a href="{{ route('news.admin.index') }}">
            <x-shared::button type="button" color="secondary">
                {{ __('news::admin.form.cancel') }}
            </x-shared::button>
        </a>
    </div>
</div>
