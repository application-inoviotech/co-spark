@extends('layouts.app', ['page' => 'payments'])

@section('title', cms('payments.meta_title'))
@section('description', cms('payments.meta_description'))

@section('content')
<section class="page-hero">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Payments</span></nav>
    <h1>{{ cms('payments.hero_title') }}</h1>
    <p class="lead">{{ cms('payments.hero_lead') }}</p>
    <div style="display:flex;gap:12px;margin-top:28px;flex-wrap:wrap"><a href="{{ route('login', ['next' => route('dashboard').'#payments']) }}" class="btn btn-dark">{{ cms('payments.hero_cta_primary') }} →</a><a href="{{ route('register') }}" class="btn btn-line">{{ cms('payments.hero_cta_secondary') }}</a></div>
  </div>
</section>
<section class="section-sm">
  <div class="wrap grid-3">
    @php $cardArt = [['dues', 'coin', 'membership'], ['insurance', 'shield', 'policies'], ['vt', 'palm', 'wallet']]; @endphp
    @foreach (array_slice(cms_rows('payments.cards', 3), 0, 3) as $i => [$title, $text, $link])
    @php [$id, $icon, $section] = $cardArt[$i]; @endphp
    <div class="card" id="{{ $id }}" style="scroll-margin-top:100px"><span class="copper" data-i="{{ $icon }}"><x-icon :name="$icon" /></span><h3 class="h3s" style="margin:16px 0 10px">{{ $title }}</h3><p class="muted small">{{ $text }}</p><a class="link-arrow" href="{{ route('dashboard') }}#{{ $section }}">{{ $link }}</a></div>
    @endforeach
  </div>
</section>
<section class="section bg-2">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">{{ cms('payments.how_eyebrow') }}</span><h2 style="margin:18px 0 22px;font-size:clamp(32px,3.6vw,46px)">{{ cms('payments.how_title') }}</h2>
      <ol class="timeline">@foreach (cms_rows('payments.how_steps', 2) as [$title, $text])<li class="done"><b>{{ $title }}</b><small>{{ $text }}</small></li>@endforeach</ol></div>
    <div class="card" style="padding:32px"><h3 style="font-weight:500">{{ cms('payments.tools_title') }}</h3><ul class="check-list" style="margin-top:18px">@foreach (cms_rows('payments.tools') as [$tool])<li>{{ $tool }}</li>@endforeach</ul>
      <div class="notice info" style="margin-top:24px"><span>{{ cms('payments.tools_notice') }}</span></div></div>
  </div>
</section>
@endsection
