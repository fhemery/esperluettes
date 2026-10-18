<x-app-layout size="md">
    <div class="w-full flex-1 flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-4"
         data-allowed-kinds="{{ implode(',', $allowedKinds) }}">
        <x-shared::title>{{ __('fanart::ui.create.heading', ['story' => $story->title]) }}</x-shared::title>

        <form action="{{ route('fanart.store', $story->slug) }}" method="POST" enctype="multipart/form-data" novalidate
              class="flex flex-col gap-4"
              x-data="{ kind: @js(old('kind', $allowedKinds[0])) }">
            @csrf
            <input type="hidden" name="kind" :value="kind">

            @if (count($allowedKinds) > 1)
                <div class="flex gap-2" role="group" aria-label="{{ __('fanart::ui.create.kind_label') }}">
                    @foreach ($allowedKinds as $allowed)
                        <button type="button"
                                class="px-4 py-2 border border-accent rounded"
                                :class="kind === '{{ $allowed }}' ? 'bg-accent text-on-accent' : ''"
                                :aria-pressed="kind === '{{ $allowed }}'"
                                @click="kind = '{{ $allowed }}'">
                            {{ __('fanart::ui.create.kind_' . $allowed) }}
                        </button>
                    @endforeach
                </div>
            @endif
            <x-shared::input-error :messages="$errors->get('kind')" />

            @if (in_array('image', $allowedKinds, true))
                <div x-show="kind === 'image'" x-cloak>
                    <x-media::image-field
                        name="image"
                        :scope="'fanart/' . $story->id"
                        :allow-library="false"
                        :show-alt="false"
                        :show-caption="false"
                        :needs-property-confirm="false"
                        :max-size="2048"
                        accept="image/jpeg,image/png,image/webp"
                        :label="__('fanart::ui.create.image_label')"
                        :help-text="__('fanart::ui.create.image_help')"
                    />
                    <x-shared::input-error :messages="$errors->get('image.file')" />
                </div>
            @endif

            @if (in_array('audio', $allowedKinds, true))
                <div x-show="kind === 'audio'" x-cloak>
                    <x-media::sound-field
                        name="sound"
                        :label="__('fanart::ui.create.sound_label')"
                        :max-size="15360"
                        accept="audio/mpeg"
                        :help-text="__('fanart::ui.create.sound_help')"
                    />
                    <x-shared::input-error :messages="$errors->get('sound')" />
                </div>
            @endif

            <div>
                <x-shared::input-label for="fanart-title" :value="__('fanart::ui.create.title_label')" />
                <x-shared::text-input id="fanart-title" name="title" type="text" maxlength="100" class="w-full"
                                      :value="old('title')" required />
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
                />
                <x-shared::input-error :messages="$errors->get('description')" />
            </div>

            <div>
                <x-shared::input-label for="fanart-chapter" :value="__('fanart::ui.create.chapter_label')" />
                <select id="fanart-chapter" name="chapter_id"
                        class="w-full border-accent surface-read text-on-surface">
                    <option value=""
                            x-text="kind === 'image' ? @js(__('fanart::ui.create.chapter_whole_work')) : @js(__('fanart::ui.create.chapter_choose'))">
                        {{ __('fanart::ui.create.chapter_whole_work') }}
                    </option>
                    @foreach ($chapters as $chapter)
                        <option value="{{ $chapter['id'] }}" @selected((string) old('chapter_id') === (string) $chapter['id'])>
                            {{ $chapter['name'] }}
                        </option>
                    @endforeach
                </select>
                <x-shared::input-error :messages="$errors->get('chapter_id')" />
            </div>

            <div>
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="attestation" value="1" class="mt-1" @checked(old('attestation')) required>
                    <span>{{ __('fanart::ui.create.attestation') }}</span>
                </label>
                <x-shared::input-error :messages="$errors->get('attestation')" />
            </div>

            @if (in_array('image', $allowedKinds, true))
                <label class="flex items-center gap-2" x-show="kind === 'image'">
                    <input type="checkbox" name="share_on_discord" value="1" @checked(old('share_on_discord'))>
                    <span>{{ __('fanart::ui.create.share_on_discord') }}</span>
                </label>
            @endif

            <div class="text-sm flex items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                {{ __('fanart::ui.create.pending_notice') }}
            </div>

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" :outline="true" type="button" onclick="history.back()">
                    {{ __('fanart::ui.create.cancel') }}
                </x-shared::button>
                <x-shared::button color="accent" type="submit">
                    {{ __('fanart::ui.create.submit') }}
                </x-shared::button>
            </div>
        </form>
    </div>
</x-app-layout>
