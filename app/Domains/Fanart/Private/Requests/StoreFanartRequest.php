<?php

namespace App\Domains\Fanart\Private\Requests;

use App\Domains\Fanart\Private\Policies\FanartPolicy;
use App\Domains\Fanart\Private\Services\FanartService;
use App\Domains\Fanart\Private\Support\FanartKind;
use App\Domains\Fanart\Private\Support\StoryResolver;
use App\Domains\Fanart\Private\Support\SubmitFanartData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFanartRequest extends FormRequest
{
    private ?int $storyId = null;

    public function storyId(): int
    {
        return $this->storyId ??= app(StoryResolver::class)->resolve((string) $this->route('slug'))->id;
    }

    public function kind(): ?FanartKind
    {
        return FanartKind::tryFrom((string) $this->input('kind'));
    }

    /** Re-checked at save time: the mode, role or visibility may have changed since the page loaded. */
    public function authorize(): bool
    {
        $storyId = $this->storyId();
        $kind = $this->kind();

        // An unknown kind fails validation; there is nothing to authorize yet.
        if ($kind === null) {
            return true;
        }

        $policy = app(FanartPolicy::class);
        $userId = $this->user()?->id;

        return $policy->uploadArtwork($userId, $storyId) || $policy->submit($userId, $storyId, $kind);
    }

    public function rules(): array
    {
        $isAudio = $this->kind() === FanartKind::Audio;
        $chapterIds = array_column(app(FanartService::class)->publishedChapterOptions($this->storyId()), 'id');

        return [
            'kind' => ['required', Rule::enum(FanartKind::class)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'maxstripped:1000'],
            'chapter_id' => [$isAudio ? 'required' : 'nullable', 'integer', Rule::in($chapterIds)],
            'attestation' => ['accepted'],
            'share_on_discord' => ['nullable', 'boolean'],
            'image' => ['array'],
            'image.file' => [
                Rule::requiredIf(fn () => $this->kind() === FanartKind::Image),
                'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048',
            ],
            'sound' => [
                Rule::requiredIf(fn () => $isAudio),
                'nullable', 'file', 'mimes:mp3', 'max:15360',
            ],
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
            'attestation.accepted' => __('fanart::ui.validation.attestation_required'),
            'image.file.required' => __('fanart::ui.validation.image_required'),
            'image.file.mimes' => __('fanart::ui.validation.image_mimes'),
            'image.file.max' => __('fanart::ui.validation.image_max'),
            'sound.required' => __('fanart::ui.validation.sound_required'),
            'sound.mimes' => __('fanart::ui.validation.sound_mimes'),
            'sound.max' => __('fanart::ui.validation.sound_max'),
        ];
    }

    public function toData(): SubmitFanartData
    {
        $kind = $this->kind();
        $file = $kind === FanartKind::Image ? $this->file('image.file') : $this->file('sound');

        return new SubmitFanartData(
            kind: $kind,
            file: $file,
            title: trim((string) $this->input('title')),
            description: $this->input('description'),
            chapterId: $this->input('chapter_id') !== null ? (int) $this->input('chapter_id') : null,
            shareOnDiscord: $this->boolean('share_on_discord'),
        );
    }
}
