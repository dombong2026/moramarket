<div class="table-footer">
 <span>Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}</span>
 <div class="pagination-row">
  @if($paginator->onFirstPage())<span class="btn btn-outline btn-sm" aria-disabled="true">Previous</span>@else<a class="btn btn-outline btn-sm" href="{{ $paginator->previousPageUrl() }}">Previous</a>@endif
  <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
  @if($paginator->hasMorePages())<a class="btn btn-outline btn-sm" href="{{ $paginator->nextPageUrl() }}">Next</a>@else<span class="btn btn-outline btn-sm" aria-disabled="true">Next</span>@endif
 </div>
 <form method="GET" action="{{ url()->current() }}" class="goto-form" data-goto-form><label for="goto-page">Go To</label><input id="goto-page" name="page" type="number" class="field" min="1" max="{{ $paginator->lastPage() }}" value="{{ $paginator->currentPage() }}" required>@foreach(request()->except('page') as $key=>$value)@if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach<button type="submit" class="btn btn-outline btn-sm">Go</button></form>
</div>
