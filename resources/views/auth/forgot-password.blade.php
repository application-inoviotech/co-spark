@extends('layouts.auth', ['page' => 'login'])

@section('title', 'Reset Password — COSPARK Insurance Group')

@section('content')
<div class="auth">
  <aside class="auth-side"><a class="logo" href="{{ route('home') }}" style="color:#fff" id="authLogo">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a><div><h2>Let's get you back in.</h2></div><span class="mono" style="color:#5E6878">COSPARK Insurance Group</span></aside>
  <main class="auth-main"><div class="auth-form" id="fp" data-send="{{ route('password.email') }}" data-verify="{{ route('password.verify') }}" data-update="{{ route('password.update') }}">
    <a href="{{ route('login') }}" class="crumbs" style="margin-bottom:30px;display:inline-flex">← Back to sign in</a>
    <div data-s="1"><h1 style="font-size:46px">Reset password</h1><p class="muted" style="margin:10px 0 28px">Enter your email and we'll send a 6-digit code.</p><div class="field"><label for="fe">Email</label><input id="fe" name="email" type="email" required><span class="err-msg">Enter a valid email.</span></div><button class="btn btn-dark btn-block" style="margin-top:22px" data-go="2">Send code →</button></div>
    <div data-s="2" hidden><h1 style="font-size:46px">Check your email</h1><p class="muted" style="margin:10px 0 28px">Enter the 6-digit code we sent.</p><div class="otp field" style="flex-direction:row">@for ($d = 0; $d < 6; $d++)<input inputmode="numeric" maxlength="1" aria-label="Digit">@endfor</div><p class="err-msg small" style="color:var(--red);display:none" id="otpErr">Enter all 6 digits.</p><button class="btn btn-dark btn-block" style="margin-top:22px" data-go="3">Verify code →</button><button class="btn-x" style="margin-top:14px" data-resend>Resend code</button></div>
    <div data-s="3" hidden><h1 style="font-size:46px">New password</h1><p class="muted" style="margin:10px 0 28px">Use at least 8 characters with a number.</p><div class="form-grid"><div class="field"><label for="np1">New password</label><input id="np1" name="password" type="password" required minlength="8" autocomplete="new-password"><span class="err-msg">Use at least 8 characters with a number.</span></div><div class="field"><label for="np2">Confirm password</label><input id="np2" name="password_confirmation" type="password" required autocomplete="new-password"><span class="err-msg">Passwords must match.</span></div></div><button class="btn btn-dark btn-block" style="margin-top:22px" data-go="4">Update password →</button></div>
    <div data-s="4" hidden class="confirm"><div class="confirm-mark">✓</div><h1 style="font-size:40px">Password updated.</h1><p class="muted">You can now sign in with your new password.</p><a href="{{ route('login') }}" class="btn btn-dark">Sign in →</a></div>
  </div></main>
</div>
@endsection

@push('scripts')
<x-script src="pages/forgot-password.js" />
@endpush
