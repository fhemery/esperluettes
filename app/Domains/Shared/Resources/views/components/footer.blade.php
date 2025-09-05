<footer class="bg-bg text-fg/90">
    <div class="min-h-4 bg-primary w-full"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <img src="{{ asset('images/themes/autumn/logo-full.png') }}" alt="{{config('app.name')}}" class="h-24">
        <div class="grid grid-cols-3 md:grid-cols-6 gap-8 md:-mt-4">
           <div class="col-span-3 md:col-span-2 md:pl-24">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Necessitatibus id nam ratione quibusdam officia alias, sint ipsa, nesciunt commodi minus veniam 
           </div>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">Le Jardin</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-primary">Le règlement</a></li>
                    <li><a href="#" class="hover:text-primary">L’équipe</a></li>
                    <li><a href="#" class="hover:text-primary">À propos</a></li>
                    <li><a href="#" class="hover:text-primary">Historique</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">En savoir plus</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-primary">Le règlement</a></li>
                    <li><a href="#" class="hover:text-primary">FAQ</a></li>
                    <li><a href="#" class="hover:text-primary">Contact</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-primary mb-3">Le Droit</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-primary">Mentions légales</a></li>
                    <li><a href="#" class="hover:text-primary">CGU</a></li>
                    <li><a href="#" class="hover:text-primary">L’association</a></li>
                </ul>
            </div>
            <!-- Big ampersand / decorative area -->
            <div class="items-end hidden md:flex">
                <div class="text-[96px] leading-none font-extrabold text-primary/10 select-none">&amp;</div>
                <div class="text-[164px] leading-none font-extrabold text-primary/10 select-none">&amp;</div>
            </div>
        </div>

        <div class="w-full flex items-center justify-center text-xs text-fg/70">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </div>
</footer>
