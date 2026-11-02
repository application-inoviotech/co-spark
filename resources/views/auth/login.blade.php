@extends('layouts.auth', ['page' => 'login'])

@section('title', 'Sign In — COSPARK Insurance Group')
@section('description', 'Sign in to your COSPARK member or producer account.')

@section('content')
@php($role = old('role', request('role') === 'producer' ? 'producer' : 'member')) {{-- ?role=producer preselects the Producer tab --}}
<div class="auth">
  <aside class="auth-side">
    <a class="logo" href="{{ route('home') }}" style="color:#fff" id="authLogo">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a>
    <div><span class="eyebrow" style="color:#97A1AF">Welcome back</span><h2 style="margin-top:18px">Protect today. Build tomorrow.</h2><p style="color:#97A1AF;max-width:36ch;margin-top:18px">Policies, payments, tokens, claims and benefits — in one account.</p></div>
    <span class="mono" style="color:#5E6878">COSPARK Insurance Group · Rochester, MI</span>
  </aside>
  <main class="auth-main">
    <div class="auth-form">
      <a href="{{ route('home') }}" class="crumbs" style="margin-bottom:30px;display:inline-flex">← Back to site</a>
      <h1 style="font-size:46px">Sign in</h1>
      <p class="muted" style="margin:10px 0 28px"><span data-for="member" @if ($role === 'producer') hidden @endif>New to COSPARK? <a href="{{ route('register') }}" class="copper">Create an account</a></span><span data-for="producer" @if ($role !== 'producer') hidden @endif>Producers join through an application. <a href="{{ route('producer') }}#apply" class="copper">Apply to become a producer</a></span></p>
      @if (session('status'))
        <div class="notice info" style="margin-bottom:22px;font-size:13px">{{ session('status') }}</div>
      @endif
      <div class="chips" role="group" aria-label="Account type" id="role" style="margin-bottom:22px"><button type="button" class="chip {{ $role === 'member' ? 'on' : '' }}" data-r="member">Member</button><button type="button" class="chip {{ $role === 'producer' ? 'on' : '' }}" data-r="producer">Producer</button></div>
      <form id="loginForm" method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">
        <input type="hidden" name="next" value="{{ old('next', request('next')) }}">
        <div class="form-grid">
          <div class="field @error('email') err @enderror"><label for="le">Email</label><input id="le" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"><span class="err-msg">{{ $errors->first('email') ?: 'Enter a valid email.' }}</span></div>
          <div class="field @error('password') err @enderror"><label for="lp">Password</label><input id="lp" name="password" type="password" required autocomplete="current-password"><span class="err-msg">{{ $errors->first('password') ?: 'Enter your password.' }}</span></div>
        </div>
        <div class="row-between" style="margin:18px 0 26px"><label class="checkline"><input type="checkbox" name="remember" value="1" @checked(old('remember', true))> Keep me signed in</label><a href="{{ route('password.request') }}" class="small copper">Forgot password?</a></div>
        <button class="btn btn-dark btn-block" type="submit">Sign in →</button>
      </form>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<x-script src="pages/login.js" />
@endpush
