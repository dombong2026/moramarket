@extends('layouts.site')
@section('title',$policy->title)
@section('content')
<div class="container page-wrap"><div class="article-layout"><p class="eyebrow">POLICY · Updated {{ $policy->updated_at->format('d M Y') }}</p><h1 class="article-title">{{ $policy->title }}</h1>
<article class="article-body">{!! $policy->body_html !!}</article>
<div style="margin-top:28px"><a class="btn btn-outline" href="{{ route('policies.index') }}">Back to Policies</a></div></div></div>
@endsection
