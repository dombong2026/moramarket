<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · Moramarket</title>
<script>document.documentElement.dataset.theme=localStorage.getItem('mm-theme')==='dark'?'dark':'light';</script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="workspace-body">
@php($scope = $workspace ?? (request()->is('dashboard*') ? 'admin' : (request()->is('vendor*') ? 'vendor' : 'account')))
<div class="workspace-shell">
<aside class="workspace-sidebar" id="workspace-sidebar">
  <a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}" class="brand sidebar-brand"><span class="brand-mark">@include('partials.icon',['name'=>'store','size'=>22])</span><span>Mora<span class="brand-orange">market</span></span></a>
  <div class="sidebar-eyebrow">{{ strtoupper($scope) }} WORKSPACE</div>
  <nav class="workspace-nav" aria-label="Workspace navigation">
  @if($scope==='admin')
    <a @class(['active'=>request()->routeIs('admin.home')]) href="{{ route('admin.home') }}">@include('partials.icon',['name'=>'grid']) Overview</a>
    <a @class(['active'=>request()->routeIs('admin.categories*')]) href="{{ route('admin.categories') }}">@include('partials.icon',['name'=>'tag']) Categories</a>
    <a @class(['active'=>request()->routeIs('admin.vendors*')]) href="{{ route('admin.vendors') }}">@include('partials.icon',['name'=>'store']) Vendors</a>
    <a @class(['active'=>request()->routeIs('admin.orders*')]) href="{{ route('admin.orders') }}">@include('partials.icon',['name'=>'orders']) Orders</a>
    <a @class(['active'=>request()->routeIs('admin.media*')]) href="{{ route('admin.media') }}">@include('partials.icon',['name'=>'box']) Media Library</a>
    <a @class(['active'=>request()->routeIs('admin.content.*') && request()->route('type')==='page']) href="{{ route('admin.content.index','page') }}">@include('partials.icon',['name'=>'edit']) Page Manager</a>
    <a @class(['active'=>request()->routeIs('admin.content.*') && request()->route('type')==='post']) href="{{ route('admin.content.index','post') }}">@include('partials.icon',['name'=>'edit']) Post Manager</a>
    <a @class(['active'=>request()->routeIs('admin.knowledge.*')]) href="{{ route('admin.knowledge.index') }}">@include('partials.icon',['name'=>'orders']) Knowledge Base</a>
    <a @class(['active'=>request()->routeIs('admin.policies.*')]) href="{{ route('admin.policies.index') }}">@include('partials.icon',['name'=>'shield']) Policy Center</a>
    <a @class(['active'=>request()->routeIs('admin.support.*')]) href="{{ route('admin.support.index') }}">@include('partials.icon',['name'=>'mail']) Support Center</a>
    <a @class(['active'=>request()->routeIs('admin.appearance*')]) href="{{ route('admin.appearance') }}">@include('partials.icon',['name'=>'sun']) Appearance</a>
    <a @class(['active'=>request()->routeIs('admin.settings*')]) href="{{ route('admin.settings') }}">@include('partials.icon',['name'=>'edit']) General Settings</a>
    <a @class(['active'=>request()->routeIs('admin.health')]) href="{{ route('admin.health') }}">@include('partials.icon',['name'=>'shield']) System Health</a>
  @elseif($scope==='vendor')
    <a @class(['active'=>request()->routeIs('vendor.home')]) href="{{ route('vendor.home') }}">@include('partials.icon',['name'=>'grid']) Overview</a>
    <a @class(['active'=>request()->routeIs('vendor.shipping*')]) href="{{ route('vendor.shipping.edit') }}">@include('partials.icon',['name'=>'store']) Delivery Settings</a>
    <a @class(['active'=>request()->routeIs('vendor.products*')]) href="{{ route('vendor.products') }}">@include('partials.icon',['name'=>'box']) My Products</a>
    <a @class(['active'=>request()->routeIs('vendor.orders*')]) href="{{ route('vendor.orders') }}">@include('partials.icon',['name'=>'orders']) Store Orders</a>
    <a @class(['active'=>request()->routeIs('vendor.media*')]) href="{{ route('vendor.media') }}">@include('partials.icon',['name'=>'box']) Media Library</a>
  @else
    <a @class(['active'=>request()->routeIs('account.home')]) href="{{ route('account.home') }}">@include('partials.icon',['name'=>'grid']) Overview</a>
    <a @class(['active'=>request()->routeIs('account.orders')]) href="{{ route('account.orders') }}">@include('partials.icon',['name'=>'orders']) My Orders</a>
    <a @class(['active'=>request()->routeIs('account.support.*')]) href="{{ route('account.support.index') }}">@include('partials.icon',['name'=>'mail']) Support Tickets</a>
  @endif
  </nav>
  <div class="sidebar-bottom"><a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}">@include('partials.icon',['name'=>'store']) View Marketplace</a><a href="{{ route('account.home') }}">@include('partials.icon',['name'=>'user']) My Account</a></div>
</aside>
<div class="workspace-main">
<header class="workspace-topbar">
 <button type="button" class="mobile-sidebar-button" data-sidebar-toggle aria-label="Toggle navigation">@include('partials.icon',['name'=>'menu'])</button>
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
