@extends('layouts.app', ['page' => 'claims'])

@section('title', 'Report a Claim — COSPARK Insurance Group')
@section('description', 'File an insurance claim online with COSPARK Insurance Group.')

@section('content')
<section class="page-hero">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('claims.index') }}">Claims</a><span>/</span><span>Report a claim</span></nav>
    <h1>Report a claim.</h1>
    @guest
    <p class="lead">Tell us what happened. You can save time by <a href="{{ route('login', ['next' => route('claims.create')]) }}" class="copper" style="text-decoration:underline">signing in</a> — your policy details will be filled in for you.</p>
    @else
    <p class="lead">Tell us what happened. Your contact details{{ !empty($prefill['policy']) ? ' and selected policy' : '' }} have been filled in.{{ empty($prefill['policy']) ? ' Enter the policy number for this claim.' : ' Check they are correct.' }}</p>
    @endguest
  </div>
</section>
<section class="section-sm">
  <div class="wrap quote-wrap">
    <div class="panel" id="claimForm" aria-live="polite" data-action="{{ route('claims.store') }}" data-dashboard-url="{{ route('dashboard') }}" data-claims-url="{{ route('claims.index') }}"></div>
    <aside>
      <div class="summary-panel">
        <span class="mono">Claim summary</span>
        <div class="sum-row"><span class="mono">Policy</span><div class="v" id="c_pol">—</div></div>
        <div class="sum-row"><span class="mono">Claim type</span><div class="v" id="c_type">—</div></div>
        <div class="sum-row"><span class="mono">Incident date</span><div class="v" id="c_date">—</div></div>
        <div class="sum-row"><span class="mono">Location</span><div class="v" id="c_loc">—</div></div>
        <div class="sum-row"><span class="mono">Files</span><div class="v" id="c_files">—</div></div>
        <p class="sum-foot">Emergency? Call 911 first. Roadside: {{ config('cospark.office.roadside_phone') }}</p>
      </div>
    </aside>
  </div>
</section>
@endsection

@push('scripts')
<script>const CLAIM_PREFILL = @json($prefill);</script>
<x-script src="forms.js" />
<x-script src="pages/file-claim.js" />
@endpush
