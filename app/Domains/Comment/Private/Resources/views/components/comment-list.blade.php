<div class="space-y-4" id="comment-list" x-data="commentList({
  url: '{{ route('comments.fragments') }}',
  entityType: '{{ $entityType }}',
  entityId: {{ (int) $entityId }},
  page: {{ (int) ($list->page ?? 1) }},
  perPage: {{ (int) ($list->perPage ?? 20) }},
  hasMore: {{ ($list->total > count($list->items)) ? 'true' : 'false' }},
})">
  @push('styles')
    <style>
      /* Comment body blockquotes (applies to fragments appended later) */
      .comment-body blockquote {
        border-left: 3px solid #e5e7eb; /* gray-200 */
        padding-left: 0.75rem; /* pl-3 */
        margin: 0.75rem 0; /* my-3 */
        font-style: normal; /* no italics */
        color: #374151; /* gray-700 */
      }
    </style>
  @endpush
  @if($error === 'not_allowed')
    <div class="p-3 rounded border border-yellow-300 bg-yellow-50 text-yellow-900 text-sm flex items-center justify-between gap-4">
      <div>{{ __('comment::comments.errors.members_only') }}</div>
      @if($isGuest)
        <a href="{{ url('/auth/login-intended?redirect=' . urlencode(request()->fullUrl() . '#comments')) }}">
          <x-shared::button color="accent">
            {{ __('comment::comments.actions.login') }}
          </x-shared::button>
        </a>
      @endif
    </div>
  @endif

  @if(!$error)
    @if(($list->config?->canCreateRoot) ?? false)
    <form
      method="POST"
      action="{{ route('comments.store') }}"
      class="mt-4 space-y-2"
      x-data="{ editorValid: {{ $list->config?->minRootCommentLength ? 'false' : 'true' }} }"
      @editor-valid.window="if($event.detail.id==='comment-body-editor'){ editorValid = $event.detail.valid }"
    >
      @csrf
      <input type="hidden" name="entity_type" value="{{ $entityType }}">
      <input type="hidden" name="entity_id" value="{{ $entityId }}">
      <x-shared::editor
        id="comment-body-editor"
        name="body"
        :nbLines="10"
        class="mt-1 block w-full"
        defaultValue="{{ old('body') }}"
        placeholder="{{ __('comment::comments.form.body.placeholder') }}"
        :min="$list->config?->minRootCommentLength"
        :max="$list->config?->maxRootCommentLength"
        isMandatory="true"
      />
      @error('body')
        <div class="text-sm text-red-600">{{ $message }}</div>
      @enderror
      <div>
        <x-shared::button type="submit" x-bind:disabled="!editorValid" color="accent">{{ __('comment::comments.form.submit') }}</x-shared::button>
      </div>
    </form>
    @endif

    @if($list->total === 0)
      <p class="text-sm text-gray-500">{{ __('comment::comments.list.empty') }}</p>
    @else
      <ul class="space-y-3" x-ref="list">
        @foreach($list->items as $comment)
          @include('comment::components.partials.comment-item', ['comment' => $comment, 'config' => $list->config, 'isModerator' => $isModerator])
        @endforeach
      </ul>
      <div class="mt-3 text-sm text-gray-500" x-show="loading">Loading…</div>
      <div class="h-1" x-ref="sentinel" x-intersect="loadMore()"></div>
    @endif
  @endif
</div>

@once
  @if(!$isGuest)
    @push('scripts')
      @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
  @endif
@endonce
@push('scripts')
<script>
  window.commentList = function(opts){
    return {
      url: opts.url,
      entityType: opts.entityType,
      entityId: opts.entityId,
      page: opts.page,
      perPage: opts.perPage,
      hasMore: !!opts.hasMore,
      loading: false,
      activeReplyId: null,
      activeEditId: null,
      init(){
        // Focus on target comment if specified
        @if($targetCommentId)
          this.$nextTick(() => {
            const targetElement = document.getElementById('comment-{{ $targetCommentId }}');
            if (targetElement) {
              // Add highlight effect
              targetElement.classList.add('ring-2', 'ring-accent', 'ring-opacity-50');
              
              // Scroll to the comment
              targetElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
              });
              
              // Remove highlight after 3 seconds
              setTimeout(() => {
                targetElement.classList.remove('ring-2', 'ring-accent', 'ring-opacity-50');
              }, 3000);
            }
          });
        @endif
        
        // Delegated events for reply UI (demo only)
        this.$el.addEventListener('click', (e) => {
          const replyBtn = e.target.closest('[data-action="reply"]');
          const cancelBtn = e.target.closest('[data-action="cancel-reply"]');
          const editBtn = e.target.closest('[data-action="edit"]');
          const cancelEditBtn = e.target.closest('[data-action="cancel-edit"]');
          if (replyBtn) {
            const id = parseInt(replyBtn.getAttribute('data-comment-id'), 10);
            this.activeReplyId = (this.activeReplyId === id) ? null : id;
            // Close edit when opening a reply
            if (this.activeReplyId !== null) this.activeEditId = null;
          }
          if (cancelBtn) {
            this.activeReplyId = null;
          }
          if (editBtn) {
            const id = parseInt(editBtn.getAttribute('data-comment-id'), 10);
            this.activeEditId = (this.activeEditId === id) ? null : id;
            // Close reply when opening edit
            if (this.activeEditId !== null) this.activeReplyId = null;
            // Try initializing the edit editor after it becomes visible
            const editorId = `edit-editor-${id}`;
            try {
              if (this.activeEditId && window.initQuillEditor) {
                requestAnimationFrame(() => {
                  requestAnimationFrame(() => { window.initQuillEditor(editorId); });
                });
              }
            } catch (e) { /* no-op */ }
          }
          if (cancelEditBtn) {
            this.activeEditId = null;
          }
        });
      },
      async loadMore(){
        if (this.loading || !this.hasMore) return;
        this.loading = true;
        try {
          const url = new URL(this.url, window.location.origin);
          url.searchParams.set('entity_type', this.entityType);
          url.searchParams.set('entity_id', this.entityId);
          url.searchParams.set('page', this.page + 1);
          url.searchParams.set('per_page', this.perPage);
          const res = await fetch(url.toString(), { headers: { 'Accept': 'text/html' } });
          if (res.status === 401) { this.hasMore = false; return; }
          if (!res.ok) { this.hasMore = false; return; }
          const html = await res.text();
          this.$refs.list.insertAdjacentHTML('beforeend', html);
          // Initialize any reply editors in the newly appended HTML
          try {
            if (window.initQuillEditor) {
              // Defer 2 animation frames to let Alpine bind listeners after DOM insertion
              requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                  const containers = this.$refs.list.querySelectorAll('[id^="reply-editor-"]');
                  containers.forEach((el) => {
                    window.initQuillEditor(el.id);
                  });
                });
              });
            }
          } catch (e) {
            // no-op: editor initialization failures should not block pagination
          }
          const next = res.headers.get('X-Next-Page') || res.headers.get('x-next-page');
          if (next) {
            this.page = parseInt(next, 10) - 1; // we increment below
          } else {
            this.hasMore = false;
          }
        } finally {
          this.loading = false;
        }
      }
    }
  }
</script>
@endpush