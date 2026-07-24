<x-app-layout size="lg">

    @section('title', $vm->getTitle())
    @push('meta')
        <meta name="description" content="{{ $vm->getMetaDescription() }}" />
    @endpush

    <section class="container mx-auto">
        <x-shared::title>{{ __('faq::index.title') }}</x-shared::title>

        @if(isset($vm) && !empty($vm->tabsAsArray()))
            <div class="mt-4">
                <div class="flex flex-wrap">
                    @foreach($vm->tabsAsArray() as $tab)
                        @php $active = $tab['key'] === $vm->getInitialTabKey(); @endphp
                        <a href="{{ route('faq.category', ['categorySlug' => $tab['key']]) }}"
                           class="surface-primary text-on-surface uppercase flex-1 border-r-read 
                            text-center px-2 py-2 text-md font-medium {{ $active ? 'is-outline' : '' }}
                            flex items-center justify-center">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 surface-read p-3 text-on-surface rounded">
                {{ __('faq::index.empty_categories') }}
            </div>
        @endif

        @if(isset($questions) && $questions->isNotEmpty())
            <div class="surface-read p-2 sm:p-4 text-on-surface flex flex-col gap-4">
                @foreach($questions as $q)
                    <x-shared::collapsible :title="$q->question" color="primary" textColor="fg">
                        @if(!empty($q->image_path))
                            <x-media::image
                                :path="$q->image_path"
                                :alt="$q->image_alt_text ?? ''"
                                class="mb-3"
                                imgClass="block mx-auto max-h-[300px] w-auto h-auto max-w-full" />
                        @endif
                        <div class="text-on-surface">{!! $q->answer !!}</div>
                    </x-shared::collapsible>
                @endforeach
            </div>
        @else
            <div class="flex items-center justify-center surface-read p-8 text-on-surface">
                {{ __('faq::index.empty_questions') }}
            </div>
        @endif
    </section>
</x-app-layout>