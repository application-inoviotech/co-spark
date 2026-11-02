@extends('layouts.auth', ['page' => 'register'])

@section('title', 'Join COSPARK — Create Your Account')
@section('description', 'Create your COSPARK member account and choose a Free, Associate or Premium membership.')

@section('content')
<div class="auth" style="grid-template-columns:.8fr 1.2fr">
  <aside class="auth-side">
    <a class="logo" href="{{ route('home') }}" style="color:#fff" id="authLogo">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a>
    <div><span class="eyebrow" style="color:#97A1AF">Join COSPARK</span><h2 style="margin-top:18px">One account for everything you protect.</h2>
      <div class="card-dark" style="margin-top:34px;max-width:340px"><span class="mono" style="color:#97A1AF;font-size:9.5px">Your selection</span><div style="font-family:var(--serif);font-size:38px;margin:8px 0" id="selPlan">{{ $wizard['plans'][$wizard['state']['plan']]['n'] }}</div><div id="selPrice" class="copper"></div><div class="mc-sep"></div><div id="selPerks" class="small" style="color:#C3CAD4"></div></div></div>
    <span class="mono" style="color:#5E6878">Paid memberships activate after email verification and first payment.</span>
  </aside>
  <main class="auth-main" style="align-items:flex-start;padding-top:60px">
    <div class="auth-form" style="width:min(620px,100%)" id="register"
         data-store="{{ route('register.store') }}" data-verify="{{ route('register.verify') }}"
         data-resend="{{ route('register.resend') }}" data-payment="{{ route('register.payment') }}"
         data-login="{{ route('login') }}" data-dashboard="{{ route('dashboard') }}"
         data-sms-consent="{{ app(\App\Support\Content::class)->get('registration.sms_consent') }}" data-terms="{{ route('legal') }}#terms" data-privacy="{{ route('legal') }}#privacy">
      <a href="{{ route('home') }}" class="crumbs" style="margin-bottom:24px;display:inline-flex">← Back to site</a>
      <div class="steps-bar" id="rBar"></div>
      <div id="rStep"></div>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script>const REGISTER = @json($wizard);</script>
<script src="{{ app(\App\Services\Billing\AuthorizeNet::class)->clientConfig()['script'] }}" charset="utf-8"></script>
<x-script src="card.js" />
<x-script src="pages/register.js" />
@endpush
