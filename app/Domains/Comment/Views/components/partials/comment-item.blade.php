<li class="p-3">
  @php($config = $config ?? null)
  <div class="flex gap-4">
    <!-- Avatar -->
    <div class="shrink-0">
      @php($avatar = $comment->authorProfile->avatar_url ?? '')
      @if($avatar)
        <img src="{{ $avatar }}" data-fallback="{{ asset('images/default-avatar.svg') }}" onerror="this.src=this.dataset.fallback;this.onerror=null;" alt="{{ $comment->authorProfile->display_name ?? 'User' }}" class="h-12 w-12 rounded-full object-cover" />
      @else
        <img src="{{ asset('images/default-avatar.svg') }}" alt="{{ $comment->authorProfile->display_name ?? 'User' }}" class="h-12 w-12 rounded-full object-cover" />
      @endif
    </div>

    <!-- Content -->
    <div class="flex-1">
      <!-- Header: author + date + edit icon (right) -->
      <div class="flex items-center gap-3">
        <div class="font-semibold text-gray-800">
          <a href="{{ route('profile.show', ['profile' => $comment->authorProfile->slug]) }}" class="hover:text-gray-600">{{ $comment->authorProfile->display_name ?: '—' }}</a>
        </div>
        @if($comment->canEditOwn && Auth::check() && Auth::id() === $comment->authorId)
          <button type="button" class="text-gray-400 hover:text-gray-600" data-action="edit" data-comment-id="{{ $comment->id }}" title="{{ __('comment::comments.actions.edit') }}" aria-label="{{ __('comment::comments.actions.edit') }}">
            <span class="material-symbols-outlined text-[16px] leading-none">edit</span>
          </button>
        @endif
        <div class="text-xs text-gray-500">
          {{ __('comment::comments.posted_at') }}
          {{ \Carbon\Carbon::parse($comment->createdAt)->translatedFormat('d/m/Y') }}
        </div>
      </div>

      <!-- Body or Edit form -->
      <div class="rich-content comment-body mt-3 text-sm text-gray-700" x-show="activeEditId !== {{ $comment->id }}">{!! $comment->body !!}</div>
      @if($comment->canEditOwn && Auth::check() && Auth::id() === $comment->authorId)
        <div class="mt-3" x-show="activeEditId === {{ $comment->id }}">
          <form
            method="POST"
            action="{{ route('comments.update', ['commentId' => $comment->id]) }}"
            class="space-y-2"
            x-data="{ editorValid: false }"
            @editor-valid.window="if($event.detail.id==='edit-editor-{{ $comment->id }}'){ editorValid = $event.detail.valid }"
          >
            @csrf
            @method('PATCH')
            @php($isChild = $isChild ?? false)
            <x-shared::editor
              id="edit-editor-{{ $comment->id }}"
              name="body"
              class="mt-1 block w-full"
              :nbLines="$isChild ? 5 : 10"
              defaultValue="{{ $comment->body }}"
              placeholder="{{ __('comment::comments.form.body.placeholder') }}"
              :min="$isChild ? ($config?->minReplyCommentLength) : ($config?->minRootCommentLength)"
              :max="$isChild ? ($config?->maxReplyCommentLength) : ($config?->maxRootCommentLength)"
              isMandatory="true"
            />
            @error('body')
              <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
            <div class="mt-2 flex gap-2">
              <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded disabled:opacity-50" :disabled="!editorValid">{{ __('comment::comments.actions.save') }}</button>
              <button type="button" class="px-3 py-1.5 bg-gray-200 text-gray-800 rounded" data-action="cancel-edit" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.cancel') }}</button>
            </div>
          </form>
        </div>
      @endif

      <!-- Actions -->
      <div class="mt-3 flex gap-4 text-xs text-gray-500">
        @php($isChild = $isChild ?? false)
        @php($hasChildren = !empty($comment->children))
        @if($comment->canReply && (((!$isChild && !$hasChildren) || ($isLastChild??false))))
          <button type="button" class="hover:text-gray-700" data-action="reply" data-comment-id="{{ $isChild ? $parentCommentId : $comment->id }}">{{ __('comment::comments.actions.reply') }}</button>
        @endif
      </div>

      <!-- Reply form -->
      <!-- Comments are mandatory, so editor is never valid at start-->
      @if($comment->canReply && (((!$isChild && !$hasChildren) || ($isLastChild??false))))
      @php($replyId = $isChild ? $parentCommentId : $comment->id)
        <div class="mt-3" x-show="activeReplyId === {{ $replyId }} && activeEditId === null">
          <form
            method="POST"
            action="{{ route('comments.store') }}"
            class="space-y-2"
            x-data="{ editorValid: false }"
            @editor-valid.window="if($event.detail.id==='reply-editor-{{ $replyId }}'){ editorValid = $event.detail.valid }"
          >
            @csrf
            <input type="hidden" name="entity_type" value="{{ $comment->entityType }}">
            <input type="hidden" name="entity_id" value="{{ $comment->entityId }}">
            <input type="hidden" name="parent_comment_id" value="{{ $replyId }}">
            <x-shared::editor
              id="reply-editor-{{ $replyId }}"
              name="body"
              class="mt-1 block w-full"
              placeholder="{{ __('comment::comments.form.body.placeholder') }}"
              :min="$config?->minReplyCommentLength"
              :max="$config?->maxReplyCommentLength"
              isMandatory="true"
            />
            @error('body')
              <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
            <div class="mt-2 flex gap-2">
              <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded disabled:opacity-50" :disabled="!editorValid">{{ __('comment::comments.form.submit') }}</button>
              <button type="button" class="px-3 py-1.5 bg-gray-200 text-gray-800 rounded" data-action="cancel-reply" data-comment-id="{{ $comment->id }}">Annuler</button>
            </div>
          </form>
        </div>
      @endif

      <!-- Children -->
      @if(!empty($comment->children))
        <ul class="mt-6 space-y-6 border-l pl-6">
          @foreach($comment->children as $child)
            @include('comment::components.partials.comment-item', ['comment' => $child, 'isChild' => true, 'isLastChild' => $loop->last, 'parentCommentId' => $comment->id, 'config' => $config])
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</li>
