{{--
    Stored in story_chapters.content at save time; Editor does not re-sanitize
    plugin output, so every value goes through Blade escaping.
    Never add ce-block--text here: only text blocks are quotable.
    indent-0 and text-center cancel the article's inherited text-indent and
    text-align: justify; no-underline! beats the unlayered `.rich-content a`
    underline rule.
--}}
<div class="ce-block ce-block--chapter-choice not-prose indent-0 text-center my-4 flex flex-wrap justify-center gap-2">@foreach ($links as $link)<a href="{{ $link['url'] }}" class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium surface-accent text-on-surface border-surface hover:border-surface/90 no-underline! transition ease-in-out duration-150">{{ $link['text'] }}</a>@endforeach</div>
