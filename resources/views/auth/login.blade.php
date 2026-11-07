@extends('layouts.auth')
@section('title','Sign In')
@section('content')
<p class="auth-subtitle">Access your Moramarket account.</p>
<form method="POST" action="{{ route('login.submit') }}">@csrf
  <div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'mail'])</span><input type="email" name="email" id="login-email" value="{{ old('email') }}" placeholder=" " autocomplete="email" maxlength="190" required><label for="login-email">Email address</label></div>
  <div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'lock'])</span><input type="password" name="password" id="login-password" placeholder=" " autocomplete="current-password" required><label for="login-password">Password</label><button type="button" class="eye-button" data-password-toggle="login-password" aria-label="Show password">@include('partials.icon',['name'=>'eye'])</button></div>
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:16px 0"><label style="display:flex;align-items:center;gap:6px;font-size:12px"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label><a class="text-link" href="{{ route('password.request') }}">Forgot password?</a></div>
  <button class="btn btn-primary" type="submit">Sign In</button>
</form>
<div class="auth-links"><span class="muted">New to Moramarket?</span><a href="{{ route('register') }}">Create account</a></div>
@endsection
