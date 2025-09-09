<x-app-layout>
    <div class="bg-seasonal flex flex-col h-full items-center justify-center py-12 px-8 flex-1" data-test-id="home-page">
        <!-- Center translucent panel -->
        <div class="w-full max-w-[600px] bg-dark/90 text-white rounded-lg shadow-xl px-6 py-8">
            <!-- Top logo placeholder -->
            <div class="w-full rounded-sm flex items-center justify-center">
                <img src="{{ asset('images/themes/default/logo-white.png') }}" alt="{{config('app.name')}}" class="h-32">
            </div>

            <div class="mt-12 space-y-4 leading-relaxed text-center text-white">
                <p>Salut à toi qui arrives en ces lieux.</p>

                <p>Ici se trouve l’entrée de notre havre : un jardin luxuriant où poussent les tiges, les feuilles, les vrilles de notre imagination. Les clairières sont peuplées de carnets, de plumes et de chapitres. Les tasses de thé sont toujours fumantes et les chats surgissent de nulle part, porteurs de mystères ou de réconfort. Parfois on croise une fée, un détective ou un vaisseau…</p>

                <p>Ici on s’entraide. On se lit, on se commente, on se conseille. Avec sincérité et bienveillance. Parce qu’on sait qu’écrire réclame du temps, du courage, de l’esprit et de l’âme. </p>

                <p>Si ces quelques mots résonnent pour toi, scribouilleur fortuit ou autrice ambitieuse, lectrice picoreuse ou dévoreur d’histoires, alors pousse la barrière, viens poser tes cahiers.
                    Le Jardin des Esperluettes t’ouvre ses branches.</p>
            </div>

            <div class="mt-12 flex items-center justify-center">
                <a href="{{ route('register') }}" class="btn-accent">{{ __('home::index.join-us') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>