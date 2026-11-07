<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title') · Moramarket</title>
<script>document.documentElement.dataset.theme=localStorage.getItem('mm-theme')==='dark'?'dark':'light';</script>
@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="auth-body"><main class="auth-wrap"><div class="auth-logo"><a class="brand" href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}"><span class="brand-mark">@include('partials.icon',['name'=>'store','size'=>24])</span><span>Mora<span class="brand-orange">market</span></span></a></div><section class="auth-panel"><h1>@yield('title')</h1>@yield('content')</section><a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}" class="auth-home">Back to Marketplace</a></main>@include('partials.notices')</body></html>
