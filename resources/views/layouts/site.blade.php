<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@php($ui = \App\Models\SiteSetting::query()->pluck('value','key'))
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Moramarket') · {{ $ui->get('site_name','Moramarket') }}</title>
<meta name="description" content="Discover products and services from independent sellers on Moramarket.">
<script>document.documentElement.dataset.theme=localStorage.getItem('mm-theme')==='dark'?'dark':'light';</script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="site-body">
<header class="site-header">
  <div class="topbar"><div class="container topbar-inner"><span>{{ $ui->get('site_tagline','Discover independent stores, products and services') }}</span><a href="{{ route('account.home') }}">My account</a></div></div>
  <div class="container mainbar">
    <a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}" class="brand" aria-label="Moramarket home"><span class="brand-mark">@include('partials.icon',['name'=>'store','size'=>23])</span><span>Mora<span class="brand-orange">market</span></span></a>
    <form action="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}" method="GET" class="site-search" role="search">
      <label class="sr-only" for="market-search">Search products</label><input name="q" id="market-search" value="{{ request('q') }}" placeholder="Search products and stores" maxlength="100">
      <button type="submit" aria-label="Search">@include('partials.icon',['name'=>'search'])</button>
    </form>
    <div class="mainbar-actions">
      <a class="icon-link" href="{{ route('cart.index') }}" aria-label="Shopping cart">@include('partials.icon',['name'=>'cart','size'=>22]) <span class="cart-count">{{ array_sum(session('cart',[])) }}</span></a>
      <details class="profile-menu">
        <summary aria-label="Profile menu">@include('partials.icon',['name'=>'user','size'=>21])</summary>
        <div class="profile-panel">
          @auth
          <div class="profile-name">{{ auth()->user()->name }}<small>{{ auth()->user()->email }}</small></div>
          <a href="{{ route('account.home') }}">My Account</a>
          @if(auth()->user()->isAdmin())<a href="{{ route('admin.home') }}">Admin Dashboard</a>@endif
          @if(auth()->user()->isApprovedVendor())<a href="{{ route('vendor.home') }}">Vendor Dashboard</a>@endif
          <a href="{{ route('account.orders') }}">My Orders</a>
          <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit">Sign out</button></form>
          @else
          <a href="{{ route('login') }}">Sign In</a><a href="{{ route('register') }}">Sign Up</a>
          @endauth
          <button type="button" data-theme-toggle>@include('partials.icon',['name'=>'moon']) <span>Toggle light/dark</span></button>
        </div>
      </details>
    </div>
  </div>
  <div class="subbar"><div class="container subbar-inner">
    <a href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">@include('partials.icon',['name'=>'grid']) All categories</a>
    <a href="{{ route('locale.catalog',['locale'=>app()->getLocale(),'type'=>'physical']) }}">Physical products</a>
    <a href="{{ route('locale.catalog',['locale'=>app()->getLocale(),'type'=>'digital']) }}">Digital products</a>
    <a href="{{ route('locale.catalog',['locale'=>app()->getLocale(),'type'=>'service']) }}">Services</a>
  </div></div>
</header>
<main id="main-content">@yield('content')</main>
<footer class="site-footer">
 <div class="container footer-columns">
  @if(($ui->get('footer_brand_enabled','1')) === '1')<div><h3>{{ $ui->get('site_name','Moramarket') }}</h3><p>{{ $ui->get('site_tagline','A multi-vendor marketplace for products and services.') }}</p></div>@endif
  @if(($ui->get('footer_market_enabled','1')) === '1')<div><h3>Explore</h3><a href="{{ route('locale.catalog',['locale'=>app()->getLocale()]) }}">Browse products</a><a href="{{ route('cart.index') }}">My cart</a></div>@endif
  @if(($ui->get('footer_account_enabled','1')) === '1')<div><h3>Account</h3><a href="{{ route('account.home') }}">Dashboard</a><a href="{{ route('account.orders') }}">Orders</a></div>@endif
  @if(($ui->get('footer_languages_enabled','1')) === '1')<div><h3>Languages</h3><div class="language-links">@foreach(['en'=>'English','zh'=>'中文','ko'=>'한국어','ja'=>'日本語','de'=>'Deutsch','km'=>'ខ្មែរ','vi'=>'Tiếng Việt','th'=>'ไทย','id'=>'Indonesia','ms'=>'Melayu','ar'=>'العربية','fr'=>'Français','es'=>'Español'] as $code=>$name)<a @class(['is-selected'=>app()->getLocale()===$code]) lang="{{ $code }}" href="{{ route('language.choose',['locale'=>$code]) }}">{{ $name }}</a>@endforeach</div></div>@endif
 </div>
 <div class="footer-bottom"><div class="container footer-bottom-inner"><span>Copyright © {{ date('Y') }} · {{ $ui->get('site_name','Moramarket') }}</span><span>Built by <a href="https://tonsamith.com" rel="noopener">Ton Samith</a></span></div></div>
</footer>
@include('partials.notices')
</body></html>
