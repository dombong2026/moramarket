@extends('layouts.auth')
@section('title','Create Account')
@section('content')
<p class="auth-subtitle">Start shopping and managing your orders.</p>
<form method="POST" action="{{ route('register.submit') }}">@csrf
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'user'])</span><input type="text" name="name" id="register-name" value="{{ old('name') }}" placeholder=" " maxlength="160" autocomplete="name" required><label for="register-name">Full name</label></div>
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'mail'])</span><input type="email" name="email" id="register-email" value="{{ old('email') }}" placeholder=" " maxlength="190" autocomplete="email" required><label for="register-email">Email address</label></div>
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'lock'])</span><input type="password" name="password" id="register-password" placeholder=" " minlength="10" autocomplete="new-password" required><label for="register-password">Password</label><button type="button" class="eye-button" data-password-toggle="register-password" aria-label="Show password">@include('partials.icon',['name'=>'eye'])</button></div>
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'lock'])</span><input type="password" name="password_confirmation" id="register-confirm" placeholder=" " minlength="10" autocomplete="new-password" required><label for="register-confirm">Confirm password</label><button type="button" class="eye-button" data-password-toggle="register-confirm" aria-label="Show confirmation password">@include('partials.icon',['name'=>'eye'])</button><button type="button" class="eye-button" data-password-toggle="register-confirm" aria-label="Show password">@include('partials.icon',['name'=>'eye'])</button></div>
<p class="native-note">Use at least 10 characters, including letters and numbers.</p>
<button class="btn btn-primary" type="submit">Create Account</button></form>
<div class="auth-links"><span class="muted">Already registered?</span><a href="{{ route('login') }}">Sign in</a></div>
@endsection
