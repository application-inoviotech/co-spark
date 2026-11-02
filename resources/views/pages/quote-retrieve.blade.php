@extends('layouts.app', ['page' => 'quote'])

@section('title', 'Retrieve a Saved Quote — COSPARK Insurance Group')
@section('description', 'Continue a quote request you saved earlier.')

@section('content')
<section class="page-hero" style="background:linear-gradient(180deg,var(--bg),#FBF5EF)">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('quote') }}">Get a quote</a><span>/</span><span>Retrieve a saved quote</span></nav>
    <h1>Pick up where you left off.</h1>
    <p class="lead">Enter the email address and the code from your “saved quote” email.</p>
  </div>
</section>
<section class="section-sm">
  <div class="wrap" style="max-width:620px">
    <form class="panel" method="POST" action="{{ route('quote.retrieve') }}" novalidate>
      @csrf
      <div class="form-grid">
        <div class="field @error('email') err @enderror"><label for="re">Email <span class="req">*</span></label><input id="re" name="email" type="email" required value="{{ old('email') }}" autocomplete="email"><span class="err-msg">{{ $errors->first('email') ?: 'Enter a valid email.' }}</span></div>
        <div class="field @error('code') err @enderror"><label for="rc">Quote code <span class="req">*</span></label><input id="rc" name="code" required value="{{ old('code') }}" style="text-transform:uppercase" autocomplete="off"><span class="err-msg">{{ $errors->first('code') ?: 'Enter your code.' }}</span></div>
      </div>
      <div class="form-nav"><a href="{{ route('quote') }}" class="btn btn-line">Start a new quote</a><button class="btn btn-copper" type="submit">Continue my quote →</button></div>
    </form>
  </div>
</section>
@endsection
