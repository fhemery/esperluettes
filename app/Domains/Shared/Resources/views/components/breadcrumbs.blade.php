<nav class="text-sm text-fg/70" aria-label="{{ __('shared::breadcrumbs.breadcrumb') }}">
  <ol class="flex items-center gap-2">
    @foreach($items as $index => $item)
      @if($index > 0)
        <li class="text-fg/40 select-none">/</li>
      @endif
      <li class="{{ $item->active ? 'text-fg font-medium' : '' }}">
        @if(!$item->active && $item->url)
          <a href="{{ $item->url }}" class="hover:text-fg/90 flex items-center gap-1">
            @if($item->icon)
              <span class="material-symbols-outlined text-base">{{ $item->icon }}</span>
            @endif
            <span>{{ $item->label }}</span>
          </a>
        @else
          <span class="flex items-center gap-1">
            @if($item->icon)
              <span class="material-symbols-outlined text-base">{{ $item->icon }}</span>
            @endif
            <span>{{ $item->label }}</span>
          </span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
