@extends('layouts.app', ['page' => 'claims'])

@section('title', cms('claims.meta_title'))
@section('description', cms('claims.meta_description'))

@section('content')
<section class="page-hero img-hero" style="background-image:url(https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?auto=format&fit=crop&w=1800&q=70)">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Claims</span></nav>
    <h1>{{ cms('claims.hero_title') }}</h1>
    <p class="lead">{{ cms('claims.hero_lead') }}</p>
    <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap"><a href="{{ route('claims.create') }}" class="btn btn-copper">{{ cms('claims.hero_cta_primary') }} →</a><a href="{{ route('dashboard') }}#claims" class="btn btn-ghost-dark">{{ cms('claims.hero_cta_secondary') }}</a></div>
  </div>
</section>
<section class="section-sm">
  <div class="wrap grid-4">
    @php $quickLinks = [route('claims.create'), route('dashboard').'#claims', '#roadside', '?type=glass#types']; @endphp
    @foreach (array_slice(cms_rows('claims.quick_cards', 2), 0, 4) as $i => [$title, $text])
    <a class="card" href="{{ $quickLinks[$i] }}"><h3 style="font-weight:500">{{ $title }}</h3><p class="muted small" style="margin:8px 0 0">{{ $text }}</p></a>
    @endforeach
  </div>
</section>
<section class="section bg-2">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">{{ cms('claims.steps_eyebrow') }}</span><h2 style="margin:18px 0 20px">{{ cms('claims.steps_title') }}</h2><p class="lead">{{ cms('claims.steps_lead') }}</p></div>
    <ol class="timeline" style="margin-top:14px">
      @foreach (cms_rows('claims.steps', 2) as [$title, $text])
      <li{!! $loop->last ? '' : ' class="done"' !!}><b>{{ $title }}</b><small>{{ $text }}</small></li>
      @endforeach
    </ol>
  </div>
</section>
<section class="section" id="types" style="scroll-margin-top:90px">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">{{ cms('claims.types_eyebrow') }}</span><h2 style="margin-top:18px">{{ cms('claims.types_title') }}</h2></div><p class="lead">{{ cms('claims.types_lead') }}</p></div>
    <nav class="tabs" id="ctTabs">@foreach ($claimTypes as $type)<button data-k="{{ $type->slug }}" class="{{ $type->slug === $activeType ? 'on' : '' }}">{{ $type->name }}</button>@endforeach</nav>
    @foreach ($claimTypes as $type)
    <div class="two-col" style="align-items:start;margin-top:40px" data-ct="{{ $type->slug }}" @if ($type->slug !== $activeType) hidden @endif><div><h3 class="h3s">{{ $type->name }} claims</h3><p class="mono muted" style="margin:24px 0 12px">Immediate actions</p><ul class="check-list">@foreach ($type->immediate_actions as $item)<li>{{ $item }}</li>@endforeach</ul><p class="mono muted" style="margin:30px 0 12px">Photo & evidence</p><p class="muted">{{ $type->photo_guidance }}</p><div style="display:flex;gap:10px;margin-top:28px;flex-wrap:wrap"><a class="btn btn-copper" href="{{ route('claims.create', ['type' => $type->slug]) }}">File a {{ strtolower($type->name) }} claim →</a><a class="btn btn-line" href="{{ route('dashboard') }}#claims">View claim</a></div></div>
      <div class="card"><p class="mono muted" style="margin:0 0 16px">Claim steps</p><ol class="timeline">@foreach ($type->steps as $step)<li class="{{ $loop->first ? 'now' : '' }}">{{ $step }}</li>@endforeach</ol><p class="mono muted" style="margin:10px 0 12px">Required documents</p><ul class="check-list">@foreach ($type->documents as $doc)<li>{{ $doc }}</li>@endforeach</ul></div></div>
    @endforeach
  </div>
</section>
<section class="section-sm bg-2">
  <div class="wrap grid-3">
    @php
      $helpLinks = [
          ['roadside', 'tel:'.config('cospark.office.roadside_tel'), ' '.config('cospark.office.roadside_phone')],
          ['medical', route('contact'), ''],
          ['catastrophe', route('resources.index', ['cat' => 'plan']), ''],
      ];
    @endphp
    @foreach (array_slice(cms_rows('claims.help_cards', 4), 0, 3) as $i => [$tag, $title, $text, $link])
    @php [$id, $href, $suffix] = $helpLinks[$i]; @endphp
    <div class="card" id="{{ $id }}" style="scroll-margin-top:100px"><span class="mono copper">{{ $tag }}</span><h3 class="h3s" style="margin:12px 0 10px">{{ $title }}</h3><p class="muted small">{{ $text }}</p><a class="link-arrow" href="{{ $href }}">{{ $link }}{{ $suffix }}</a></div>
    @endforeach
  </div>
</section>
<section class="section">
  <div class="wrap-narrow"><span class="eyebrow">{{ cms('claims.faq_eyebrow') }}</span><h2 style="margin:18px 0 30px;font-size:clamp(32px,3.6vw,46px)">{{ cms('claims.faq_title') }}</h2>
    <div class="acc">
      @foreach (cms_rows('claims.faqs', 2) as [$question, $answer])
      <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
      @endforeach
    </div>
  </div>
</section>
@endsection

@push('scripts')
<x-script src="pages/claims.js" />
@endpush
