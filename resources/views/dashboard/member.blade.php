@extends('layouts.base', ['page' => 'dashboard'])

@section('title', 'Member Dashboard — COSPARK')
@section('description', 'Manage your COSPARK membership, policies, payments, tokens, claims, vacations and benefits.')

@section('body')
<div class="dash">
  <aside class="dash-side" id="dSide">
    <a class="logo" href="{{ route('home') }}" style="color:#fff" id="dLogo"></a>
    <nav class="dash-nav" id="dNav" aria-label="Dashboard"></nav>
    <div class="dash-user"><span class="avatar" id="dAv">{{ $user->initials }}</span><div><div style="color:#fff" id="dName">{{ $user->name }}</div><span class="mono" style="font-size:9px;color:var(--copper-2)" id="dTier">{{ $planName }} member</span></div><a href="{{ route('logout') }}" data-logout aria-label="Sign out" style="margin-left:auto;color:#77818F" id="dOut"></a></div>
  </aside>
  <div class="dash-main">
    <header class="dash-top">
      <button class="icon-btn mob-dash-toggle" id="dMenu" aria-label="Open menu"></button>
      <div><span class="mono muted" id="dCrumb" style="font-size:9.5px">Member dashboard</span><h1 id="dTitle">Overview</h1></div>
      <div class="right">
        <a class="icon-btn" href="#notifications" aria-label="Notifications" id="dBell"></a>
        <a class="btn btn-dark btn-sm" href="{{ route('quote') }}" style="display:none" id="dQuote">Get a Quote</a>
        <a class="btn btn-line btn-sm top-out" href="{{ route('logout') }}" data-logout aria-label="Sign out"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H4V3h5M16 17l5-5-5-5M21 12H9"/></svg><span>Sign out</span></a>
      </div>
    </header>
    <div class="dash-body" id="view" aria-live="polite"></div>
  </div>
</div>
<div class="modal" id="dModal" role="dialog" aria-modal="true"><div class="modal-bg" data-close></div><div class="modal-box" id="dBox"></div></div>
<form id="logoutForm" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@include('partials.appointment-modal')
@endsection

@push('scripts')
<script src="{{ app(\App\Services\Billing\AuthorizeNet::class)->clientConfig()['script'] }}" charset="utf-8"></script>
<x-script src="card.js" />
<script>const DASH = @json($endpoints);</script>
@include('partials.realtime')
<x-script src="dashboard.js" />
@endpush
