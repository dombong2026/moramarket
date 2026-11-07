@extends('layouts.auth')
@section('title','Reset Password')
@section('content')
<p class="auth-subtitle">Enter your account email to request a reset link.</p>
<form action="{{ route('password.email') }}" method="POST">@csrf
<div class="float-field"><span class="field-icon">@include('partials.icon',['name'=>'mail'])</span><input type="email" name="email" id="forgot-email" value="{{ old('email') }}" placeholder=" " required><label for="forgot-email">Email address</label></div><button class="btn btn-primary" type="submit">Request reset link</button></form>
<div class="auth-links"><a href="{{ route('login') }}">Back to sign in</a></div>
@endsection
