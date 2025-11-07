@props([
    'count' => 0,
])

<x-shared::metric-badge
    icon="bookmark"
    size="md"
    :value="$count"
    :label="''"
    :tooltip="trans_choice('readlist::counter.counter_tooltip', $count)"
/>
