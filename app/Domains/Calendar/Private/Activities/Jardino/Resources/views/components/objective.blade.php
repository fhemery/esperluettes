<div class="mt-4 p-3 border rounded">
    <div class="text-sm text-fg/80">
        <div class="font-medium">{{ $objective->storyTitle }}</div>
        <div>{{ number_format($objective->targetWordCount, 0, ',', ' ') }} mots</div>
    </div>
</div>
