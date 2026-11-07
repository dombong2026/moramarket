@php($tableId = 'table-search-'.substr(md5(url()->current()),0,10))
<div class="table-controls">
    <form method="GET" action="{{ url()->current() }}" role="search" aria-label="Filter table rows">
        <label class="sr-only" for="{{ $tableId }}">Search rows</label>
        <input type="search" id="{{ $tableId }}" name="q" maxlength="100"
            value="{{ request('q') }}" placeholder="Search" autocomplete="off">
        @if(!empty($statuses))
        <label class="sr-only" for="{{ $tableId }}-status">Filter rows by status or type</label>
        <select id="{{ $tableId }}-status" name="status">
            <option value="">All</option>
            @foreach($statuses as $key=>$label)
            <option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>
            @endforeach
        </select>
        @endif
        <button class="btn btn-primary btn-sm" type="submit">@include('partials.icon',['name'=>'search','size'=>16]) Search</button>
        <a class="btn btn-outline btn-sm" href="{{ url()->current() }}">Reset</a>
    </form>
    <span class="muted" aria-label="Rows displayed per page">{{ \App\Support\TableQuery::PAGE_SIZE }} per page</span>
</div>
