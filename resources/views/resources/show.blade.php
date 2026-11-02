@extends('layouts.app', ['page' => 'resources'])

@section('title', $resource->title.' — COSPARK')
@section('description', 'Insurance guidance from COSPARK Insurance Group.')

@section('content')
<article>
<section class="page-hero img-hero" id="aHero" style="background-image: url({{ $resource->image_url }});"><div class="wrap"><nav class="crumbs" id="aCrumbs"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('resources.index') }}">Resources</a><span>/</span><a href="{{ route('resources.index', ['cat' => $resource->category->slug]) }}">{{ $resource->category->name }}</a></nav><h1 id="aT" style="max-width:20ch">{{ $resource->title }}</h1><p class="lead" id="aS">{{ $resource->summary }}</p><span class="mono" id="aMeta" style="color:#C4CBD5">{{ $resource->category->name }} · {{ $resource->read_minutes }} min read</span></div></section>
<section class="section-sm"><div class="wrap-narrow" style="font-size:17.5px;line-height:1.75">
  <p id="aLead" style="font-size:20px;color:var(--ink-2)">{{ $resource->summary }}</p>
  @if ($resource->body)
  {!! $resource->body !!}
  @else
  <p>This article is being prepared. Please contact an agent for help with this topic.</p>
  <a href="{{ route('contact') }}" class="link-arrow">Contact an agent →</a>
  @endif
  <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap"><a href="{{ route('quote') }}" class="btn btn-copper">Get a Quote →</a><a href="{{ route('resources.index') }}" class="btn btn-line">More resources</a></div>
</div></section>
</article>
<section class="section-sm bg-2"><div class="wrap"><h2 style="font-size:36px;margin-bottom:24px">Related resources</h2><div class="grid-3" id="aRel">@foreach ($related as $item)<a class="prop-card" href="{{ route('resources.show', $item) }}"><div class="prop-img" style="background-image:url({{ $item->imageAt(700) }})"></div><div class="in"><span class="tag">{{ $item->category->name }}</span><h3 style="font-weight:500;line-height:1.35">{{ $item->title }}</h3></div></a>@endforeach</div></div></section>
@endsection
