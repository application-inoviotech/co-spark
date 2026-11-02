@extends('layouts.app', ['page' => 'quote'])

@section('title', 'Get a Quote — COSPARK Insurance Group')
@section('description', 'Request an auto, home, renters, condo, life, business or bundled insurance quote from COSPARK Insurance Group.')

@section('content')
<section class="page-hero" style="background:linear-gradient(180deg,var(--bg),#FBF5EF)">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Get a quote</span></nav>
    <h1>Your Next Policy Starts With One Conversation.</h1>
    <p class="lead">Answer a few questions and a COSPARK agent will prepare your quote. Most people finish in under five minutes.</p>
  </div>
</section>
<section class="section-sm">
  <div class="wrap quote-wrap">
    <div class="panel" id="quoteForm" aria-live="polite" data-action="{{ route('quote.store') }}" data-dashboard="{{ auth()->check() ? route('dashboard') : '' }}"></div>
    <aside>
      <div class="summary-panel">
        <span class="mono">Your quote summary</span>
        <div class="sum-row"><span class="mono">Coverage</span><div class="v" id="s_type">—</div></div>
        <div class="sum-row"><span class="mono">ZIP code</span><div class="v" id="s_zip">—</div></div>
        <div class="sum-row"><span class="mono">Details</span><div class="v" id="s_detail">—</div></div>
        <div class="sum-row"><span class="mono">Name</span><div class="v" id="s_name">—</div></div>
        <div class="sum-row"><span class="mono">Email</span><div class="v" id="s_email">—</div></div>
        <div class="sum-row"><span class="mono">Phone</span><div class="v" id="s_phone">—</div></div>
        <p class="sum-foot">Your information is encrypted and only used to prepare your quote.</p>
      </div>
      <div class="card" style="margin-top:14px">
        <h3 style="font-weight:500">Prefer to talk it through?</h3>
        <p class="muted small" style="margin:8px 0 16px">Book a phone or in-person appointment with an agent.</p>
        <button class="link-arrow" data-appt>Schedule an appointment</button>
      </div>
    </aside>
  </div>
</section>
@endsection

@push('scripts')
<script>const QUOTE_TYPES = @json($quoteTypes), QUOTE_DRAFT = @json($draft);</script>
<x-script src="forms.js" />
<x-script src="quote.js" />
@endpush
