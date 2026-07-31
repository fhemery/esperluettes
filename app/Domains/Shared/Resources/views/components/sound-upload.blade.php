{{-- 
    Sound Upload Component
    
    A reusable sound upload component with preview and delete functionality.
    
    Props:
    - name: Input name for the file upload
    - id: Unique identifier
    - currentPath: Path to current sound file (relative to storage disk)
    - currentUrl: Direct URL to current sound file (overrides currentPath URL generation)
    - disk: Storage disk (default: 'public')
    - maxSize: Max file size in KB (default: 10240 = 10MB)
    - accept: Accepted file types (default: 'audio/mp3')
    - removable: Whether to show delete option (default: true)
    - label: Label text (optional)
    - helpText: Additional help text (optional)
--}}
@props([
    'name',
    'id' => null,
    'currentPath' => null,
    'currentUrl' => null,
    'disk' => 'public',
    'maxSize' => 10240,
    'accept' => 'audio/mp3',
    'removable' => true,
    'label' => null,
    'helpText' => null,
])

@php
    $inputId = $id ?? 'sound-upload-' . Str::random(8);
    $hasCurrentSound = !empty($currentPath) || !empty($currentUrl);
    // Use provided URL directly, or construct from path for public disk
    $resolvedUrl = $currentUrl ?? ($currentPath ? asset('storage/' . $currentPath) : null);
    
    $maxSizeMB = round($maxSize / 1024, 1);
@endphp

<div 
    x-data="soundUpload({ 
        hasCurrentSound: @js($hasCurrentSound), 
        currentUrl: @js($resolvedUrl),
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
        {{-- Sound preview with action button --}}
        <template x-if="previewUrl">
            <div class="relative w-full max-w-md">
                <div class="bg-surface border border-border rounded-lg p-4 shadow-sm">
                    {{-- Audio player --}}
                    <audio 
                        controls 
                        :src="previewUrl"
                        class="w-full mb-3"
                        preload="metadata"
                    >
                        {{ __('shared::sound-upload.browser_no_support') }}
                    </audio>
                    
                    {{-- File info --}}
                    <div class="flex items-center justify-between text-sm text-fg/70">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">audio_file</span>
                            <span x-text="fileName"></span>
                        </div>
                        <span x-text="fileSize" class="text-xs"></span>
                    </div>
                </div>
                
                {{-- Delete button for current sound (not new) --}}
                @if($removable)
                <button 
                    type="button"
                    x-show="!isNewFile"
                    x-on:click="markForDeletion()"
                    class="absolute -top-2 -right-2 w-7 h-7 flex items-center justify-center rounded-full border border-border bg-surface text-error hover:bg-error hover:text-white transition-colors shadow-sm"
                    title="{{ __('shared::sound-upload.delete') }}"
                >
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
                @endif
                
                {{-- Cancel button for new upload --}}
                <button 
                    type="button"
                    x-show="isNewFile"
                    x-on:click="clearNewFile()"
                    class="absolute -top-2 -right-2 w-7 h-7 flex items-center justify-center rounded-full border border-border bg-surface text-error hover:bg-error hover:text-white transition-colors shadow-sm"
                    title="{{ __('shared::sound-upload.cancel') }}"
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
                    <span class="material-symbols-outlined text-[48px]">upload_audio_file</span>
                    <div class="text-center">
                        <p class="text-sm font-medium">{{ __('shared::sound-upload.drop_or_click') }}</p>
                        <p class="text-xs mt-1">{{ __('shared::sound-upload.allowed_formats') }}</p>
                        <p class="text-xs mt-1">{{ __('shared::sound-upload.max_size', ['size' => $maxSizeMB]) }}</p>
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
        Alpine.data('soundUpload', ({ hasCurrentSound, currentUrl, inputId }) => ({
            previewUrl: currentUrl,
            isNewFile: false,
            markedForRemoval: false,
            isDragging: false,
            fileName: '',
            fileSize: '',

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.setPreview(file);
                }
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file && file.type.startsWith('audio/')) {
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
                    this.fileName = file.name;
                    this.fileSize = this.formatFileSize(file.size);
                };
                reader.readAsDataURL(file);
            },

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            markForDeletion() {
                this.markedForRemoval = true;
                this.previewUrl = null;
                this.isNewFile = false;
                this.fileName = '';
                this.fileSize = '';
                // Clear file input
                this.$refs.fileInput.value = '';
            },

            clearNewFile() {
                this.$refs.fileInput.value = '';
                this.isNewFile = false;
                this.markedForRemoval = false;
                this.fileName = '';
                this.fileSize = '';
                // Restore original preview if there was one
                this.previewUrl = hasCurrentSound ? currentUrl : null;
            },
        }));
    });
</script>
@endpush
@endonce
