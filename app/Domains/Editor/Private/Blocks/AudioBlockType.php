<?php

declare(strict_types=1);

namespace App\Domains\Editor\Private\Blocks;

use App\Domains\Editor\Public\Blocks\EditorBlockType;
use App\Domains\Media\Public\Api\MediaPublicApi;
use Illuminate\Support\Facades\Blade;

/**
 * Built-in `audio` block, opt-in via `blockTypes`:
 * ['type' => 'audio', 'path' => 'news/x.mp3', 'caption' => '…'?].
 * No Purifier profile allows <audio>, so the type emits its own HTML, escaped.
 */
final class AudioBlockType implements EditorBlockType
{
    public function __construct(private readonly MediaPublicApi $media)
    {
    }

    public function key(): string
    {
        return 'audio';
    }

    public function labelKey(): string
    {
        return 'editor::multi.add_audio';
    }

    public function icon(): string
    {
        return 'music_note';
    }

    public function editorView(): string
    {
        return 'editor::components.multi._audio-block';
    }

    public function render(array $block, array $context): string
    {
        $path = $block['path'] ?? null;
        if (!$path) {
            return '';
        }
        $caption = trim((string) ($block['caption'] ?? ''));

        return Blade::render(
            <<<'BLADE'
<figure class="ce-block ce-block--audio">
    <audio controls preload="metadata" class="w-full" src="{{ $url }}" aria-label="{{ $label }}">{{ $fallback }}</audio>
    @if ($caption !== '')
        <figcaption>{{ $caption }}</figcaption>
    @endif
</figure>
BLADE,
            [
                'url' => $this->media->originalUrl((string) $path),
                'caption' => $caption,
                'label' => $caption !== '' ? $caption : __('editor::multi.audio_player_label'),
                'fallback' => __('editor::multi.audio_no_support'),
            ]
        );
    }
}
