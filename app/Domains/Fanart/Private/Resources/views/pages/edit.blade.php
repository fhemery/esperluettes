@php
    $isAudio = $fanart->kind === \App\Domains\Fanart\Private\Support\FanartKind::Audio;
@endphp
<x-app-layout size="md">
    <div class="w-full flex-1 flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-4">
        <x-shared::title>{{ __('fanart::ui.edit.heading', ['title' => $fanart->title]) }}</x-shared::title>

        <form action="{{ route('fanart.update', $fanart->id) }}" method="POST" enctype="multipart/form-data" novalidate
              class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            @if ($isAudio)
                <div>
                    <x-media::sound-field
                        name="sound"
                        :label="__('fanart::ui.create.sound_label')"
                        :max-size="15360"
                        accept="audio/mpeg"
                        :help-text="__('fanart::ui.edit.replace_sound_help')"
                    />
                    <x-shared::input-error :messages="$errors->get('sound')" />
                </div>
            @else
                <div>
                    <x-media::image-field
                        name="image"
                        :scope="'fanart/' . $story->id"
                        :allow-library="false"
                        :show-alt="false"
                        :show-caption="false"
                        :needs-property-confirm="false"
                        :max-size="2048"
                        accept="image/jpeg,image/png,image/webp"
                        :preview-url="route('fanart.thumb', [$fanart->id, 400])"
                        :label="__('fanart::ui.create.image_label')"
                        :help-text="__('fanart::ui.edit.replace_image_help')"
                    />
                    <x-shared::input-error :messages="$errors->get('image.file')" />
                </div>
            @endif

            <div>
                <x-shared::input-label for="fanart-title" :value="__('fanart::ui.create.title_label')" />
                <x-shared::text-input id="fanart-title" name="title" type="text" maxlength="100" class="w-full"
                                      :value="old('title', $fanart->title)" required />
                <x-shared::input-error :messages="$errors->get('title')" />
            </div>

            <div>
                <x-shared::input-label for="fanart-description" :value="__('fanart::ui.create.description_label')" />
                <x-editor::rich-text
                    id="fanart-description"
                    name="description"
                    toolbar="inline"
                    :max="1000"
                    :nbLines="6"
                    :defaultValue="old('description', $fanart->description ?? '')"
                />
                <x-shared::input-error :messages="$errors->get('description')" />
            </div>

            <div>
                <x-shared::input-label for="fanart-chapter" :value="__('fanart::ui.create.chapter_label')" />
                <select id="fanart-chapter" name="chapter_id"
                        class="w-full border-accent surface-read text-on-surface">
                    <option value="">
                        {{ $isAudio ? __('fanart::ui.create.chapter_choose') : __('fanart::ui.create.chapter_whole_work') }}
                    </option>
                    @foreach ($chapters as $chapter)
                        <option value="{{ $chapter['id'] }}"
                                @selected((string) old('chapter_id', $fanart->chapter_id) === (string) $chapter['id'])>
                            {{ $chapter['name'] }}
                        </option>
                    @endforeach
                </select>
                <x-shared::input-error :messages="$errors->get('chapter_id')" />
            </div>

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" :outline="true" type="button" onclick="history.back()">
                    {{ __('fanart::ui.create.cancel') }}
                </x-shared::button>
                <x-shared::button color="accent" type="submit">
                    {{ __('fanart::ui.edit.submit') }}
                </x-shared::button>
            </div>
        </form>
    </div>
</x-app-layout>
