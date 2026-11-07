@extends('layouts.workspace')
@section('title','General Settings')
@section('subtitle','Manage your marketplace name, tagline, and support contact.')
@section('page_actions')<a class="btn btn-outline" href="{{ route('admin.health') }}">System Health</a>@endsection
@section('content')
<div class="panel panel-pad" style="max-width:750px">
<form method="POST" action="{{ route('admin.settings.update') }}">@csrf @method('PATCH')
<div class="field-block"><label class="field-label" for="site-name">Website Name</label><input class="field" name="site_name" id="site-name" required minlength="2" maxlength="80" value="{{ old('site_name',$settings['site_name'] ?? 'Moramarket') }}"></div>
<div class="field-block"><label class="field-label" for="site-tagline">Marketplace tagline</label><input class="field" name="site_tagline" id="site-tagline" required maxlength="160" value="{{ old('site_tagline',$settings['site_tagline'] ?? 'Discover independent stores, products and services') }}"></div>
<div class="field-block"><label class="field-label" for="support-email">Support email (optional)</label><input class="field" type="email" name="support_email" id="support-email" maxlength="190" value="{{ old('support_email',$settings['support_email'] ?? '') }}"></div>
<div style="display:flex;justify-content:flex-end;margin-top:20px"><button class="btn btn-primary" type="submit">Save Settings</button></div>
</form>
</div>
@endsection
