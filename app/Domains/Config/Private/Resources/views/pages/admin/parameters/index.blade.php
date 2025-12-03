<x-admin::layout>
    <div class="flex flex-col gap-6" x-data="parameterSearch()">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <x-shared::title>{{ __('config::admin.parameters.title') }}</x-shared::title>
            
            {{-- Search input --}}
            <div class="w-full md:w-96">
                <input 
                    type="text" 
                    x-model="query" 
                    placeholder="{{ __('config::admin.parameters.search_placeholder') }}"
                    class="w-full px-4 py-2 border border-border rounded-lg bg-surface text-fg focus:ring-2 focus:ring-primary focus:border-primary"
                />
            </div>
        </div>

        @if($grouped->isEmpty())
            <div class="surface-read text-on-surface p-8 text-center">
                <p class="text-fg/50">{{ __('config::admin.parameters.no_parameters') }}</p>
            </div>
        @else
            {{-- Grouped parameters --}}
            @foreach($grouped as $domain => $params)
                <section 
                    class="surface-read text-on-surface rounded-lg overflow-hidden"
                    x-show="matchesDomain('{{ $domain }}')"
                    x-cloak
                >
                    <div class="bg-surface-alt px-4 py-3 border-b border-border">
                        <h2 class="text-lg font-semibold flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">folder</span>
                            {{ __($domain . '::config.domain_name', [], null, $domain . '::config.domain_name') }}
                            <span class="text-sm font-normal text-fg/50">({{ $domain }})</span>
                        </h2>
                    </div>

                    <div class="divide-y divide-border/50">
                        @foreach($params as $param)
                            <x-config::parameter-row 
                                :definition="$param['definition']"
                                :value="$param['value']"
                                :is-overridden="$param['isOverridden']"
                            />
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- No results message --}}
            <div x-show="noResults" x-cloak class="surface-read text-on-surface p-8 text-center">
                <p class="text-fg/50">{{ __('config::admin.parameters.no_results') }}</p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function parameterSearch() {
            return {
                query: '',
                
                matchesDomain(domain) {
                    if (!this.query.trim()) return true;
                    
                    const q = this.query.toLowerCase();
                    const section = document.querySelector(`[x-show="matchesDomain('${domain}')"]`);
                    if (!section) return false;
                    
                    // Check if any parameter row in this domain matches
                    const rows = section.querySelectorAll('[data-search-content]');
                    for (const row of rows) {
                        const content = row.getAttribute('data-search-content').toLowerCase();
                        if (content.includes(q)) {
                            return true;
                        }
                    }
                    return false;
                },
                
                get noResults() {
                    if (!this.query.trim()) return false;
                    
                    const q = this.query.toLowerCase();
                    const allRows = document.querySelectorAll('[data-search-content]');
                    for (const row of allRows) {
                        const content = row.getAttribute('data-search-content').toLowerCase();
                        if (content.includes(q)) {
                            return false;
                        }
                    }
                    return true;
                }
            };
        }
    </script>
    @endpush
</x-admin::layout>
