<div class="table-controls">
  <form method="GET" action="{{ url()->current() }}">
    <label class="sr-only" for="table-query">Search</label><input type="search" id="table-query" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Search">
    @if(!empty($statuses))<label class="sr-only" for="table-status">Filter</label><select id="table-status" name="status"><option value="">All statuses</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>@endif
    <button class="btn btn-primary btn-sm" type="submit">Search / Filter</button><a class="btn btn-outline btn-sm" href="{{ url()->current() }}">Reset</a>
  </form>
  <span class="muted">10 per page</span>
</div>
