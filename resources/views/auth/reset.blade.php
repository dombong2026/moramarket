@extends('layouts.auth')
@section('title','Set New Password')
@section('content')
<p class="auth-subtitle">Choose a strong new password.</p>
<form action="{{ route('password.update') }}" method="POST">@csrf<input type="hidden" name="token" value="{{ $token }}">
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'mail'])</span><input type="email" name="email" id="reset-email" value="{{ old('email',$email) }}" placeholder=" " maxlength="190" autocomplete="email" required><label for="reset-email">Email address</label></div>
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'lock'])</span><input type="password" name="password" id="reset-password" placeholder=" " minlength="10" autocomplete="new-password" required><label for="reset-password">New password</label><button type="button" class="eye-button" data-password-toggle="reset-password" aria-label="Show password">@include('partials.icon',['name'=>'eye'])</button></div>
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'lock'])</span><input type="password" name="password_confirmation" id="reset-confirm" placeholder=" " minlength="10" autocomplete="new-password" required><label for="reset-confirm">Confirm password</label><button type="button" class="eye-button" data-password-toggle="reset-confirm" aria-label="Show confirmation password">@include('partials.icon',['name'=>'eye'])</button></div>
<p class="native-note">Use at least 10 characters, including letters and numbers.</p>
<button type="submit" class="btn btn-primary">Save Password</button></form>
@endsection
