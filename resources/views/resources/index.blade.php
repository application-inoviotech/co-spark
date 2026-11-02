@extends('layouts.app', ['page' => 'resources'])

@section('title', cms('resources.meta_title'))
@section('description', cms('resources.meta_description'))

@section('content')
<section class="page-hero">
  <div class="grid-bg"></div>
  <div class="wrap" style="position:relative">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span>Resources</span></nav>
    <h1>{{ cms('resources.hero_title') }}</h1>
    <p class="lead">{{ cms('resources.hero_lead') }}</p>
    <div class="field" style="max-width:520px;margin-top:28px"><label for="rq" class="sr-only">Search resources</label><input id="rq" type="search" placeholder="{{ cms('resources.search_placeholder') }}"></div>
  </div>
</section>
<section class="section-sm">
  <div class="wrap">
    <div class="filter-row" id="rCats"><button class="chip {{ $activeCategory === 'all' ? 'on' : '' }}" data-c="all">All</button>@foreach ($categories as $category)<button class="chip {{ $activeCategory === $category->slug ? 'on' : '' }}" data-c="{{ $category->slug }}">{{ $category->name }}</button>@endforeach</div>
    <div class="grid-3" id="rGrid" style="margin-top:20px">@foreach ($resources as $resource)<a class="prop-card" href="{{ route('resources.show', $resource) }}" data-cat="{{ $resource->category->slug }}" data-text="{{ $resource->title }} {{ $resource->summary }}" @if ($activeCategory !== 'all' && $resource->category->slug !== $activeCategory) hidden @endif><div class="prop-img" style="background-image:url({{ $resource->imageAt(700) }})"></div><div class="in"><span class="tag">{{ $resource->category->name }} · {{ $resource->read_minutes }} min read</span><h3 style="font-weight:500;line-height:1.35">{{ $resource->title }}</h3><p class="muted small" style="margin:0">{{ $resource->summary }}</p></div></a>@endforeach</div>
    <p class="empty" id="rEmpty" hidden>{{ cms('resources.empty_text') }} <button class="link-arrow" data-appt>{{ cms('resources.empty_link') }}</button></p>
  </div>
</section>
<section class="section bg-2" id="glossary">
  <div class="wrap two-col" style="align-items:start">
    <div><span class="eyebrow">{{ cms('resources.glossary_eyebrow') }}</span><h2 style="margin:18px 0 20px;font-size:clamp(32px,3.6vw,46px)">{{ cms('resources.glossary_title') }}</h2><p class="lead">{{ cms('resources.glossary_lead') }}</p></div>
    <div class="acc">
      @foreach (cms_rows('resources.glossary', 2) as [$term, $definition])
      <details><summary>{{ $term }}</summary><p>{{ $definition }}</p></details>
      @endforeach
    </div>
  </div>
</section>
<section class="section-sm"><div class="wrap grid-3">
  @foreach (cms_rows('resources.help_cards', 4) as [$title, $text, $link, $href])
  @php $external = str_starts_with($href, 'http'); @endphp
  <div class="card"><h3 style="font-weight:500">{{ $title }}</h3><p class="muted small">{{ $text }}</p><a class="link-arrow" href="{{ $external ? $href : url($href) }}"@if ($external) target="_blank" rel="noopener"@endif>{{ $link }}</a></div>
  @endforeach
</div></section>
@endsection

@push('scripts')
<x-script src="pages/resources.js" />
@endpush
