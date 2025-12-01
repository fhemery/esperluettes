{{-- 
    Image Upload Component
    
    A reusable image upload component with preview, removal, and dimension guidance.
    Suitable for header images, cover images, avatars, etc.
    
    Props:
    - name: Input name for the file upload
    - id: Unique identifier
    - currentPath: Path to current image (relative to storage disk)
    - disk: Storage disk (default: 'public')
    - recommendedWidth: Suggested width in pixels (for guidance)
    - recommendedHeight: Suggested height in pixels (for guidance)
    - aspectRatio: Aspect ratio as string e.g. "16:9" or "3:2" (alternative to dimensions)
    - maxSize: Max file size in KB (default: 2048 = 2MB)
    - accept: Accepted file types (default: 'image/*')
    - removable: Whether to show remove option (default: true)
    - label: Label text (optional, uses default if not provided)
    - helpText: Additional help text (optional)
--}}
@props([
    'name',
    'id' => null,
    'currentPath' => null,
    'disk' => 'public',
    'recommendedWidth' => null,
    'recommendedHeight' => null,
    'aspectRatio' => null,
    'maxSize' => 2048,
    'accept' => 'image/*',
    'removable' => true,
    'label' => null,
    'helpText' => null,
])

@php
    $inputId = $id ?? 'image-upload-' . Str::random(8);
    $hasCurrentImage = !empty($currentPath);
    $currentUrl = $hasCurrentImage ? asset('storage/' . $currentPath) : null;
    
    // Build dimension guidance text
    $dimensionGuide = null;
    if ($recommendedWidth && $recommendedHeight) {
        $dimensionGuide = __('shared::image-upload.recommended_dimensions', [
            'width' => $recommendedWidth,
            'height' => $recommendedHeight,
        ]);
    } elseif ($aspectRatio) {
        $dimensionGuide = __('shared::image-upload.recommended_ratio', [
            'ratio' => $aspectRatio,
        ]);
    }
    
    $maxSizeMB = round($maxSize / 1024, 1);
@endphp

<div 
    x-data="imageUpload({ 
        hasCurrentImage: @js($hasCurrentImage), 
        currentUrl: @js($currentUrl),
        inputId: @js($inputId)
    })"
    {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}
>
    {{-- Label --}}
    @if($label)
        <x-shared::input-label :for="$inputId">{{ $label }}</x-shared::input-label>
    @endif

    {{-- Preview area --}}
    <div 
        class="relative border-2 border-dashed border-border rounded-lg p-4 transition-colors"
        :class="{ 'border-primary bg-primary/5': isDragging, 'hover:border-primary/50': !isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop($event)"
    >
        {{-- Current/Preview image --}}
        <template x-if="previewUrl && !markedForRemoval">
            <div class="flex flex-col items-center gap-3">
                <img 
                    :src="previewUrl" 
                    alt="{{ __('shared::image-upload.preview_alt') }}"
                    class="max-w-full max-h-48 rounded-lg object-contain shadow-sm"
                />
                <div class="flex items-center gap-2 text-sm text-fg/60">
                    <span class="material-symbols-outlined text-success text-[18px]">check_circle</span>
                    <span x-text="isNewFile ? '{{ __('shared::image-upload.new_image_selected') }}' : '{{ __('shared::image-upload.current_image') }}'"></span>
                </div>
            </div>
        </template>

        {{-- Removal notice --}}
        <template x-if="markedForRemoval">
            <div class="flex flex-col items-center gap-3 py-4 text-fg/60">
                <span class="material-symbols-outlined text-[48px] text-warning">hide_image</span>
                <span class="text-sm">{{ __('shared::image-upload.marked_for_removal') }}</span>
            </div>
        </template>

        {{-- Empty state / Upload prompt --}}
        <template x-if="!previewUrl && !markedForRemoval">
            <div class="flex flex-col items-center gap-3 py-4 text-fg/60">
                <span class="material-symbols-outlined text-[48px]">add_photo_alternate</span>
                <div class="text-center">
                    <p class="text-sm font-medium">{{ __('shared::image-upload.drop_or_click') }}</p>
                    @if($dimensionGuide)
                        <p class="text-xs mt-1">{{ $dimensionGuide }}</p>
                    @endif
                    <p class="text-xs mt-1">{{ __('shared::image-upload.max_size', ['size' => $maxSizeMB]) }}</p>
                </div>
            </div>
        </template>

        {{-- Hidden file input --}}
        <input 
            type="file"
            name="{{ $name }}"
            id="{{ $inputId }}"
            accept="{{ $accept }}"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            x-on:change="handleFileSelect($event)"
        />
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        {{-- Remove toggle (only if there's a current image and removable is true) --}}
        @if($removable && $hasCurrentImage)
            <label class="inline-flex items-center gap-2 text-sm cursor-pointer select-none">
                <input 
                    type="checkbox"
                    name="{{ $name }}_remove"
                    class="rounded border-border text-primary focus:ring-primary"
                    x-model="markedForRemoval"
                    x-on:change="if(markedForRemoval) clearNewFile()"
                />
                <span class="text-fg/70">{{ __('shared::image-upload.remove_current') }}</span>
            </label>
        @endif

        {{-- Clear new selection --}}
        <button 
            type="button"
            class="inline-flex items-center gap-1 px-2 py-1 text-sm text-fg/60 hover:text-fg transition-colors"
            x-show="isNewFile"
            x-on:click="clearNewFile()"
        >
            <span class="material-symbols-outlined text-[16px]">close</span>
            {{ __('shared::image-upload.clear') }}
        </button>
    </div>

    {{-- Help text --}}
    @if($helpText)
        <p class="text-xs text-fg/60">{{ $helpText }}</p>
    @endif

    {{-- Error display --}}
    <x-shared::input-error :messages="$errors->get($name)" class="mt-1" />
</div>

@once
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('imageUpload', ({ hasCurrentImage, currentUrl, inputId }) => ({
            previewUrl: currentUrl,
            isNewFile: false,
            markedForRemoval: false,
            isDragging: false,

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.setPreview(file);
                }
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('image/')) {
                    // Update the file input
                    const input = document.getElementById(inputId);
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    this.setPreview(file);
                }
            },

            setPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.isNewFile = true;
                    this.markedForRemoval = false;
                };
                reader.readAsDataURL(file);
            },

            clearNewFile() {
                const input = document.getElementById(inputId);
                input.value = '';
                this.isNewFile = false;
                // Restore original preview if there was one
                this.previewUrl = hasCurrentImage ? currentUrl : null;
            },
        }));
    });
</script>
@endpush
@endonce
