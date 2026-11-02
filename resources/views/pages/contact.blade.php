@extends('layouts.app', ['page' => 'contact'])

@section('title', cms('contact.meta_title'))
@section('description', cms('contact.meta_description'))

@section('content')
<section class="page-hero">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Contact</span></nav>
    <h1>{{ cms('contact.hero_title') }}</h1>
    <p class="lead">{{ cms('contact.hero_lead') }}</p>
  </div>
</section>
<section class="section-sm">
  <div class="wrap quote-wrap">
    <div class="panel">
      <form data-simple-form="MSG" action="{{ route('contact.store') }}" novalidate>
        <h2 class="step-title" style="margin-bottom:26px">{{ cms('contact.form_title') }}</h2>
        <div class="form-grid">
          <div class="field half"><label for="cf1">First name <span class="req">*</span></label><input id="cf1" name="first_name" required autocomplete="given-name"><span class="err-msg">Enter your first name.</span></div>
          <div class="field half"><label for="cf2">Last name <span class="req">*</span></label><input id="cf2" name="last_name" required autocomplete="family-name"><span class="err-msg">Enter your last name.</span></div>
          <div class="field half"><label for="cf3">Email <span class="req">*</span></label><input id="cf3" name="email" type="email" required autocomplete="email"><span class="err-msg">Enter a valid email.</span></div>
          <div class="field half"><label for="cf4">Contact number</label><input id="cf4" name="phone" type="tel" autocomplete="tel"><span class="err-msg">Enter a valid phone number with 10 to 15 digits.</span></div>
          <div class="field"><label for="cf5">Message <span class="req">*</span></label><textarea id="cf5" name="message" required>{{ $quoteProduct ? 'Please contact me about a '.$quoteProduct->short_name.' insurance quote.' : '' }}</textarea><span class="err-msg">Enter a message.</span></div>
        </div>
        <div class="form-nav"><span class="muted small">{{ cms('contact.form_note') }}</span><button class="btn btn-copper" type="submit">{{ cms('contact.form_button') }} →</button></div>
      </form>
      <div data-done hidden class="confirm"><div class="confirm-mark">✓</div><h2 class="step-title">{{ cms('contact.done_title') }}</h2><p class="muted">{{ cms('contact.done_text') }}</p><div class="ref-id" data-ref></div></div>
    </div>
    <aside class="summary-panel">
      <span class="mono">COSPARK Insurance Group</span>
      <div class="sum-row"><span class="mono">Office</span><div class="v">{{ $office['address_line1'] }}<br>{{ $office['address_line2'] }}</div></div>
      <div class="sum-row"><span class="mono">Phone</span><div class="v"><a href="tel:{{ $office['phone_tel'] }}">{{ $office['phone'] }}</a></div></div>
      <div class="sum-row"><span class="mono">Email</span><div class="v"><a href="mailto:{{ $office['email'] }}">{{ $office['email'] }}</a></div></div>
      <div class="sum-row"><span class="mono">Hours</span><div class="v">{{ cms('contact.hours') }}</div></div>
      <button class="btn btn-copper btn-block" style="margin-top:24px" data-appt>{{ cms('contact.appt_button') }}</button>
    </aside>
  </div>
</section>
<section class="section-sm" style="padding-top:0"><div class="wrap"><iframe title="COSPARK office map" src="https://www.google.com/maps?q={{ urlencode($office['address_line1'].' '.$office['address_line2']) }}&output=embed" style="width:100%;height:380px;border:1px solid var(--line);filter:grayscale(.6)" loading="lazy"></iframe></div></section>
@endsection
