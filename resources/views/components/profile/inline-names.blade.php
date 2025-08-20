@props([
    // Array|Collection of ProfileDto-like objects
    // Expected fields: display_name or name, slug or profile_slug
    'profiles' => [],
    // Tailwind classes for links
    'linkClass' => 'text-indigo-700 hover:text-indigo-900 underline',
])

@php($profilesArr = is_array($profiles) ? $profiles : $profiles->all())
@php($count = count($profilesArr))
@php($names = [])

@foreach($profilesArr as $idx => $p)
    @php($name = $p->display_name ?? $p->name ?? '')
    @php($slug = $p->slug ?? $p->profile_slug ?? null)
    @php($names[] = $name)

    @if($idx > 0), @endif

    @if($slug)
        <a class="{{ $linkClass }}" href="{{ route('profile.show', ['profile' => $slug]) }}">{{ $name }}</a>
    @else
        {{ $name }}
    @endif
@endforeach

<span class="sr-only">{{ implode(', ', $names) }}</span>
