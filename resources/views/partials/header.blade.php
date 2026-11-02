@php
    $active = $page ?? '';
    $caret = '<svg class="caret" viewBox="0 0 10 10"><path d="M1 3l4 4 4-4" stroke="currentColor" fill="none"/></svg>';
@endphp
<header class="site-header" id="siteHeader"><div class="wrap nav">
    <a class="logo" href="{{ route('home') }}" aria-label="COSPARK Insurance Group home">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a>
    <ul class="nav-links">
      <li class="{{ $active === 'insurance' ? 'active' : '' }}"><button aria-haspopup="true">Insurance{!! $caret !!}</button><div class="dd mega">@foreach ($nav->insurance() as [$heading, $items])<div><h6>{{ $heading }}</h6>@foreach ($items as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div>@endforeach<div class="mega-foot"><span class="muted small">{{ cms('layout.mega_note') }}</span><a class="link-arrow" href="{{ route('quote') }}">{{ cms('layout.mega_cta') }}</a></div></div></li>
      <li class="{{ $active === 'membership' ? 'active' : '' }}"><a href="{{ route('membership') }}">Membership</a></li>
      <li class="{{ $active === 'quote' ? 'active' : '' }}"><button aria-haspopup="true">Get a Quote{!! $caret !!}</button><div class="dd">@foreach ($nav->quotes() as [$label, $url])<a href="{{ $url }}">{{ $label }} quote</a>@endforeach</div></li>
      <li class="{{ $active === 'claims' ? 'active' : '' }}"><button aria-haspopup="true">Claims{!! $caret !!}</button><div class="dd">@foreach ($nav->links('nav_claims') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></li>
      <li class="{{ $active === 'resources' ? 'active' : '' }}"><button aria-haspopup="true">Resources{!! $caret !!}</button><div class="dd">@foreach ($nav->links('nav_resources') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></li>
      <li class="{{ $active === 'payments' ? 'active' : '' }}"><button aria-haspopup="true">Payments{!! $caret !!}</button><div class="dd">@foreach ($nav->links('nav_payments') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></li>
      <li class="{{ $active === 'producer' ? 'active' : '' }}"><a href="{{ route('producer') }}">Become a Producer</a></li>
    </ul>
    <div class="nav-right">
      @auth
        <a class="nav-signin" href="{{ auth()->user()->homeRoute() }}">My account</a>
      @else
        <a class="nav-signin" href="{{ route('login') }}">Sign in</a>
      @endauth
      <a class="btn btn-dark btn-sm" href="{{ route('quote') }}">Get a Quote <span aria-hidden="true">→</span></a>
      <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false"><span></span></button>
    </div>
  </div></header>
  <nav class="mobile-nav" id="mobileNav" aria-label="Mobile">
    <a href="{{ route('home') }}">Home</a>
    <details><summary>Insurance</summary><div>@foreach ($nav->insurance() as [, $items])@foreach ($items as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach @endforeach</div></details>
    <a href="{{ route('membership') }}">Membership</a>
    <details><summary>Get a Quote</summary><div>@foreach ($nav->quotes() as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></details>
    <details><summary>Claims</summary><div>@foreach ($nav->links('nav_claims') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></details>
    <details><summary>Resources</summary><div>@foreach ($nav->links('nav_resources') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></details>
    <details><summary>Payments</summary><div>@foreach ($nav->links('nav_payments') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></details>
    <a href="{{ route('ecosystem') }}">Ecosystem & Vacations</a>
    <a href="{{ route('producer') }}">Become a Producer</a>
    <a href="{{ route('contact') }}">Contact</a>
    <a class="btn btn-copper btn-block" href="{{ route('quote') }}">Get a Quote →</a>
    @auth
      <a class="btn btn-line btn-block" href="{{ auth()->user()->homeRoute() }}" style="margin-top:10px">My account</a>
    @else
      <a class="btn btn-line btn-block" href="{{ route('login') }}" style="margin-top:10px">Sign in</a>
    @endauth
  </nav>
