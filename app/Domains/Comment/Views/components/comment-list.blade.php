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
      <x-shared::editor id="comment-body-editor" name="body" :nbLines="10" class="mt-1 block w-full" defaultValue="{{ old('body') }}" placeholder="{{ __('comment::comments.form.body.placeholder') }}" />
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
      <ul class="space-y-3" x-ref="list">
        @foreach($list->items as $comment)
          @include('comment::components.partials.comment-item', ['comment' => $comment])
        @endforeach
      </ul>
      <div class="mt-3 text-sm text-gray-500" x-show="loading">Loading…</div>
      <div class="h-1" x-ref="sentinel"></div>
    @endif
  @endif
</div>

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
      init(){
        // Delegated events for reply UI (demo only)
        this.$el.addEventListener('click', (e) => {
          const replyBtn = e.target.closest('[data-action="reply"]');
          const cancelBtn = e.target.closest('[data-action="cancel-reply"]');
          if (replyBtn) {
            const id = parseInt(replyBtn.getAttribute('data-comment-id'), 10);
            this.activeReplyId = (this.activeReplyId === id) ? null : id;
          }
          if (cancelBtn) {
            this.activeReplyId = null;
          }
        });

        if (!this.hasMore) return;
        const io = new IntersectionObserver((entries) => {
          entries.forEach(e => { if (e.isIntersecting) this.loadMore(); });
        }, { rootMargin: '200px 0px' });
        io.observe(this.$refs.sentinel);
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
          const next = res.headers.get('X-Next-Page');
          if (next) {
            this.page = parseInt(next, 10) - 1; // we increment below
          } else {
            this.hasMore = false;
          }
          this.page = this.page + 1;
        } finally {
          this.loading = false;
        }
      }
    }
  }
</script>
@endpush