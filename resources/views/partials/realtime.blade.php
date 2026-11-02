{{-- Live notifications for the signed-in user; the Pusher client loads only once Pusher keys are configured. --}}
@if (\App\Notifications\Channels\RealtimeChannel::enabled())
@php
    $realtime = [
        'key' => config('broadcasting.connections.pusher.key'),
        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        'auth' => url('/broadcasting/auth'),
        'channel' => $channel ?? 'private-App.Models.User.'.auth()->id(), // matches routes/channels.php
    ];
@endphp
<script src="{{ asset('assets/vendor/pusher-8.4.0.min.js') }}"></script>
<script>window.COSPARK_RT = @json($realtime);</script>
@endif
<x-script src="realtime.js" />
