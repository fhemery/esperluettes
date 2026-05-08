@php
    $faqQuestion = $faqQuestion ?? null;
    $isEdit = $faqQuestion !== null;
@endphp

<div class="flex flex-col gap-6"
     x-data="slugForm('{{ old('question', $faqQuestion?->question ?? '') }}', '{{ old('slug', $faqQuestion?->slug ?? '') }}', {{ $isEdit ? 'true' : 'false' }})">

    <div>
        <x-shared::input-label for="faq_category_id" :required="true">
            {{ __('faq::admin.questions.form.category') }}
        </x-shared::input-label>
        <select
            id="faq_category_id"
            name="faq_category_id"
            class="mt-1 block w-full rounded-md border-border bg-surface-read text-on-surface"
            required
        >
            <option value="">—</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ old('faq_category_id', $faqQuestion?->faq_category_id) == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <x-shared::input-error :messages="$errors->get('faq_category_id')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="question" :required="true">
            {{ __('faq::admin.questions.form.question') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="question"
            name="question"
            class="mt-1 block w-full"
            x-model="name"
            @blur="generateSlug()"
            required
            maxlength="255"
        />
        <x-shared::input-error :messages="$errors->get('question')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="slug" :required="true">
            {{ __('faq::admin.questions.form.slug') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="slug"
            name="slug"
            class="mt-1 block w-full font-mono"
            x-model="slug"
            @input="slugManuallyEdited = true"
            required
            maxlength="255"
            pattern="[a-z0-9\-]+"
        />
        <p class="text-xs text-fg/60 mt-1">{{ __('faq::admin.questions.form.slug_help') }}</p>
        <x-shared::input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div>
        <x-shared::input-label for="answer" :required="true">
            {{ __('faq::admin.questions.form.answer') }}
        </x-shared::input-label>
        <x-shared::editor
            name="answer"
            id="answer"
            :defaultValue="old('answer', $faqQuestion?->answer ?? '')"
            :nbLines="10"
            :isMandatory="true"
            :resizable="true"
            :withHeadings="true"
            :withLinks="true"
            class="mt-1"
        />
        <x-shared::input-error :messages="$errors->get('answer')" class="mt-1" />
    </div>

    <div>
        <x-shared::image-upload
            name="image"
            id="image"
            :currentPath="$faqQuestion?->image_path"
            :label="__('faq::admin.questions.form.image')"
            :helpText="__('shared::image-upload.max_size', ['size' => 2])"
        />
    </div>

    <div>
        <x-shared::input-label for="image_alt_text">
            {{ __('faq::admin.questions.form.image_alt_text') }}
        </x-shared::input-label>
        <x-shared::text-input
            type="text"
            id="image_alt_text"
            name="image_alt_text"
            class="mt-1 block w-full"
            :value="old('image_alt_text', $faqQuestion?->image_alt_text ?? '')"
            maxlength="255"
        />
        <x-shared::input-error :messages="$errors->get('image_alt_text')" class="mt-1" />
    </div>

    <div>
        <x-shared::toggle
            name="is_active"
            :checked="old('is_active', $faqQuestion?->is_active ?? true)"
            :label="__('faq::admin.questions.form.is_active')"
        />
        <x-shared::input-error :messages="$errors->get('is_active')" class="mt-1" />
    </div>

    <div class="flex gap-4">
        <x-shared::button type="submit" color="primary" icon="save">
            {{ $isEdit ? __('faq::admin.questions.form.update') : __('faq::admin.questions.form.create') }}
        </x-shared::button>
        <a href="{{ route('faq.admin.faq-questions.index') }}">
            <x-shared::button type="button" color="secondary">
                {{ __('faq::admin.questions.form.cancel') }}
            </x-shared::button>
        </a>
    </div>
</div>
