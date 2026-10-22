<div class="flex flex-wrap gap-4">
    @foreach ($fanarts as $image)
        <x-fanart::thumbnail :fanart="$image" />
    @endforeach
</div>
