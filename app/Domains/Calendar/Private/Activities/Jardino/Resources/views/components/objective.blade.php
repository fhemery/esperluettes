<div class="mt-4 p-3 border rounded">
    <div class="flex justify-center">
        <x-shared::title tag="h3" class="text-center">{{ $objective->storyTitle }}</x-shared::title>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
        <div>
            <div class="font-medium text-fg">{{ __('jardino::objective.words_written') }}</div>
            <div class="text-lg">{{ number_format($objective->wordsWritten, 0, ',', ' ') .' / '. number_format($objective->targetWordCount, 0, ',', ' ') }}</div>
        </div>

        <div>
            <div class="font-medium text-fg">{{ __('jardino::objective.progress') }}</div>
            <div class="text-lg">{{ number_format($objective->progressPercentage, 1) }}%</div>
        </div>

        <div>
            <div class="font-medium text-fg">{{ __('jardino::objective.flowers_earned') }}</div>
            <div class="text-lg">{{ $objective->flowersEarned }}</div>
        </div>

        <div>
            <div class="font-medium text-fg">{{ __('jardino::objective.flowers_planted') }}</div>
            <div class="text-lg">{{ $objective->flowersPlanted }}</div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <div class="font-medium text-fg">{{ __('jardino::objective.flowers_available') }}</div>
        <div class="text-2xl font-bold text-accent">{{ $objective->flowersAvailable }}</div>
    </div>
</div>
