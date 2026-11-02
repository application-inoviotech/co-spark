@extends('layouts.app', ['page' => 'insurance'])

@section('title', $product->name.' — COSPARK Insurance Group')
@section('description', 'Coverage overview, options, benefits and FAQs for COSPARK insurance products.')

@section('content')
<section class="page-hero img-hero" id="pHero" style="background-image: url({{ $product->image_url }});">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" id="pCrumbs"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('insurance.index') }}">Insurance</a><span>/</span><a href="{{ route('insurance.index') }}#{{ $category->slug }}">{{ $category->name }}</a><span>/</span><span>{{ $product->name }}</span></nav>
    <h1 id="pTag">{{ $product->tagline }}</h1>
    <p class="lead" id="pIntro">{{ $product->intro }}</p>
    <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap"><a id="pQuote" class="btn btn-copper" href="{{ $quoteUrl }}">{{ $quoteCtaLabel }} →</a><button class="btn btn-ghost-dark" data-appt>Speak to an agent</button></div>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow" id="pName">{{ $product->name }}</span><h2 style="margin-top:18px">What's covered.</h2></div><p class="lead">Every policy is tailored with an agent. Here's an overview of the core protection available.</p></div>
    <div class="grid-3" id="pCov">@foreach ($category->coverage as $coverage)<div class="card"><span class="copper"><x-icon name="shield" /></span><h3 style="font-weight:500;margin:16px 0 8px">{{ $coverage['title'] }}</h3><p class="muted small" style="margin:0">{{ $coverage['text'] }}</p></div>@endforeach</div>
  </div>
</section>
<section class="section bg-2">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">Coverage options</span><h2 style="margin:18px 0 26px;font-size:clamp(32px,3.6vw,46px)">Make it fit your life.</h2><ul class="check-list" id="pOpts">@foreach ($category->options as $option)<li>{{ $option }}</li>@endforeach</ul></div>
    <div class="card-dark" style="padding:36px"><span class="mono" style="color:var(--copper-2)">Member benefits</span><h3 class="h3s" style="margin:14px 0 22px;color:#fff">Better with COSPARK membership.</h3><ul class="check-list" id="pBen" style="color:#C3CAD4">@foreach ($category->benefits as $benefit)<li>{{ $benefit }}</li>@endforeach</ul><a href="{{ route('membership') }}" class="link-arrow" style="margin-top:28px;color:#fff">Compare memberships</a></div>
  </div>
</section>
<section class="section">
  <div class="wrap-narrow"><span class="eyebrow">Questions</span><h2 style="margin:18px 0 30px;font-size:clamp(32px,3.6vw,46px)">Frequently asked.</h2><div class="acc" id="pFaq">@foreach ($faqs as [$question, $answer])<details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>@endforeach</div></div>
</section>
<section class="section-sm bg-2">
  <div class="wrap"><div class="row-between" style="margin-bottom:28px;flex-wrap:wrap"><h2 style="font-size:clamp(30px,3.2vw,40px)">Related coverage</h2><a href="{{ route('insurance.index') }}" class="link-arrow">All products</a></div><div class="grid-3" id="pRel">@foreach ($related as $item)<a class="prop-card" href="{{ route('insurance.show', $item) }}"><div class="prop-img" style="background-image:url({{ $item->imageAt(700) }})"></div><div class="in"><h3 style="font-weight:500">{{ $item->name }}</h3><p class="muted small" style="margin:0">{{ $item->tagline }}</p></div></a>@endforeach</div></div>
</section>
<section class="section-sm dark on-dark">
  <div class="wrap row-between" style="flex-wrap:wrap;gap:24px"><div><h2 style="font-size:clamp(32px,3.6vw,46px)" id="pCtaH">Ready to price {{ $product->short_name }} coverage?</h2><p class="lead" style="margin-top:10px">Get a quote online or talk to a licensed COSPARK agent.</p></div><div style="display:flex;gap:12px;flex-wrap:wrap"><a id="pQuote2" class="btn btn-copper" href="{{ $quoteUrl }}">{{ $quoteCtaLabel }} →</a><a href="{{ route('contact') }}" class="btn btn-ghost-dark">Contact us</a></div></div>
</section>
@endsection
