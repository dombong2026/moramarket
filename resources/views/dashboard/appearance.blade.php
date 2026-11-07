@extends('layouts.workspace')
@section('title','Appearance')
@section('subtitle','Update homepage presentation and select visible footer sections.')
@section('page_actions')<a href="{{ route('locale.home',['locale'=>app()->getLocale()]) }}" class="btn btn-outline">View Storefront</a>@endsection
@section('content')
<div class="panel panel-pad" style="max-width:850px">
<form method="POST" action="{{ route('admin.appearance.update') }}">@csrf @method('PATCH')
<h2 style="font-size:17px;font-weight:780;margin-bottom:14px">Homepage Hero</h2>
<div class="field-block"><label class="field-label" for="hero-heading">Heading</label><input class="field" name="hero_heading" id="hero-heading" required maxlength="160" value="{{ old('hero_heading',$settings['hero_heading'] ?? 'Discover something worth sharing.') }}"></div>
<div class="field-block"><label class="field-label" for="hero-description">Description</label><textarea class="field" rows="4" name="hero_description" id="hero-description" required maxlength="300">{{ old('hero_description',$settings['hero_description'] ?? 'Shop independent sellers, explore everyday essentials, and find new products and services from one marketplace.') }}</textarea></div>
<h2 style="font-size:17px;font-weight:780;margin:24px 0 12px">Footer Columns</h2>
<p class="muted">Enable or disable sections without creating empty footer links.</p>
<div class="form-grid">
@foreach(['footer_brand_enabled'=>'Brand information','footer_market_enabled'=>'Marketplace links','footer_account_enabled'=>'Account links','footer_languages_enabled'=>'Languages'] as $key=>$title)
<div class="panel" style="padding:13px"><label style="display:flex;align-items:center;gap:10px"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$settings[$key] ?? '1') == '1')> <strong>{{ $title }}</strong></label></div>
@endforeach
</div>
<div style="display:flex;justify-content:flex-end;margin-top:22px"><button type="submit" class="btn btn-primary">Save Appearance</button></div>
</form>
</div>
@endsection
