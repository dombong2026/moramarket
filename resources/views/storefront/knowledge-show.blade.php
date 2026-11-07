@extends('layouts.site')
@section('title',$article->title)
@section('content')
<div class="container page-wrap"><div class="article-layout"><p class="eyebrow">{{ $article->category->name }} · Updated {{ $article->updated_at->format('d M Y') }}</p><h1 class="article-title">{{ $article->title }}</h1>
<article class="article-body">{!! $article->body_html !!}</article>
<div style="margin-top:30px"><a class="btn btn-outline" href="{{ route('knowledge.index') }}">Back to Support Center</a></div>
</div></div>
@endsection
