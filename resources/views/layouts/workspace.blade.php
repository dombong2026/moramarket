<!doctype html>
<html data-theme="{{ request()->cookie('mm-theme') === 'dark' ? 'dark' : 'light' }}" lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · Moramarket</title>

@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="workspace-body">
@php($scope = $workspace ?? (request()->is('dashboard*') ? 'admin' : (request()->is('vendor*') ? 'vendor' : 'account')))
<div class="workspace-shell">
<aside class="workspace-sidebar" id="workspace-sidebar" aria-label="Dashboard sidebar">
  <a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}" class="brand sidebar-brand"><span class="brand-mark">@include('partials.icon',['name'=>'store','size'=>22])</span><span>Mora<span class="brand-orange">market</span></span></a>
  <div class="sidebar-eyebrow">{{ strtoupper($scope) }} WORKSPACE</div>
  @include('partials.workspace-navigation')
  <div class="sidebar-bottom"><a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}">@include('partials.icon',['name'=>'store']) View Marketplace</a><a href="{{ route('account.home') }}">@include('partials.icon',['name'=>'user']) My Account</a></div>
</aside>
<div class="workspace-main">
<header class="workspace-topbar">
 <button type="button" class="mobile-sidebar-button" data-sidebar-toggle aria-label="Toggle navigation" aria-expanded="false" aria-controls="workspace-sidebar">@include('partials.icon',['name'=>'menu'])</button>
 <div class="topbar-heading"><small>Moramarket / {{ ucfirst($scope) }}</small><strong>@yield('title', 'Overview')</strong></div>
 <div class="topbar-right">
   <button type="button" class="icon-button" data-theme-toggle aria-label="Toggle light and dark mode">@include('partials.icon',['name'=>'moon'])</button>
   <details class="profile-menu"><summary aria-label="Profile menu">@include('partials.icon',['name'=>'user'])</summary><div class="profile-panel"><div class="profile-name">{{ auth()->user()->name }}<small>{{ auth()->user()->email }}</small></div><a href="{{ route('account.home') }}">My Account</a><a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}">Marketplace</a><form method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form></div></details>
 </div>
</header>
<main class="workspace-content">
  <div class="section-header"><div><p class="eyebrow">MORAMARKET</p><h1>@yield('title','Overview')</h1>@hasSection('subtitle')<p class="page-subtitle">@yield('subtitle')</p>@endif</div><div class="section-actions">@yield('page_actions')</div></div>
  @yield('content')
</main>
</div>
</div>
@include('partials.notices')
</body></html>
