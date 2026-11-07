<nav class="table-footer" aria-label="Table pagination">
  <span>Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</span>
  <div class="pagination-row">
    @if($paginator->onFirstPage())
      <span class="btn btn-outline btn-sm" aria-disabled="true">Previous</span>
    @else
      <a class="btn btn-outline btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
    @endif
    <span aria-live="polite">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    @if($paginator->hasMorePages())
      <a class="btn btn-outline btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
    @else
      <span class="btn btn-outline btn-sm" aria-disabled="true">Next</span>
    @endif
  </div>
  <form method="GET" action="{{ url()->current() }}" class="goto-form" data-goto-form>
    @php($goId = 'goto-'.substr(md5(url()->current()),0,10))
    <label for="{{ $goId }}">Go To</label>
    <input id="{{ $goId }}" name="page" type="number" class="field" min="1"
      max="{{ max(1,$paginator->lastPage()) }}" value="{{ $paginator->currentPage() }}" required>
    @foreach(request()->except('page') as $key=>$value)
      @if(is_scalar($value))
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
      @endif
    @endforeach
    <button type="submit" class="btn btn-outline btn-sm">Go</button>
  </form>
</nav>
