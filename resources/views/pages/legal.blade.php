@extends('layouts.app', ['page' => 'legal'])

@section('title', 'Legal — COSPARK Insurance Group')
@section('description', 'Privacy policy, terms and conditions, disclaimers and SMS terms for COSPARK Insurance Group.')

@section('content')
<section class="page-hero"><div class="wrap"><nav class="crumbs"><a href="{{ route('home') }}">Home</a><span>/</span><span>Legal</span></nav><h1>Legal & policies.</h1><p class="lead">Final legal language will be supplied and approved by COSPARK Insurance Group before launch.</p></div></section>
<section class="section-sm"><div class="wrap-narrow" style="font-size:16.5px">
  <h2 id="privacy" style="font-size:38px;margin:10px 0 14px;scroll-margin-top:100px">Privacy Policy</h2><p class="muted" style="white-space:pre-line">{{ config('cospark.legal.privacy') }}</p>
  <h2 id="terms" style="font-size:38px;margin:40px 0 14px;scroll-margin-top:100px">Terms & Conditions</h2><p class="muted" style="white-space:pre-line">{{ config('cospark.legal.terms') }}</p>
  <h2 id="disclaimers" style="font-size:38px;margin:40px 0 14px;scroll-margin-top:100px">Disclaimers</h2><p class="muted" style="white-space:pre-line">{{ config('cospark.legal.disclaimers') }}</p>
  <h2 id="sms" style="font-size:38px;margin:40px 0 14px;scroll-margin-top:100px">SMS Terms</h2><p class="muted" style="white-space:pre-line">{{ config('cospark.legal.sms') }}</p>
</div></section>
@endsection
