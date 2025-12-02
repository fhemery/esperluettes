{{-- 
    Image Upload Component
    
    A reusable image upload component with preview and delete functionality.
    
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
    - removable: Whether to show delete option (default: true)
    - label: Label text (optional)
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

    {{-- Upload/Preview area --}}
    <div class="relative">
        {{-- Image preview with action button --}}
        <template x-if="previewUrl">
            <div class="relative inline-block">
                <img 
                    :src="previewUrl" 
                    alt="{{ __('shared::image-upload.preview_alt') }}"
                    class="max-w-full max-h-48 rounded-lg object-contain shadow-sm"
                />
                
                {{-- Delete button for current image (not new) --}}
                @if($removable)
                <button 
                    type="button"
                    x-show="!isNewFile"
                    x-on:click="markForDeletion()"
                    class="absolute surface-read top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full border border-border text-error hover:bg-error hover:text-white transition-colors"
                    title="{{ __('shared::image-upload.delete') }}"
                >
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
                @endif
                
                {{-- Cancel button for new upload --}}
                <button 
                    type="button"
                    x-show="isNewFile"
                    x-on:click="clearNewFile()"
                    class="absolute surface-read top-2 right-2 w-7 h-7 flex items-center justify-center rounded-full border border-border text-error hover:bg-error hover:text-white transition-colors"
                    title="{{ __('shared::image-upload.cancel') }}"
                >
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </template>

        {{-- Empty state / Upload prompt --}}
        <template x-if="!previewUrl">
            <div 
                class="relative border-2 border-dashed border-border rounded-lg p-6 transition-colors cursor-pointer"
                :class="{ 'border-primary bg-primary/5': isDragging, 'hover:border-primary/50 hover:bg-primary/5': !isDragging }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                x-on:click="$refs.fileInput.click()"
            >
                <div class="flex flex-col items-center gap-3 text-fg/60">
                    <span class="material-symbols-outlined text-[48px]">add_photo_alternate</span>
                    <div class="text-center">
                        <p class="text-sm font-medium">{{ __('shared::image-upload.drop_or_click') }}</p>
                        @if($dimensionGuide)
                            <p class="text-xs mt-1">{{ $dimensionGuide }}</p>
                        @endif
                        <p class="text-xs mt-1">{{ __('shared::image-upload.max_size', ['size' => $maxSizeMB]) }}</p>
                    </div>
                </div>
            </div>
        </template>

        {{-- Hidden file input --}}
        <input 
            type="file"
            name="{{ $name }}"
            id="{{ $inputId }}"
            x-ref="fileInput"
            accept="{{ $accept }}"
            class="hidden"
            x-on:change="handleFileSelect($event)"
        />
        
        {{-- Hidden delete marker --}}
        <input 
            type="hidden"
            name="{{ $name }}_remove"
            x-model="markedForRemoval"
        />
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
                    const input = this.$refs.fileInput;
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

            markForDeletion() {
                this.markedForRemoval = true;
                this.previewUrl = null;
                this.isNewFile = false;
                // Clear file input
                this.$refs.fileInput.value = '';
            },

            clearNewFile() {
                this.$refs.fileInput.value = '';
                this.isNewFile = false;
                this.markedForRemoval = false;
                // Restore original preview if there was one
                this.previewUrl = hasCurrentImage ? currentUrl : null;
            },
        }));
    });
</script>
@endpush
@endonce
