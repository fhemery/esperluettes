<li class="border border-gray-200 rounded p-3">
  <div class="text-sm text-gray-700">{{ $comment->body }}</div>
  @if($comment->authorProfile && $comment->authorProfile->display_name)
    <div class="mt-1 text-xs text-gray-500">— {{ $comment->authorProfile->display_name }}</div>
  @endif
  <div class="mt-2 flex gap-3 text-xs text-gray-500">
    <button type="button" class="hover:text-gray-700" data-action="reply" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.reply') }}</button>
    <button type="button" class="hover:text-gray-700" data-action="edit" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.edit') }}</button>
    <!--<button type="button" class="hover:text-gray-700" data-action="report" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.actions.report') ?? 'Signaler' }}</button>-->
  </div>
  <div class="mt-3 border-l pl-3" x-show="activeReplyId === {{ (int) $comment->id }}">
    <div class="text-xs text-gray-500 mb-2">{{ __('comment::comments.actions.reply') }}</div>
    <x-shared::editor id="reply-editor-{{ $comment->id }}" name="reply_body_{{ $comment->id }}" :nbLines="4" class="mt-1 block w-full" placeholder="{{ __('comment::comments.form.body.placeholder') }}" />
    <div class="mt-2 flex gap-2">
      <button type="button" class="px-3 py-1.5 bg-blue-600 text-white rounded" data-action="submit-reply" data-comment-id="{{ $comment->id }}">{{ __('comment::comments.form.submit') }}</button>
      <button type="button" class="px-3 py-1.5 bg-gray-200 text-gray-800 rounded" data-action="cancel-reply" data-comment-id="{{ $comment->id }}">Annuler</button>
    </div>
  </div>
</li>
