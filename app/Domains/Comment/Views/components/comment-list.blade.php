<div class="space-y-4" id="comment-list">
  @if($error === 'not_allowed')
    <div class="p-3 rounded border border-yellow-300 bg-yellow-50 text-yellow-900 text-sm flex items-center justify-between gap-4">
      <div>{{ __('comment::comments.errors.members_only') }}</div>
      @if($isGuest)
        <a href="{{ url('/auth/login-intended?redirect=' . urlencode(request()->fullUrl())) }}" class="px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700">
          {{ __('comment::comments.actions.login') }}
        </a>
      @endif
    </div>
  @endif

  @if(!$error)
    <form method="POST" action="{{ route('comments.store') }}" class="mt-4 space-y-2">
      @csrf
      <input type="hidden" name="entity_type" value="{{ $entityType }}">
      <input type="hidden" name="entity_id" value="{{ $entityId }}">
      <x-shared::editor id="comment-body-editor" name="body" :nbLines="5" class="mt-1 block w-full" defaultValue="{{ old('body') }}" placeholder="{{ __('comment::comments.form.body.placeholder') }}" />
      @error('body')
        <div class="text-sm text-red-600">{{ $message }}</div>
      @enderror
      <div>
        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded">{{ __('comment::comments.form.submit') }}</button>
      </div>
    </form>

    @if($list->total === 0)
      <p class="text-sm text-gray-500">{{ __('comment::comments.list.empty') }}</p>
    @else
      <ul class="space-y-3">
        @foreach($list->items as $comment)
          <li class="border border-gray-200 rounded p-3">
            <div class="text-sm text-gray-700">{{ $comment->body }}</div>
            @if($comment->authorProfile && $comment->authorProfile->display_name)
              <div class="mt-1 text-xs text-gray-500">— {{ $comment->authorProfile->display_name }}</div>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  @endif
</div>