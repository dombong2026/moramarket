@extends('layouts.site')
@section('title','Policies')
@section('content')
<div class="container page-wrap"><div class="section-heading"><div><h1 style="font-size:30px;font-weight:800">Policies</h1><p>Published marketplace terms and policies.</p></div></div>
<div class="article-grid">@forelse($policies as $policy)<article class="panel panel-pad"><h2 style="font-size:20px;font-weight:760;margin:0 0 10px">{{ $policy->title }}</h2><a class="text-link" href="{{ route('policies.show',$policy->slug) }}">Read policy</a></article>@empty<div class="empty-state"><strong>No published policies</strong>Published policies will appear here.</div>@endforelse</div>
<div style="margin-top:24px">{{ $policies->links() }}</div></div>
@endsection
