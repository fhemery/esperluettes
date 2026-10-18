<?php

namespace App\Domains\Fanart\Private\Requests;

use App\Domains\Fanart\Private\Models\Fanart;
use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\UpdateArtworkData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtworkRequest extends FormRequest
{
    public function fanart(): Fanart
    {
        return $this->route('fanart');
    }

    public function authorize(): bool
    {
        return app(FanartPolicy::class)->editArtwork($this->user()?->id, $this->fanart());
    }

    public function rules(): array
    {
        $fanart = $this->fanart();
        $isAudio = $fanart->kind === FanartKind::Audio;
        $chapterIds = array_column(app(FanartService::class)->publishedChapterOptions($fanart->story_id), 'id');

        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'maxstripped:1000'],
            'chapter_id' => [$isAudio ? 'required' : 'nullable', 'integer', Rule::in($chapterIds)],
            'image' => ['array'],
            'image.file' => $isAudio
                ? ['prohibited']
                : ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sound' => $isAudio
                ? ['nullable', 'file', 'mimes:mp3', 'max:15360']
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('fanart::ui.validation.title_required'),
            'title.max' => __('fanart::ui.validation.title_max'),
            'description.maxstripped' => __('fanart::ui.validation.description_max'),
            'chapter_id.required' => __('fanart::ui.validation.chapter_required'),
            'chapter_id.in' => __('fanart::ui.validation.chapter_invalid'),
            'image.file.mimes' => __('fanart::ui.validation.image_mimes'),
            'image.file.max' => __('fanart::ui.validation.image_max'),
            'sound.mimes' => __('fanart::ui.validation.sound_mimes'),
            'sound.max' => __('fanart::ui.validation.sound_max'),
        ];
    }

    public function toData(): UpdateArtworkData
    {
        $isAudio = $this->fanart()->kind === FanartKind::Audio;

        return new UpdateArtworkData(
            file: $isAudio ? $this->file('sound') : $this->file('image.file'),
            title: trim((string) $this->input('title')),
            description: $this->input('description'),
            chapterId: $this->input('chapter_id') !== null && $this->input('chapter_id') !== ''
                ? (int) $this->input('chapter_id')
                : null,
        );
    }
}
