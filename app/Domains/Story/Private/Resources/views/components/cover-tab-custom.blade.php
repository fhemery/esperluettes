{{--
    Custom cover tab content.
    Props:
    - existingCustomCoverUrl: URL of the currently stored custom cover (or null)
    Dispatches 'custom-cover-selected' event with { previewUrl, hasNewFile }.
--}}
@props(['existingCustomCoverUrl' => null])

<div x-show="tab === 'custom'" x-cloak
    x-data="customCoverTab(@js($existingCustomCoverUrl))"
    class="flex flex-col sm:flex-row gap-6 items-start p-4">

    {{-- Preview --}}
    <div class="flex-shrink-0 w-[150px]">
        <template x-if="previewUrl">
            <img :src="previewUrl" alt="" class="w-[150px] object-contain rounded" loading="lazy" />
        </template>
        <template x-if="!previewUrl">
            <div class="w-[150px] h-[200px] bg-gray-100 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-gray-300 text-4xl">image</span>
            </div>
        </template>
    </div>

    {{-- Controls --}}
    <div class="flex flex-col gap-4 flex-1">
        <p class="text-sm text-fg">{{ __('story::shared.cover.custom_description') }}</p>

        {{-- AI warning notice --}}
        <div class="flex items-start gap-2 rounded-md bg-warning/10 border border-warning/40 px-3 py-2 text-sm text-warning-fg">
            <span class="material-symbols-outlined text-[18px] mt-0.5 flex-shrink-0">warning</span>
            <span>{{ __('story::shared.cover.custom_ai_warning') }}</span>
        </div>

        {{-- Upload area --}}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium">{{ __('story::shared.cover.custom_upload_label') }}</label>
            <div
                class="relative border-2 border-dashed border-border rounded-lg p-4 transition-colors cursor-pointer"
                :class="{ 'border-primary bg-primary/5': isDragging, 'hover:border-primary/50 hover:bg-primary/5': !isDragging }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                x-on:click="$refs.customFileInput.click()"
            >
                <div class="flex flex-col items-center gap-2 text-fg/60">
                    <span class="material-symbols-outlined text-[36px]">add_photo_alternate</span>
                    <p class="text-xs text-center">{{ __('shared::image-upload.drop_or_click') }}</p>
                    <p class="text-xs text-center">{{ __('story::shared.cover.custom_dimensions') }}</p>
                    <p class="text-xs text-center">{{ __('shared::image-upload.max_size', ['size' => 2]) }}</p>
                </div>
            </div>

            {{-- Size error --}}
            <p x-show="sizeError" x-cloak class="text-sm text-error" x-text="sizeError"></p>

            <input
                type="file"
                name="cover_image"
                x-ref="customFileInput"
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                x-on:change="handleFileSelect($event)"
            />
        </div>

        {{-- Rights confirmation checkbox (shown only when a new file is selected) --}}
        <div x-show="hasNewFile" x-cloak class="flex items-start gap-2">
            <input
                type="checkbox"
                id="cover-rights-confirmed"
                name="cover_rights_confirmed"
                value="1"
                x-model="rightsConfirmed"
                class="mt-0.5 rounded border-accent text-accent shadow-sm focus:border-accent focus:ring-accent/10"
            />
            <label for="cover-rights-confirmed" class="text-sm text-fg cursor-pointer">
                {{ __('story::shared.cover.custom_rights_label') }}
            </label>
        </div>
        <x-input-error :messages="$errors->get('cover_rights_confirmed')" />

        {{-- Select button --}}
        <div class="flex justify-center">
            <x-shared::button type="button" color="accent"
                x-bind:disabled="!canSelect"
                @click="confirmCustom(); $dispatch('close-modal', 'cover-selector')">
                {{ __('story::shared.cover.select') }}
            </x-shared::button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
if (!window.customCoverTab) {
    window.customCoverTab = function (existingCoverUrl) {
        return {
            previewUrl: existingCoverUrl || null,
            hasNewFile: false,
            rightsConfirmed: false,
            isDragging: false,
            sizeError: null,

            get canSelect() {
                if (this.hasNewFile) {
                    return this.rightsConfirmed;
                }
                return !!this.previewUrl;
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    if (!this.checkSize(file)) return;
                    this.setPreview(file);
                }
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    if (!this.checkSize(file)) return;
                    const input = this.$refs.customFileInput;
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    this.setPreview(file);
                }
            },

            checkSize(file) {
                const maxKb = 2048;
                if (file.size > maxKb * 1024) {
                    this.sizeError = @js(__('shared::image-upload.size_error', ['max' => 2]));
                    this.$refs.customFileInput.value = '';
                    return false;
                }
                this.sizeError = null;
                return true;
            },

            setPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.hasNewFile = true;
                    this.rightsConfirmed = false;
                };
                reader.readAsDataURL(file);
            },

            confirmCustom() {
                if (!this.canSelect) return;
                this.$dispatch('custom-cover-selected', { previewUrl: this.previewUrl, hasNewFile: this.hasNewFile });
            },
        };
    };
}
</script>
@endpush
@endonce
