@extends('layouts.base', ['page' => 'receipt'])

@section('title', 'Receipt '.$payment->reference.' — COSPARK')

@section('body')
<main class="wrap-narrow" style="padding:60px 0">
  <div class="row-between" style="margin-bottom:34px;flex-wrap:wrap;gap:12px">
    <a class="logo" href="{{ route('home') }}">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a>
    <button class="btn btn-line btn-sm" onclick="window.print()">Print / Save as PDF</button>
  </div>
  <span class="eyebrow">{{ $payment->succeeded() ? 'Payment receipt' : 'Payment record' }}</span>
  <h1 style="font-size:44px;margin:14px 0 6px">${{ number_format($payment->amount, 2) }}</h1>
  <p class="muted" style="margin:0 0 30px">{{ $payment->description }}</p>
  <div class="card">
    <dl style="display:grid;grid-template-columns:1fr 1.4fr;margin:0">
      @foreach ([
          'Reference' => $payment->reference,
          'Date' => ($payment->paid_at ?? $payment->created_at)->format('F j, Y g:i A'),
          'Status' => ucfirst($payment->status),
          'Billed to' => $user->name.' · '.$user->member_number,
          'Payment method' => $payment->method_label,
          'Period' => $payment->period_start ? $payment->period_start->format('M j, Y').' – '.$payment->period_end?->format('M j, Y') : '—',
      ] as $label => $value)
        <dt class="muted" style="padding:11px 0;border-bottom:1px solid var(--bg-2)">{{ $label }}</dt>
        <dd style="margin:0;padding:11px 0;border-bottom:1px solid var(--bg-2)">{{ $value }}</dd>
      @endforeach
    </dl>
  </div>
  <p class="small muted" style="margin-top:28px">{{ $office['name'] }} · {{ $office['address_line1'] }}, {{ $office['address_line2'] }} · {{ $office['phone'] }} · {{ $office['email'] }}</p>
</main>
@endsection
