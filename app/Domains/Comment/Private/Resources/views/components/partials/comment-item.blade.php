<li class="py-4 sm:px-4 sm:mb-4" id="comment-{{ $comment->id }}">
  @php
  $config = $config ?? null;
  $isUnknown = is_null($comment->authorId);
  $displayName = $isUnknown
    ? __('comment::comments.unknown_user')
    : ($comment->authorProfile->display_name ?: '—');
  $avatar = $comment->authorProfile->avatar_url ?? '';
  @endphp

  <div class="grid grid-cols-2 gap-2 grid-cols-[auto_1fr]">
    <!-- Avatar -->
    <div class="shrink-0">
      @if($avatar)
      <img src="{{ $avatar }}" data-fallback="{{ asset('images/default-avatar.svg') }}" onerror="this.src=this.dataset.fallback;this.onerror=null;" alt="{{ $displayName }}" class="h-8 w-8 sm:h-12 sm:w-12 rounded-full object-cover" />
      @else
      <img src="{{ asset('images/default-avatar.svg') }}" alt="{{ $displayName }}" class="h-6 w-6 sm:h-12 sm:w-12 rounded-full object-cover" />
      @endif
    </div>

    <!-- Header: author + + report + date + edit icon (right) -->
    <div class="flex items-center gap-2 grow">
      <div class="font-semibold text-secondary">
        @if($isUnknown || empty($comment->authorProfile->slug))
        <span class="text-fg/80">{{ $displayName }}</span>
        @else
        <a href="{{ route('profile.show', ['profile' => $comment->authorProfile->slug]) }}" class="hover:text-secondary/80">{{ $displayName }}</a>
        @endif
      </div>
      @if(Auth::id() !== $comment->authorId) 
        <div class="ml-2 flex gap-2">
          <x-moderation::report-button
              topic-key="comment"
              :entity-id="$comment->id"
              :compact="true"
              size="xs" />
          @if($isModerator)
          <x-moderation::moderation-button
              badgeColor="warning"
              position="top"
              id="comment-moderator-btn">
              <x-moderation::action
                  :action="route('comments.moderation.delete', $comment->id)"
                  method="DELETE"
                  :label="__('comment::moderation.delete.label')" />
              <x-moderation::action
                  :action="route('comments.moderation.empty-content', $comment->id)"
                  method="POST"
                  :label="__('comment::moderation.empty_content.label')" />
          </x-moderation::moderation-button>
          @endif
      </div>
      @endif
      
      @if($comment->canEditOwn && Auth::check() && Auth::id() === $comment->authorId)
      <button type="button" class="text-gray-400 hover:text-gray-600" data-action="edit" data-comment-id="{{ $comment->id }}" title="{{ __('comment::comments.actions.edit') }}" aria-label="{{ __('comment::comments.actions.edit') }}">
        <span class="material-symbols-outlined text-[16px] leading-none">edit</span>
      </button>
      @endif
      <div class="text-xs text-gray-500"
        x-data="{ created: new Date('{{ $comment->createdAt }}'),
                   updated: new Date('{{ $comment->updatedAt }}') }">
        <span x-text="DateUtils.formatDate(created)"></span>
        @if($comment->updatedAt !== $comment->createdAt)
        <span class="ml-2 text-gray-400">
          {{ __('comment::comments.updated_at') }}
          <span x-text="DateUtils.formatDateTime(updated)"></span>
        </span>
        @endif
      </div>
    </div>

    <div class="col-span-2">
      <!-- Body or Edit form -->
      <div class="rich-content comment-body text-lg pl-2 sm:pl-6" x-show="activeEditId !== {{ $comment->id }}">
        {!! $comment->body !!}
      </div>


      @if($comment->canEditOwn && Auth::check() && Auth::id() === $comment->authorId)
      <div class="mt-3" x-show="activeEditId === {{ $comment->id }}">
        <form
          method="POST"
          action="{{ route('comments.update', ['commentId' => $comment->id]) }}"
          class="space-y-2"
          x-data="{ editorValid: false }"
          @editor-valid.window="if($event.detail.id==='edit-editor-{{ $comment->id }}'){ editorValid = $event.detail.valid }">
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
            isMandatory="true" />
          @error('body')
          <div class="text-sm text-red-600">{{ $message }}</div>
          @enderror
          <div class="mt-2 flex gap-2">
            <x-shared::button type="submit" color="accent" x-bind:disabled="!editorValid">{{ __('comment::comments.actions.save') }}</x-shared::button>
            <x-shared::button color="neutral" :outline="true" data-action="cancel-edit" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.cancel') }}</x-shared::button>
          </div>
        </form>
      </div>
      @endif

      <!-- Actions -->
      <div class="mt-3 flex gap-4 text-xs text-gray-500">
        @php($isChild = $isChild ?? false)
        @php($hasChildren = !empty($comment->children))
        @if($comment->canReply && (((!$isChild && !$hasChildren) || ($isLastChild??false))))
        <x-shared::button color="tertiary" size="xs" outline="true" data-action="reply" data-comment-id="{{ $isChild ? $parentCommentId : $comment->id }}">{{ __('comment::comments.actions.reply') }}</x-shared::button>
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
          @editor-valid.window="if($event.detail.id==='reply-editor-{{ $replyId }}'){ editorValid = $event.detail.valid }">
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
            isMandatory="true" />
          @error('body')
          <div class="text-sm text-red-600">{{ $message }}</div>
          @enderror
          <div class="mt-2 flex gap-2">
            <x-shared::button type="submit" color="accent" x-bind:disabled="!editorValid">{{ __('comment::comments.actions.reply') }}</x-shared::button>
            <x-shared::button color="neutral" :outline="true" data-action="cancel-reply" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.cancel') }}</x-shared::button>
          </div>
        </form>
      </div>
      @endif

      <!-- Children -->
      @if(!empty($comment->children))
      <ul class="mb-3 border-l-2 border-accent ml-2 sm:ml-6 pl-2 sm:pl-2">
        @foreach($comment->children as $child)
        @include('comment::components.partials.comment-item', ['comment' => $child, 'isChild' => true, 'isLastChild' => $loop->last, 'parentCommentId' => $comment->id, 'config' => $config, 'isModerator' => $isModerator])
        @endforeach
      </ul>
      @endif
    </div>
  </div>
</li>