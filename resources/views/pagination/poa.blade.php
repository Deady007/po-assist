@if ($paginator->hasPages())
  <nav role="navigation" aria-label="Pagination">
    <div class="pagination">
      @if ($paginator->onFirstPage())
        <span class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo;</span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="disabled" aria-disabled="true">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="active" aria-current="page">{{ $page }}</span>
            @else
              <a href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
      @else
        <span class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&rsaquo;</span>
      @endif
    </div>
  </nav>
@endif

