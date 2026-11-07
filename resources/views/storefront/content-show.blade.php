@extends('layouts.site')
@section('title',$entry->seo_title ?: $entry->title)
@section('meta_description',$entry->seo_description ?: ($entry->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($entry->body_html),150)))
@section('content')
<div class="container page-wrap"><div class="article-layout">
<p class="eyebrow">{{ $type==='post'?'BLOG':'PAGE' }} · {{ $entry->published_at?->format('d M Y') }}</p>
<h1 class="article-title">{{ $entry->title }}</h1>
@if($entry->excerpt)<p class="article-excerpt">{{ $entry->excerpt }}</p>@endif
@if($entry->featured_type==='color' && $entry->featured_color)
<div class="article-cover" style="background:{{ $entry->featured_color }}"></div>
@elseif($entry->featured_type==='image' && $entry->featuredMedia)
<img class="article-cover" src="{{ route('content.featured',['type'=>$type,'entry'=>$entry]) }}" alt="{{ $entry->featuredMedia->alt_text ?: $entry->title }}">
@elseif($entry->featured_type==='video' && $entry->featuredMedia)
<video class="article-cover" controls preload="metadata" playsinline><source src="{{ route('content.featured',['type'=>$type,'entry'=>$entry]) }}" type="{{ $entry->featuredMedia->mime_type }}">Your browser does not support the video.</video>
@elseif($entry->featured_type==='youtube' && $entry->featured_youtube_id)
<div class="article-video"><iframe loading="lazy" title="{{ $entry->title }}" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen src="https://www.youtube-nocookie.com/embed/{{ $entry->featured_youtube_id }}"></iframe></div>
@endif
<article class="article-body">{!! $entry->body_html !!}</article>
<div style="margin-top:32px"><a class="btn btn-outline" href="{{ $type==='post'?route('content.blog',app()->getLocale()):route('content.pages',app()->getLocale()) }}">Back to {{ $type==='post'?'blog':'pages' }}</a></div>
</div></div>
@endsection
