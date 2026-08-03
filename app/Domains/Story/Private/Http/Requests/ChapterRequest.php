<?php

namespace App\Domains\Story\Private\Http\Requests;

use App\Domains\Shared\Support\HtmlLinkUtils;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;

class ChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Controller handles 404 authorization; request itself allows validation
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required_trimmed', 'max:255'],
            'author_note' => ['nullable', 'maxstripped:1000'],
            'published' => ['nullable', 'boolean'],
            'publish_at' => ['nullable', 'date', 'after:now'],
            'mode' => ['nullable', Rule::in(['simple', 'advanced'])],
        ];

        if ($this->isAdvanced()) {
            // Advanced (MultiEdit): blocks are the source, content is derived.
            $rules['content'] = ['nullable', 'string'];
            $rules['blocks_order'] = ['nullable', 'string'];
            $rules['blocks'] = ['required', 'array', 'min:1'];
            $rules['blocks.*.type'] = ['required', Rule::in(['text', 'image'])];
            $rules['blocks.*.html'] = ['nullable', 'string'];
            $rules['blocks.*.path'] = ['nullable', 'string', 'max:1024'];
            $rules['blocks.*.alt'] = ['nullable', 'string', 'max:255'];
            $rules['blocks.*.caption'] = ['nullable', 'string', 'max:255'];
            $rules['blocks.*.keep_original'] = ['nullable'];
            $rules['blocks.*.file'] = ['nullable', 'image', 'max:2048'];
        } else {
            $rules['content'] = ['required'];
        }

        return $rules;
    }

    private function isAdvanced(): bool
    {
        return $this->input('mode') === 'advanced';
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        $authorNote = $this->input('author_note');
        if (trim(strip_tags($authorNote)) === '') {
            $authorNote = null;
        }
        $content = $this->input('content');

        $publishAt = $this->input('publish_at');
        $timezone = $this->input('timezone', 'UTC');
        $resolvedPublishAt = null;
        if (is_string($publishAt) && $publishAt !== '') {
            try {
                $tz = in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : 'UTC';
                $resolvedPublishAt = Carbon::createFromFormat('Y-m-d\TH:i', $publishAt, $tz)
                    ->utc()
                    ->toDateTimeString();
            } catch (\Throwable) {
                $resolvedPublishAt = $publishAt;
            }
        }

        $merge = [
            'title' => $title,
            'author_note' => $authorNote !== null
                ? HtmlLinkUtils::stripExternalLinks(Purifier::clean((string) $authorNote, 'strict-with-links'))
                : null,
            'publish_at' => $resolvedPublishAt,
        ];

        if ($this->isAdvanced()) {
            // Block HTML is purified exactly once, in the resolver, with the
            // narrative profile. Purifying here too would let the two policies
            // diverge silently. Alt/caption strings are trimmed for consistency.
            $merge['blocks'] = $this->trimmedBlockLabels();
        } else {
            $merge['content'] = HtmlLinkUtils::stripExternalLinks(
                Purifier::clean((string) ($content ?? ''), 'strict-with-links')
            );
        }

        $this->merge($merge);
    }

    /**
     * @return array<string,mixed>
     */
    private function trimmedBlockLabels(): array
    {
        $blocks = $this->input('blocks');
        if (!is_array($blocks)) {
            return [];
        }

        foreach ($blocks as $uid => $block) {
            if (!is_array($block)) {
                continue;
            }
            foreach (['alt', 'caption'] as $key) {
                if (isset($block[$key]) && is_string($block[$key])) {
                    $blocks[$uid][$key] = trim($block[$key]);
                }
            }
        }

        return $blocks;
    }

    public function messages(): array
    {
        return [
            'title.required_trimmed' => __('story::validation.chapter.title.required'),
            'author_note.maxstripped' => __('story::validation.chapter.author_note_too_long'),
            'content.required' => __('story::validation.chapter.content.required'),
            'publish_at.after' => __('story::validation.chapter.publish_at.after'),
            'blocks.required' => __('story::validation.chapter.blocks.required'),
            'blocks.min' => __('story::validation.chapter.blocks.required'),
        ];
    }
}
