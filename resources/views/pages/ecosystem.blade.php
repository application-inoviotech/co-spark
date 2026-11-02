@extends('layouts.app', ['page' => 'ecosystem'])

@section('title', cms('ecosystem.meta_title'))
@section('description', cms('ecosystem.meta_description'))

@section('content')
<section class="page-hero dark on-dark" style="border:0">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" style="color:#97A1AF"><a href="{{ route('home') }}">Home</a><span>/</span><span>Ecosystem</span></nav>
    <h1 style="color:#fff">{{ cms('ecosystem.hero_title') }}</h1>
    <p class="lead">{{ cms('ecosystem.hero_lead') }}</p>
  </div>
</section>
<section class="section" id="vacations" style="scroll-margin-top:80px">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">{{ cms('ecosystem.vac_eyebrow') }}</span><h2 style="margin-top:18px">{{ cms('ecosystem.vac_title') }}</h2></div><p class="lead">{{ cms('ecosystem.vac_lead') }}</p></div>
    <div class="grid-3" id="vacGrid">@foreach ($vacations as $property)<div class="prop-card"><div class="prop-img" style="background-image:url({{ $property->imageAt(800) }})"></div><div class="in"><span class="tag">{{ $property->location }}</span><h3 class="h3s" style="font-size:26px">{{ $property->name }}</h3><p class="muted small" style="margin:0">{{ $property->description }}</p><div class="amen">@foreach ($property->amenities ?? [] as $amenity)<span>{{ $amenity }}</span>@endforeach</div><a class="btn btn-dark btn-sm" style="margin-top:auto;align-self:flex-start" href="{{ route('dashboard') }}#vacations">{{ cms('ecosystem.vac_cta') }} →</a></div></div>@endforeach</div>
  </div>
</section>
<section class="section bg-2" id="partners" style="scroll-margin-top:80px">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">{{ cms('ecosystem.partners_eyebrow') }}</span><h2 style="margin-top:18px">{{ cms('ecosystem.partners_title') }}</h2></div><p class="lead">{{ cms('ecosystem.partners_lead') }}</p></div>
    <div class="grid-3" id="partGrid">@foreach ($partners as $partner)<div class="card"><span class="tag">{{ $partner->category }}</span><h3 class="h3s" style="font-size:26px;margin:10px 0 8px">{{ $partner->name }}</h3><p class="muted small" style="margin:0 0 16px">{{ $partner->description }}</p>@if ($partnerUrl = \App\Support\PublicLink::external($partner->website_url))<a class="link-arrow" href="{{ $partnerUrl }}" target="_blank" rel="noopener">{{ cms('ecosystem.partners_cta') }}</a>@else<span class="muted small">Partner link coming soon</span>@endif</div>@endforeach</div>
  </div>
</section>
<section class="section-sm">
  <div class="wrap two-col">
    <div><span class="eyebrow">{{ cms('ecosystem.qr_eyebrow') }}</span><h2 style="margin:18px 0 20px;font-size:clamp(32px,3.6vw,46px)">{{ cms('ecosystem.qr_title') }}</h2></div>
    <ol class="timeline">@foreach (cms_rows('ecosystem.qr_steps', 2) as [$title, $text])<li{!! $loop->last ? '' : ' class="done"' !!}><b>{{ $title }}</b><small>{{ $text }}</small></li>@endforeach</ol>
  </div>
</section>
@endsection
