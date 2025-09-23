<footer class="bg-bg text-fg/90">
    <div class="min-h-4 bg-primary w-full"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 sm:py-4">
        <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-24">
        <div class="grid grid-cols-3 md:grid-cols-5 gap-8 md:-mt-4">
           <div class="col-span-3 md:col-span-2 md:pl-24">
                {{ __('shared::footer.brand_description') }}
           </div>

            <div class="col-span-3 sm:col-span-1">
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">{{ __('shared::footer.title_garden') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ __('shared::footer.link_team.url') }}" class="hover:text-primary">{{ __('shared::footer.link_team.label') }}</a></li>
                    <li><a href="{{ __('shared::footer.link_rules.url') }}" class="hover:text-primary">{{ __('shared::footer.link_rules.label') }}</a></li>
                </ul>
            </div>

            <div class="col-span-3 sm:col-span-1">
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">{{ __('shared::footer.title_learn_more') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ __('shared::footer.link_faq.url') }}" class="hover:text-primary">{{ __('shared::footer.link_faq.label') }}</a></li>
                    <li><a href="{{ __('shared::footer.link_contact.url') }}" class="hover:text-primary">{{ __('shared::footer.link_contact.label') }}</a></li>
                </ul>
            </div>

            <div class="col-span-3 sm:col-span-1">
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">{{ __('shared::footer.title_legal') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ __('shared::footer.link_legal_notice.url') }}" class="hover:text-primary">{{ __('shared::footer.link_legal_notice.label') }}</a></li>
                    <li><a href="{{ __('shared::footer.link_association.url') }}" class="hover:text-primary">{{ __('shared::footer.link_association.label') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="w-full flex items-center justify-center text-xs text-fg/70 mt-4">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </div>
</footer>
