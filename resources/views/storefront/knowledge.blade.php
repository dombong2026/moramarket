@extends('layouts.site')
@section('title','Support Center')
@section('content')
<div class="container page-wrap"><section class="support-hero"><p class="eyebrow">MORAMARKET HELP</p><h1>Support Center</h1><p>Find answers in published help articles or contact the support team.</p>
@auth<a class="btn btn-primary" href="{{ route('account.support.index') }}">My Support Tickets</a>@else<a class="btn btn-primary" href="{{ route('login') }}">Sign in to Contact Support</a>@endauth
</section>
<form class="panel panel-pad" method="GET" action="{{ route('knowledge.index') }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
<div style="flex:2;min-width:170px"><label class="field-label" for="knowledge-q">Search articles</label><input class="field" id="knowledge-q" name="q" value="{{ request('q') }}" maxlength="100"></div>
<div style="flex:1;min-width:150px"><label class="field-label" for="knowledge-category">Category</label><select class="field" id="knowledge-category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('category')===$category->slug)>{{ $category->name }}</option>@endforeach</select></div>
<button class="btn btn-primary" type="submit">Search</button><a class="btn btn-outline" href="{{ route('knowledge.index') }}">Reset</a></form>
<div class="section-heading"><div><h2>Help Articles</h2><p>Published guides and frequently asked questions.</p></div></div>
<div class="article-grid">@forelse($articles as $article)<article class="panel panel-pad"><span class="pill">{{ $article->category->name }}</span><h3 style="font-size:19px;font-weight:750;margin:13px 0"><a href="{{ route('knowledge.show',$article->slug) }}">{{ $article->title }}</a></h3><a class="text-link" href="{{ route('knowledge.show',$article->slug) }}">Read article</a></article>@empty<div class="empty-state"><strong>No matching articles</strong>Try another search or check again after new articles are published.</div>@endforelse</div>
<div style="margin-top:20px">{{ $articles->links() }}</div></div>
@endsection
