<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-semibold mb-6">Announcements</h1>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($announcements as $announcement)
                <article class="border rounded-lg overflow-hidden shadow-sm bg-white">
                    @if($announcement->header_image_path)
                        <a href="{{ route('announcements.show', $announcement->slug) }}">
                            @php
                                $path = pathinfo($announcement->header_image_path ?? '', PATHINFO_DIRNAME);
                                $name = pathinfo($announcement->header_image_path ?? '', PATHINFO_FILENAME);
                            @endphp
                            <picture>
                                <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                                <img
                                    class="w-full h-40 object-cover"
                                    src="{{ asset('storage/'.$announcement->header_image_path) }}"
                                    srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                                    sizes="(max-width: 640px) 100vw, 400px"
                                    alt="{{ $announcement->title }}"
                                    loading="lazy"
                                >
                            </picture>
                        </a>
                    @endif
                    <div class="p-4">
                        <h2 class="text-xl font-bold mb-2">
                            <a href="{{ route('announcements.show', $announcement->slug) }}" class="hover:underline">
                                {{ $announcement->title }}
                            </a>
                        </h2>
                        <p class="text-gray-600 mb-4">{{ $announcement->summary }}</p>
                        <a class="text-primary-600 hover:underline" href="{{ route('announcements.show', $announcement->slug) }}">
                            Read more
                        </a>
                    </div>
                </article>
            @empty
                <p class="text-gray-600">No announcements yet.</p>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $announcements->links() }}
        </div>
    </div>
</x-app-layout>
