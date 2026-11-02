@extends('layouts.app', ['page' => 'insurance'])

@section('title', cms('insurance.meta_title'))
@section('description', cms('insurance.meta_description'))

@section('content')
<section class="page-hero img-hero" style="background-image:url(https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1800&q=70)">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Insurance</span></nav>
    <h1>{{ cms('insurance.hero_title') }}</h1>
    <p class="lead">{{ cms('insurance.hero_lead') }}</p>
    <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap"><a href="{{ route('quote') }}" class="btn btn-copper">{{ cms('insurance.hero_cta_primary') }} →</a><button class="btn btn-ghost-dark" data-appt>{{ cms('insurance.hero_cta_secondary') }}</button></div>
  </div>
</section>
<div class="wrap"><nav class="tabs" id="catTabs" aria-label="Insurance categories" style="position:sticky;top:76px;background:var(--bg);z-index:5">@foreach ($categories as $category)<a href="#{{ $category->slug }}" class="{{ $loop->first ? 'on' : '' }}">{{ $category->name }}</a>@endforeach<a href="#states">{{ cms('insurance.states_tab') }}</a></nav></div>
<div id="cats">
@foreach ($categories as $category)
<section class="section-sm" id="{{ $category->slug }}" style="scroll-margin-top:140px">
    <div class="wrap"><div class="sec-head" style="margin-bottom:34px"><h2 style="font-size:clamp(34px,3.8vw,48px)">{{ $category->name }}</h2><p class="lead">{{ $category->lead }}</p></div>
    <div class="grid-3">@foreach ($category->products as $product)<a class="prop-card" href="{{ route('insurance.show', $product) }}"><div class="prop-img" style="background-image:url({{ $product->imageAt(700) }})"></div><div class="in"><h3 style="font-weight:500">{{ $product->name }}</h3><p class="muted small" style="margin:0;flex:1">{{ $product->intro }}</p><span class="link-arrow" style="margin-top:10px;align-self:flex-start">{{ cms('insurance.card_link') }}</span></div></a>@endforeach</div></div></section>
@endforeach
</div>
<section class="section-sm bg-2" id="states">
  <div class="wrap two-col">
    <div><span class="eyebrow">{{ cms('insurance.states_eyebrow') }}</span><h2 style="margin:18px 0 16px;font-size:clamp(32px,3.6vw,46px)">{{ cms('insurance.states_title') }}</h2><p class="lead">{{ cms('insurance.states_lead') }}</p></div>
    <div class="card">
      <div class="field"><label for="stSel">Your state</label><select id="stSel" data-quote-url="{{ route('quote') }}"><option value="">Select…</option>@foreach ($states as $state)<option>{{ $state }}</option>@endforeach</select></div>
      <div id="stOut" class="muted small" style="margin-top:16px">{{ cms('insurance.states_prompt') }}</div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<x-script src="pages/insurance.js" />
@endpush
