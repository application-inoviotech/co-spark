{{-- Public website: header, page content, footer and the appointment popup --}}
@extends('layouts.base')

@section('body')
@include('partials.header')
<main id="main">
@yield('content')
</main>
@include('partials.footer')
@include('partials.appointment-modal')
@endsection
