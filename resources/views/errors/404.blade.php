@extends('layouts.app', ['page' => '404'])

@section('title', 'Page not found — COSPARK Insurance Group')

@section('content')
<section class="final" style="padding:140px 0 80px"><div class="wrap"><span class="eyebrow">Error 404</span><h2 style="margin-top:22px">This page isn't here.</h2><p class="lead" style="margin:20px auto 0">It may have moved. Head home or start a quote.</p><div class="ctas"><a href="{{ route('home') }}" class="btn btn-dark">Go home</a><a href="{{ route('quote') }}" class="btn btn-line">Get a Quote</a></div><div class="watermark" aria-hidden="true">404</div></div></section>
@endsection
