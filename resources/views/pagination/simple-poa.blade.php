@if ($paginator->hasPages())
  <nav role="navigation" aria-label="Pagination">
    <div class="pagination">
      @if ($paginator->onFirstPage())
        <span class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo; Prev</span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo; Prev</a>
      @endif

      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">Next &rsaquo;</a>
      @else
        <span class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')">Next &rsaquo;</span>
      @endif
    </div>
  </nav>
@endif

