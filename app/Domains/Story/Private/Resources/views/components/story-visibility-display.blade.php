@props([
    'visibility' => 'public', // public | community | private
])

@switch($visibility)
  @case('community')
  <x-shared::badge color="info">{{ __('story::shared.visibility.options.community') }}</x-shared::badge>   
    @break

  @case('private')
  <x-shared::badge color="warning">{{ __('story::shared.visibility.options.private') }}</x-shared::badge>
    @break

  @default
  <x-shared::badge color="success">{{ __('story::shared.visibility.options.public') }}</x-shared::badge>
@endswitch
