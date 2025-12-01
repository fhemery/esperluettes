{{--
    Mature Content Gate Component
    
    Displays an age verification overlay for mature content.
    Uses sessionStorage to persist confirmed age within browser session.
    
    Props:
    - thresholdAge: integer - The minimum age required (e.g., 16, 18)
    - storiesUrl: string - URL to redirect when user clicks "Take me out"
--}}
@props([
    'thresholdAge' => 18,
    'storiesUrl' => '/stories',
])

<div 
    x-data="matureContentGate({ thresholdAge: {{ $thresholdAge }}, storiesUrl: '{{ $storiesUrl }}' })"
    x-show="showGate"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="mature-gate-title"
>
    <!-- Backdrop with blur effect on content behind -->
    <div class="absolute inset-0 bg-bg/80 backdrop-blur-md"></div>
    
    <!-- Gate dialog -->
    <div class="relative surface-read text-on-surface p-6 sm:p-8 rounded-lg shadow-xl max-w-md mx-4 text-center">
        <!-- Age badge -->
        <div class="flex justify-center mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-error/20 border-4 border-error">
                <span class="text-3xl font-bold text-error">-{{ $thresholdAge }}</span>
            </div>
        </div>

        <h2 id="mature-gate-title" class="text-xl font-bold mb-4">
            {{ __('story::mature_gate.title') }}
        </h2>
        
        <p class="text-fg/80 mb-6">
            {{ __('story::mature_gate.description') }}
        </p>

        <!-- Checkbox -->
        <label class="flex items-center justify-center gap-3 mb-6 cursor-pointer select-none">
            <input 
                type="checkbox" 
                x-model="confirmed"
                class="w-5 h-5 rounded border-border text-primary focus:ring-primary"
            />
            <span class="text-fg">{{ __('story::mature_gate.checkbox_label', ['age' => $thresholdAge]) }}</span>
        </label>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <x-shared::button 
                type="button"
                color="primary"
                x-on:click="confirmAge()"
                x-bind:disabled="!confirmed"
            >
                {{ __('story::mature_gate.continue_button') }}
            </x-shared::button>
            
            <a href="{{ $storiesUrl }}">
                <x-shared::button type="button" color="neutral" :outline="true" class="w-full">
                    {{ __('story::mature_gate.leave_link') }}
                </x-shared::button>
            </a>
        </div>
    </div>
</div>

<!-- Blur overlay on the actual content (applied via sibling styling) -->
<div 
    x-data 
    x-show="$store.matureGate?.active" 
    x-cloak
    class="fixed inset-0 z-40 pointer-events-none"
    aria-hidden="true"
></div>

@once
@push('scripts')
<script>
    // Global store for mature gate state (allows other components to know if gate is active)
    document.addEventListener('alpine:init', () => {
        Alpine.store('matureGate', {
            active: false
        });
    });

    function matureContentGate({ thresholdAge, storiesUrl }) {
        return {
            thresholdAge,
            storiesUrl,
            confirmed: false,
            showGate: false,
            
            init() {
                const confirmedAge = this.getConfirmedAge();
                // Show gate if user hasn't confirmed this age or higher
                this.showGate = confirmedAge === null || confirmedAge < this.thresholdAge;
                
                // Update global store
                if (typeof Alpine !== 'undefined' && Alpine.store) {
                    Alpine.store('matureGate').active = this.showGate;
                }
                
                // Apply blur to content if gate is shown
                if (this.showGate) {
                    this.applyContentBlur(true);
                    // Prevent scrolling when gate is shown
                    document.body.style.overflow = 'hidden';
                }
            },
            
            getConfirmedAge() {
                try {
                    const stored = sessionStorage.getItem('mature_content_confirmed_age');
                    return stored ? parseInt(stored, 10) : null;
                } catch (e) {
                    // sessionStorage not available
                    return null;
                }
            },
            
            setConfirmedAge(age) {
                try {
                    const current = this.getConfirmedAge();
                    // Store the highest confirmed age
                    if (current === null || age > current) {
                        sessionStorage.setItem('mature_content_confirmed_age', age.toString());
                    }
                } catch (e) {
                    // sessionStorage not available
                }
            },
            
            confirmAge() {
                if (!this.confirmed) return;
                
                this.setConfirmedAge(this.thresholdAge);
                this.showGate = false;
                
                // Update global store
                if (typeof Alpine !== 'undefined' && Alpine.store) {
                    Alpine.store('matureGate').active = false;
                }
                
                // Remove blur and restore scrolling
                this.applyContentBlur(false);
                document.body.style.overflow = '';
            },
            
            applyContentBlur(blur) {
                // Target the main content area to blur
                const content = document.querySelector('[data-mature-content]');
                if (content) {
                    if (blur) {
                        content.style.filter = 'blur(8px)';
                        content.style.pointerEvents = 'none';
                        content.style.userSelect = 'none';
                    } else {
                        content.style.filter = '';
                        content.style.pointerEvents = '';
                        content.style.userSelect = '';
                    }
                }
            }
        };
    }
</script>
@endpush
@endonce
