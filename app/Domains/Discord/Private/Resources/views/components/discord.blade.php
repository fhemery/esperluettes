@props(['isLinked' => false, 'discordUsername' => null])

<div x-data="discordComponent()" class="shrink-0">
    @if (!$isLinked)
    <x-shared::badge color="neutral" outline="true">
        <span class="flex items-center surface-read text-on-surface cursor-pointer gap-2"
            data-discord-state="disconnected" data-action="open-link"
            x-on:click="openLinkAndGenerate()">
            <img src="{{ asset('images/icons/Discord-Symbol-Blurple.svg') }}" alt="Discord" class="w-6 h-6">
            <span>{{ __('discord::components.discord-component.link') }}</span>
        </span>
    </x-shared::badge>
    @else
    <span class="self-end inline-flex items-center gap-2 py-2 px-4 rounded-full surface-read text-on-surface cursor-pointer"
        x-on:click="openUnlink">
        <img src="{{ asset('images/icons/Discord-Symbol-Blurple.svg') }}" alt="Discord" class="w-6 h-6">
        <span class="username">{{ $discordUsername }}</span>
        <span class="material-symbols-outlined cursor-pointer" aria-hidden="true">link_off</span>
    </span>
    @endif

    <!-- Link instructions pop-up -->
    <x-shared::modal name="discord-link">
        <div class="p-4 surface-read text-on-surface flex flex-col gap-4">
            <h3 class="font-semibold text-lg mb-2">{{ __('discord::components.discord-component.connect_title') }}</h3>
            <p class="text-sm mb-2">{{ __('discord::components.discord-component.connect_description') }}</p>
            <p class="text-sm text-on-surface-variant mb-2">
                {!! __('discord::components.discord-component.connect_instructions') !!}
            </p>
            <div class="surface-secondary text-on-surface px-3 py-2 rounded font-mono tracking-wide">
                <template x-if="loading">
                    <span>…</span>
                </template>
                <template x-if="!loading && code">
                    <span>/connect code:<span class="font-semibold" x-text="code"></span></span>
                </template>
                <template x-if="!loading && !code">
                    <span>/connect code:________</span>
                </template>
            </div>
            <p class="text-sm">{{ __('discord::components.discord-component.connect_hint') }}</p>
            <div class="flex justify-center">
                <x-shared::button type="button" color="accent" x-on:click="closeLink">{{ __('discord::components.discord-component.ok') }}</x-shared::button>
            </div>
        </div>
    </x-shared::modal>
    <!-- Unlink confirmation pop-up -->
    <x-shared::modal name="discord-unlink">
        <div class="p-4 surface-read text-on-surface flex flex-col gap-4">
            <h3 class="font-semibold text-lg text-accent">{{ __('discord::components.discord-component.unlink_title') }}</h3>
            <p>{!! __('discord::components.discord-component.unlink_description') !!}</p>
            <div class="surface-secondary text-on-surface px-3 py-2 rounded font-mono tracking-wide">
                <span>/disconnect</span>
            </div>
            <div class="flex items-center justify-center gap-2">
                <x-shared::button type="button" color="accent" x-on:click="closeUnlink">{{ __('discord::components.discord-component.ok') }}</x-shared::button>
            </div>
        </div>
    </x-shared::modal>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('discordComponent', () => ({
            
            code: null,
            loading: false,
            error: null,
            async generateCode() {
                this.loading = true;
                this.error = null;
                this.code = null;
                try {
                    const resp = await fetch("{{ route('discord.web.connect.code') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({}),
                    });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    const json = await resp.json();
                    this.code = json.code || null;
                } catch (e) {
                    this.error = 'failed';
                } finally {
                    this.loading = false;
                }
            },
            async openLinkAndGenerate() {
                this.$dispatch('open-modal', 'discord-link');
                await this.generateCode();
            },
            async closeLink(){
                this.$dispatch('close-modal', 'discord-link');
            },
            async openUnlink() {
                this.$dispatch('open-modal', 'discord-unlink');
            },
            async closeUnlink(){
                this.$dispatch('close-modal', 'discord-unlink');
            },
        }));
    });
</script>
@endpush