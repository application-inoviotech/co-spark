@extends('layouts.app', ['page' => 'producer'])

@section('title', cms('producer.meta_title'))
@section('description', cms('producer.meta_description'))

@section('content')
<section class="page-hero dark on-dark" style="border:0">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" style="color:#97A1AF"><a href="{{ route('home') }}">Home</a><span>/</span><span>Become a producer</span></nav>
    <h1 style="color:#fff">{{ cms('producer.hero_title') }}</h1>
    <p class="lead">{{ cms('producer.hero_lead') }}</p>
    <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap"><a href="#apply" class="btn btn-copper">{{ cms('producer.hero_cta_primary') }} →</a><a href="{{ auth()->user()?->isProducer() ? route('producer.dashboard') : route('login', ['role' => 'producer']) }}" class="btn btn-ghost-dark">{{ cms('producer.hero_cta_secondary') }}</a></div>
  </div>
</section>
<section class="section-sm">
  <div class="wrap"><div class="stats">@foreach (cms_rows('producer.stats', 2) as [$value, $label])<div><b>{{ $value }}</b><span class="mono muted">{{ $label }}</span></div>@endforeach</div></div>
</section>
<section class="section-sm">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">{{ cms('producer.benefits_eyebrow') }}</span><h2 style="margin-top:18px">{{ cms('producer.benefits_title') }}</h2></div><p class="lead">{{ cms('producer.benefits_lead') }}</p></div>
    <div class="grid-3">
      @foreach (cms_rows('producer.benefits', 2) as [$title, $text])
      <div class="card"><h3 style="font-weight:500">{{ $title }}</h3><p class="muted small">{{ $text }}</p></div>
      @endforeach
    </div>
  </div>
</section>
<section class="section-sm bg-2">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">{{ cms('producer.req_eyebrow') }}</span><h2 style="margin:18px 0 20px;font-size:clamp(32px,3.6vw,46px)">{{ cms('producer.req_title') }}</h2><p class="lead">{{ cms('producer.req_lead') }}</p></div>
    <ul class="check-list card" style="padding:30px">
      @foreach (cms_rows('producer.requirements') as [$requirement])
      <li>{{ $requirement }}</li>
      @endforeach
    </ul>
  </div>
</section>
<section class="section" id="apply" style="scroll-margin-top:80px">
  <div class="wrap quote-wrap">
    <div class="panel" id="prodForm" aria-live="polite" data-action="{{ route('producer.apply') }}" data-portal-url="{{ route('producer.dashboard') }}"></div>
    <aside>
      <div class="summary-panel"><span class="mono">Application</span>
        <div class="sum-row"><span class="mono">Applicant</span><div class="v" id="pa_name">—</div></div>
        <div class="sum-row"><span class="mono">Experience</span><div class="v" id="pa_exp">—</div></div>
        <div class="sum-row"><span class="mono">License</span><div class="v" id="pa_lic">—</div></div>
        <div class="sum-row"><span class="mono">Documents</span><div class="v" id="pa_docs">—</div></div>
        <p class="sum-foot">Apply · Documents · Review · Terms · Submit</p></div>
    </aside>
  </div>
</section>
@endsection

@push('scripts')
<x-script src="forms.js" />
<x-script src="pages/producer-apply.js" />
@endpush
