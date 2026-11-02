@extends('layouts.app', ['page' => 'membership'])

@section('title', cms('membership.meta_title'))
@section('description', cms('membership.meta_description'))

@section('content')
<section class="page-hero">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Membership</span></nav>
    <h1>{{ cms('membership.hero_title') }}</h1>
    <p class="lead">{{ cms('membership.hero_lead') }}</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="row-between" style="margin-bottom:40px;flex-wrap:wrap">
      <span class="eyebrow">{{ cms('membership.plans_eyebrow') }}</span>
      <div class="chips" role="group" aria-label="Billing frequency" id="freq">
        <button class="chip on" data-m="1" data-l="month">Monthly</button><button class="chip" data-m="3" data-l="quarter">Quarterly</button><button class="chip" data-m="6" data-l="6 months">Bi-annual</button><button class="chip" data-m="12" data-l="year">Annual</button>
      </div>
    </div>
    @include('partials.plan-cards', ['page' => 'membership'])
    <p class="muted small" style="margin-top:28px;text-align:center">{{ cms('membership.plans_note') }}</p>
  </div>
</section>

<section class="section bg-2">
  <div class="wrap">
    <h2 style="font-size:clamp(32px,3.6vw,46px);margin-bottom:30px">{{ cms('membership.compare_title') }}</h2>
    <div class="tbl-wrap card" style="padding:8px 20px">
      <table class="tbl"><thead><tr><th>Feature</th>@foreach (['free', 'associate', 'premium'] as $key)<th>{{ config("membership.plans.{$key}.name") }}</th>@endforeach</tr></thead><tbody>
        @foreach (cms_rows('membership.compare_rows', 4) as $cells)
        <tr>@foreach ($cells as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @endforeach
      </tbody></table>
    </div>
  </div>
</section>

<section class="section dark on-dark" id="tokens">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">{{ cms('membership.tokens_eyebrow') }}</span><h2 style="margin-top:18px">{{ cms('membership.tokens_title') }}</h2></div><p class="lead">{{ cms('membership.tokens_lead') }}</p></div>
    <div class="tokens3">
      @foreach (['dt', 'it', 'vt'] as $type)
      @php [$label, $value, $text] = cms_rows("membership.token_{$type}", 3)[0] ?? ['', '', '']; @endphp
      <div class="tok {{ $type }}"><span class="mono">{{ $label }}</span><div class="v">{{ $value }}</div><p class="s">{{ $text }}</p></div>
      @endforeach
    </div>
    <div class="wallet-box" style="margin-top:20px">
      <div class="two-col" style="gap:40px">
        <div><span class="mono" style="color:#97A1AF">{{ cms('membership.estimator_label') }}</span><h3 class="h3s" style="margin:14px 0 22px;color:#fff">{{ cms('membership.estimator_title') }}</h3>
          <div class="form-grid">
            <div class="field half"><label style="color:#97A1AF" for="prem">Annual premium ($)</label><input id="prem" type="number" value="2400" min="0" style="background:#0F1724;border-color:var(--navy-line);color:#fff"></div>
            <div class="field half"><label style="color:#97A1AF" for="tier">Membership</label><select id="tier" style="background-color:#0F1724;border-color:var(--navy-line);color:#fff"><option value="0.05">Associate (5%)</option><option value="0.07" selected>Premium (7%)</option></select></div>
          </div></div>
        <div style="align-self:center"><div class="row-between" style="border-bottom:1px solid var(--navy-line);padding:14px 0"><span class="muted">Insurance Tokens / year</span><b id="itOut" style="font-family:var(--serif);font-size:34px;font-weight:400"></b></div><div class="row-between" style="border-bottom:1px solid var(--navy-line);padding:14px 0"><span class="muted">Dues Tokens / year</span><b id="dtOut" style="font-family:var(--serif);font-size:34px;font-weight:400"></b></div><div class="row-between" style="padding:14px 0"><span class="muted">Total tokens / year</span><b id="totOut" class="grad-text" style="font-family:var(--serif);font-size:44px;font-weight:400"></b></div></div>
      </div>
    </div>
    <p class="small" style="color:#6F7A89;margin-top:18px">{{ cms('membership.tokens_footnote') }}</p>
  </div>
</section>

<section class="section" id="journey">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">{{ cms('membership.journey_eyebrow') }}</span><h2 style="margin:18px 0 22px">{{ cms('membership.journey_title') }}</h2><p class="lead">{{ cms('membership.journey_lead') }}</p></div>
    <ul class="timeline" style="margin-top:20px">
      @foreach (cms_rows('membership.journey_steps', 2) as $i => [$title, $text])
      <li{!! [' class="done"', ' class="done"', ' class="now"'][$i] ?? '' !!}><b>{{ $title }}</b><small>{{ $text }}</small></li>
      @endforeach
    </ul>
  </div>
</section>

<section class="section-sm bg-2">
  <div class="wrap two-col">
    <div><span class="eyebrow">{{ cms('membership.subs_eyebrow') }}</span><h2 style="margin:18px 0 18px;font-size:clamp(32px,3.6vw,46px)">{{ cms('membership.subs_title') }}</h2><p class="lead">{{ cms('membership.subs_lead') }}</p></div>
    <ul class="check-list card" style="padding:30px">@foreach (cms_rows('membership.subs_points') as [$point])<li>{{ $point }}</li>@endforeach</ul>
  </div>
</section>

<section class="section">
  <div class="wrap-narrow"><span class="eyebrow">{{ cms('membership.faq_eyebrow') }}</span><h2 style="margin:18px 0 30px;font-size:clamp(32px,3.6vw,46px)">{{ cms('membership.faq_title') }}</h2>
    <div class="acc">
      @foreach (cms_rows('membership.faqs', 2) as [$question, $answer])
      <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
      @endforeach
    </div>
  </div>
</section>
<section class="section-sm dark on-dark"><div class="wrap row-between" style="flex-wrap:wrap;gap:24px"><h2 style="font-size:clamp(32px,3.6vw,46px)">{{ cms('membership.cta_title') }}</h2><div style="display:flex;gap:12px;flex-wrap:wrap"><a href="{{ route('register') }}" class="btn btn-copper">{{ cms('membership.cta_primary') }} →</a><button class="btn btn-ghost-dark" data-appt>{{ cms('membership.cta_secondary') }}</button></div></div></section>
@endsection

@push('scripts')
<x-script src="pages/membership.js" />
@endpush
