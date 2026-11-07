@extends('layouts.site')
@section('title',$type==='page'?'Pages':'Blog')
@section('content')
<div class="container page-wrap">
<div class="section-heading"><div><h1 style="font-size:30px;font-weight:800">{{ $type==='page'?'Explore Pages':'Moramarket Blog' }}</h1><p>{{ $type==='page'?'Published information pages':'Latest published stories and updates' }}</p></div></div>
<div class="article-grid">
@forelse($entries as $entry)
<article class="panel panel-pad"><span class="product-type">{{ $type==='post'?$entry->published_at?->format('d M Y'):'Page' }}</span>
<h2 style="font-size:21px;font-weight:760;margin:10px 0"><a href="{{ route('content.show',['locale'=>app()->getLocale(),'type'=>$type,'entry'=>$entry->slug]) }}">{{ $entry->title }}</a></h2>
<p class="muted">{{ $entry->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($entry->body_html),130) }}</p>
<a class="text-link" href="{{ route('content.show',['locale'=>app()->getLocale(),'type'=>$type,'entry'=>$entry->slug]) }}">Read more</a></article>
@empty<div class="empty-state"><strong>No published {{ $type==='page'?'pages':'posts' }} yet</strong>Content will appear here when it is published.</div>@endforelse
</div><div style="margin-top:22px">{{ $entries->links() }}</div>
</div>
@endsection
