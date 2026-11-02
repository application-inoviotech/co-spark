{{-- Sign in, registration and password reset: split screen without site header/footer --}}
@extends('layouts.base')

@section('body')
@yield('content')
@include('partials.appointment-modal')
@endsection
