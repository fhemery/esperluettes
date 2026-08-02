@props(['page' => null, 'action', 'method' => 'POST'])

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- Main content section --}}
    <div class="surface-read text-on-surface p-6 rounded-lg">
        <h2 class="text-lg font-semibold mb-4">{{ __('static::admin.form.content_section') }}</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Title --}}
            <div>
                <x-shared::input-label for="title" :required="true">
                    {{ __('static::admin.form.title') }}
                </x-shared::input-label>
                <x-shared::text-input 
                    id="title" 
                    name="title" 
                    :value="old('title', $page?->title)" 
                    required 
                    maxlength="200"
                    x-data
                    x-on:blur="if (!document.getElementById('slug').value) { 
                        document.getElementById('slug').value = $el.value.toLowerCase()
                            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
                    }"
                />
                <x-shared::input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            {{-- Slug --}}
            <div>
                <x-shared::input-label for="slug" :required="true">
                    {{ __('static::admin.form.slug') }}
                </x-shared::input-label>
                <x-shared::text-input 
                    id="slug" 
                    name="slug" 
                    :value="old('slug', $page?->slug)" 
                    required 
                    maxlength="255"
                    pattern="[a-z0-9\-]+"
                />
                <p class="text-xs text-fg/60 mt-1">{{ __('static::admin.form.slug_help') }}</p>
                <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
            </div>
        </div>

        {{-- Header Image --}}
        <div class="mt-4">
            <x-media::image-field
                name="header_image"
                scope="static-pages"
                :path="old('header_image.path', $page?->header_image_path)"
                :show-alt="false"
                :show-caption="false"
                :label="__('static::admin.form.header_image')"
                :help-text="__('static::admin.form.header_image_help')"
            />
            <x-shared::input-error :messages="$errors->get('header_image.file')" class="mt-1" />
        </div>

        {{-- Summary --}}
        <div class="mt-4">
            <x-shared::input-label for="summary">
                {{ __('static::admin.form.summary') }}
            </x-shared::input-label>
            <textarea 
                id="summary" 
                name="summary" 
                rows="3"
                maxlength="500"
                class="w-full rounded-md border-border bg-surface-read text-on-surface focus:border-primary focus:ring-primary"
            >{{ old('summary', $page?->summary) }}</textarea>
            <x-shared::input-error :messages="$errors->get('summary')" class="mt-1" />
        </div>

        {{-- Content (Multi Editor) --}}
        <div class="mt-4">
            <x-shared::input-label for="content" :required="true">
                {{ __('static::admin.form.content') }}
            </x-shared::input-label>
            @php
                // On a failed validation re-render, rebuild the advanced blocks from old input
                // (uploaded files are not repopulated by the browser and must be re-picked).
                $meBlocks = $page?->content_blocks ?? [];
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
                $meMode = old('mode', ($page?->content_blocks ? 'advanced' : 'simple'));
            @endphp
            <x-editor::multi
                name="blocks"
                content-name="content"
                :content-value="old('content', $page?->content ?? '')"
                :blocks="$meBlocks"
                :mode="$meMode"
                scope="static-pages"
                toolbar="editorial"
                :nbLines="15"
            />
            <x-shared::input-error :messages="$errors->get('content')" class="mt-1" />
            <x-shared::input-error :messages="$errors->get('blocks')" class="mt-1" />
        </div>
    </div>

    {{-- Settings section --}}
    <div class="surface-read text-on-surface p-6 rounded-lg">
        <h2 class="text-lg font-semibold mb-4">{{ __('static::admin.form.settings_section') }}</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Status --}}
            <div>
                <x-shared::input-label for="status" :required="true">
                    {{ __('static::admin.form.status') }}
                </x-shared::input-label>
                <select 
                    id="status" 
                    name="status" 
                    required
                    class="w-full rounded-md border-border bg-surface-read text-on-surface focus:border-primary focus:ring-primary"
                >
                    <option value="draft" @selected(old('status', $page?->status ?? 'draft') === 'draft')>
                        {{ __('static::admin.status.draft') }}
                    </option>
                    <option value="published" @selected(old('status', $page?->status) === 'published')>
                        {{ __('static::admin.status.published') }}
                    </option>
                </select>
                <x-shared::input-error :messages="$errors->get('status')" class="mt-1" />
            </div>

            {{-- Meta description --}}
            <div>
                <x-shared::input-label for="meta_description">
                    {{ __('static::admin.form.meta_description') }}
                </x-shared::input-label>
                <x-shared::text-input 
                    id="meta_description" 
                    name="meta_description" 
                    :value="old('meta_description', $page?->meta_description)" 
                    maxlength="160"
                />
                <p class="text-xs text-fg/60 mt-1">{{ __('static::admin.form.meta_description_help') }}</p>
                <x-shared::input-error :messages="$errors->get('meta_description')" class="mt-1" />
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3">
        <a href="{{ route('static.admin.index') }}">
            <x-shared::button type="button" color="secondary">
                {{ __('static::admin.form.cancel') }}
            </x-shared::button>
        </a>
        <x-shared::button type="submit" color="primary">
            {{ $page ? __('static::admin.form.update') : __('static::admin.form.create') }}
        </x-shared::button>
    </div>
</form>
