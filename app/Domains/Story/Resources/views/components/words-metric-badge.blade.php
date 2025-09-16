@props(['nbWords' => 0, 'nbCharacters' => 0])

@php($label = __('story::shared.metrics.words_and_signs', [
    'nbWords' => \App\Domains\Shared\Support\NumberFormatter::compact($nbWords),
    'nbCharacters' => \App\Domains\Shared\Support\NumberFormatter::compact($nbCharacters)
]))

<x-shared::metric-badge
    {{ $attributes }}
    icon="article"
    :value="$nbWords"
    :label="$label"
    :tooltip="''"
/>
