@extends('layouts.app', ['page' => 'home'])

@section('title', cms('home.meta_title'))
@section('description', cms('home.meta_description'))

@section('content')
<!-- HERO -->
<section class="hero">
  <div class="grid-bg"></div><div class="glow a"></div><div class="glow b"></div>
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow rv">{{ cms('home.hero_eyebrow') }}</span>
      <h1 class="rv d1">{{ cms('home.hero_title') }}<span class="l2 grad-text">{{ cms('home.hero_title_highlight') }}</span></h1>
      <p class="lead rv d2" style="color:var(--ink-2)">{{ cms('home.hero_lead') }}</p>
      <div class="hero-ctas rv d3">
        <a href="{{ route('quote') }}" class="btn btn-copper">{{ cms('home.hero_cta_primary') }} <span aria-hidden="true">→</span></a>
        <a href="{{ route('membership') }}" class="btn btn-line">{{ cms('home.hero_cta_secondary') }}</a>
      </div>
      <p class="hero-note rv d4">{{ cms('home.hero_note') }}</p>
    </div>
    <div class="orbit-stage rv d2" aria-hidden="true">
      <div class="orbit-ring r1"></div><div class="orbit-ring r2"></div>
      <span class="orbit-tag" style="left:-20px;top:38px">Insurance</span>
      <span class="orbit-tag" style="left:280px;top:-18px">Membership</span>
      <span class="orbit-tag" style="left:50px;bottom:56px">Benefits</span>
      <div class="member-card">
        <span class="mc-dot"></span>
        <div class="mono">Good morning, James</div>
        <div class="mono mc-tier">Premium member</div>
        <div class="mc-sep"></div>
        <div class="mono">COSPARK value</div>
        <div class="mc-big"><span data-count="284">0</span><small>TOKENS</small></div>
        <div class="mc-up">+12 this month</div>
        <div class="row-between" style="margin-top:24px"><span class="mono">60-month journey</span><span class="mono" style="color:#fff">24 / 60</span></div>
        <div class="bar"><i style="width:40%"></i></div>
        <div class="mono" style="margin-top:26px">Active policies</div>
        <div class="mc-pols"><div><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/></svg>Home</div><div><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3z"/></svg>Auto</div><div><svg viewBox="0 0 24 24" fill="none" stroke-width="1.6"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>Health</div></div>
        <div class="mc-sep" style="margin-top:22px"></div>
        <div class="mono">Next payment</div>
        <div class="row-between" style="margin-top:10px"><span style="font-size:22px">$25.00</span><span class="mono">Auto-pay enabled</span></div>
      </div>
    </div>
  </div>
</section>

<!-- MARQUEE -->
<div class="marquee" aria-label="Platform features">
  <div class="wrap"><span class="mono">{{ cms('home.marquee_label') }}</span></div>
  @php $marquee = cms_rows('home.marquee'); @endphp
  <div class="marquee-track">@foreach (array_merge($marquee, $marquee) as $i => [$item])<span{!! $i >= count($marquee) ? ' aria-hidden="true"' : '' !!}>{{ $item }}</span>@endforeach</div>
</div>

<!-- CONNECTED MODEL -->
<section class="section">
  <div class="wrap two-col">
    <div>
      <div class="sec-num"><b>01</b><i></i>{{ cms('home.connected_label') }}</div>
      <span class="eyebrow">{{ cms('home.connected_eyebrow') }}</span>
      <h2 style="margin:20px 0 24px">{{ cms('home.connected_title') }}</h2>
      <p class="lead">{{ cms('home.connected_lead') }}</p>
      <div class="model-copy" id="modelCopy">{{ cms('home.connected_prompt') }}</div>
    </div>
    <div class="model-orbit" id="modelOrbit">
      <div class="ring"></div>
      <div class="core"><div><b>COSPARK</b><span class="mono">Tokens</span></div></div>
    </div>
  </div>
</section>

<!-- COSPARK MODEL -->
<section class="section bg-2">
  <div class="wrap">
    <div class="sec-num"><b>02</b><i></i>{{ cms('home.model_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.model_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.model_title') }}</h2></div>
      <p class="lead">{{ cms('home.model_lead') }}</p>
    </div>
    @php
      $stepArt = [
          [route('register'), '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>'],
          [route('membership').'#tokens', '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5c-.5-1-1.5-1.5-2.5-1.5-1.5 0-2.5.8-2.5 2s1 1.7 2.5 2 2.5.8 2.5 2-1 2-2.5 2c-1 0-2-.5-2.5-1.5"/>'],
          [route('membership').'#journey', '<rect x="3" y="8" width="18" height="4"/><path d="M5 12v9h14v-9M12 8v13M12 8S10.5 3 8 3.5 7 8 12 8zm0 0s1.5-5 4-4.5S17 8 12 8z"/>'],
      ];
    @endphp
    <div class="stack">
      @foreach (array_slice(cms_rows('home.model_steps', 3), 0, 3) as $i => [$title, $text, $link])
      <article class="stack-card"><div><div class="stack-num">{{ sprintf('%02d', $i + 1) }}</div><svg viewBox="0 0 24 24" fill="none" stroke-width="1.5">{!! $stepArt[$i][1] !!}</svg></div><div><span class="mono">{{ $title }}</span><p>{{ $text }}</p><a href="{{ $stepArt[$i][0] }}" class="link-arrow">{{ $link }}</a></div></article>
      @endforeach
    </div>
  </div>
</section>

<!-- MEMBERSHIP -->
<section class="section">
  <div class="wrap">
    <div class="sec-num"><b>03</b><i></i>{{ cms('home.plans_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.plans_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.plans_title') }}</h2></div>
      <p class="lead">{{ cms('home.plans_lead') }}</p>
    </div>
    @include('partials.plan-cards', ['page' => 'home'])
  </div>
</section>

<!-- WALLET -->
<section class="section dark on-dark">
  <div class="wrap">
    <div class="sec-num"><b>04</b><i></i>{{ cms('home.wallet_label') }}</div>
    <div class="sec-head">
      <h2>{{ cms('home.wallet_title') }}</h2>
      <p class="lead">{{ cms('home.wallet_lead') }}</p>
    </div>
    <div class="wallet-box">
      <div class="row-between" style="border-bottom:1px solid var(--navy-line);padding-bottom:20px"><span class="mono" style="color:#97A1AF">My COSPARK wallet</span><span class="mono" style="color:var(--green)">Live · Premium member</span></div>
      <div class="wallet-grid">
        <div>
          <span class="mono" style="color:#97A1AF">Balance</span>
          <div class="wallet-big" style="margin-top:14px"><span data-count="284">0</span><small>TOKENS</small></div>
          <div class="mc-up">+12 this month</div>
          <div class="token-split"><div><span class="mono" style="color:#97A1AF;font-size:9px">DT · Dues</span><b>134</b></div><div><span class="mono" style="color:#97A1AF;font-size:9px">IT · Insurance</span><b>150</b></div><div><span class="mono" style="color:#97A1AF;font-size:9px">VT · Vacation</span><b>$0</b></div></div>
          <a href="{{ route('dashboard') }}#wallet" class="link-arrow" style="margin-top:28px;color:#fff">View token details</a>
        </div>
        <div>
          <span class="mono" style="color:#97A1AF">Recent activity</span>
          <div style="margin-top:14px">
            <div class="act-row"><span class="amt">+12</span>Membership dues<span class="mono">This month</span></div>
            <div class="act-row"><span class="amt">+150</span>Annual premium allocation<span class="mono">Allocation</span></div>
            <div class="act-row"><span class="amt">+12</span>Membership dues<span class="mono">Last month</span></div>
          </div>
          <div class="row-between" style="margin-top:26px"><span class="mono" style="color:#97A1AF">Start</span><span class="mono" style="color:#fff">Month 24 / 60</span><span class="mono" style="color:#97A1AF">60 months</span></div>
          <div class="bar"><i style="width:40%"></i></div>
          <p class="mono" style="color:#6F7A89;margin-top:18px;font-size:9.5px;line-height:1.8">Token ledger · History · Annual-premium tracking · Forfeiture rules · Redemption eligibility</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- INSURANCE -->
<section class="section">
  <div class="wrap">
    <div class="sec-num"><b>05</b><i></i>{{ cms('home.insurance_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.insurance_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.insurance_title') }}</h2></div>
      <p class="lead">{{ cms('home.insurance_lead') }}</p>
    </div>
    @php
      $cardArt = [
          ['homeowners', 'photo-1613490493576-7fde63acd811', ' tall'], ['car', 'photo-1503376780353-7e6692767b70', ' tall'],
          ['health', 'photo-1505751172876-fa1923c5c528', ''], ['business', 'photo-1486406146926-c627a92ad1ab', ''],
      ];
    @endphp
    <div class="ins-grid">
      @foreach (array_slice(cms_rows('home.insurance_cards', 4), 0, 4) as $i => [$tag, $title, $text, $link])
      <a class="ins-card{{ $cardArt[$i][2] }}" href="{{ route('insurance.show', $cardArt[$i][0]) }}" style="background-image:url(https://images.unsplash.com/{{ $cardArt[$i][1] }}?auto=format&fit=crop&w=1200&q=70)"><span class="mono">{{ $tag }}</span><div><h3>{{ $title }}</h3><p>{{ $text }}</p><span class="link-arrow">{{ $link }}</span></div></a>
      @endforeach
    </div>
    <div style="text-align:center;margin-top:40px"><a href="{{ route('insurance.index') }}" class="btn btn-line">{{ cms('home.insurance_cta') }}</a></div>
  </div>
</section>

<!-- POLICY MGMT -->
<section class="section bg-2">
  <div class="wrap">
    <div class="sec-num"><b>06</b><i></i>{{ cms('home.policy_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.policy_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.policy_title') }}</h2></div>
      <p class="lead">{{ cms('home.policy_lead') }}</p>
    </div>
    <div class="browser">
      <div class="browser-bar"><i></i><i></i><i></i><span>member.cospark.com/policies</span></div>
      <div class="browser-body">
        <nav class="browser-side" id="mockSide">@foreach ([['grid', 'Dashboard', 'overview'], ['doc', 'My Policies', 'policies'], ['wallet', 'Token Wallet', 'wallet'], ['card', 'Payments', 'payments'], ['cal', 'Appointments', 'appointments'], ['gift', 'Benefits', 'benefits']] as [$icon, $label, $section])<a href="{{ route('dashboard') }}#{{ $section }}" class="{{ $section === 'policies' ? 'on' : '' }}"><x-icon :name="$icon" />{{ $label }}</a>@endforeach</nav>
        <div class="browser-main">
          <span class="mono muted">My policies</span>
          <div class="grid-3" style="margin-top:18px">
            <div class="card" style="padding:22px"><span class="copper" id="ic1"><x-icon name="home" /></span><h3 style="font-weight:500;margin:14px 0 8px">Home</h3><span class="pill green">Active</span><p class="mono muted" style="margin:10px 0 0;font-size:9.5px">Expires May 2027</p></div>
            <div class="card" style="padding:22px"><span class="copper" id="ic2"><x-icon name="car" /></span><h3 style="font-weight:500;margin:14px 0 8px">Auto</h3><span class="pill green">Active</span><p class="mono muted" style="margin:10px 0 0;font-size:9.5px">Expires November 2026</p></div>
            <div class="card" style="padding:22px"><span class="copper" id="ic3"><x-icon name="heart" /></span><h3 style="font-weight:500;margin:14px 0 8px">Health</h3><span class="pill green">Active</span><p class="mono muted" style="margin:10px 0 0;font-size:9.5px">Expires January 2027</p></div>
          </div>
          <div class="row-between" style="background:var(--bg-2);border:1px solid var(--line);padding:22px;margin-top:16px;flex-wrap:wrap"><div style="display:flex;gap:16px;align-items:center"><i style="width:7px;height:7px;border-radius:50%;background:var(--copper)"></i><div><span class="mono muted">Next policy renewal</span><div style="font-weight:500;margin-top:4px">Auto · November 18</div></div></div><a href="{{ route('dashboard') }}#policies" class="link-arrow">Review policy</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- QUOTE -->
<section class="section" style="background:linear-gradient(180deg,var(--bg),#FBF5EF)">
  <div class="wrap">
    <div class="sec-num"><b>07</b><i></i>{{ cms('home.quote_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.quote_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.quote_title') }}</h2></div>
      <p class="lead">{{ cms('home.quote_lead') }}</p>
    </div>
    <div class="quote-wrap">
      <div class="panel" id="miniQuote" data-action="{{ route('quote.quick') }}" data-quote-url="{{ route('quote') }}"></div>
      <aside class="summary-panel" aria-live="polite">
        <span class="mono">Your quote summary</span>
        <div class="sum-row"><span class="mono">Coverage</span><div class="v" id="sq_cov">—</div></div>
        <div class="sum-row"><span class="mono">Name</span><div class="v" id="sq_name">—</div></div>
        <div class="sum-row"><span class="mono">Email</span><div class="v" id="sq_email">—</div></div>
        <div class="sum-row"><span class="mono">Phone</span><div class="v" id="sq_phone">—</div></div>
        <div class="sum-row"><span class="mono">Address</span><div class="v" id="sq_addr">—</div></div>
        <p class="sum-foot">Quote capture · Lead tracking · Status management · Follow-up · Quote history</p>
      </aside>
    </div>
  </div>
</section>

<!-- APPOINTMENTS -->
<section class="section bg-2">
  <div class="wrap two-col" style="align-items:start">
    <div>
      <div class="sec-num"><b>08</b><i></i>{{ cms('home.appt_label') }}</div>
      <span class="eyebrow">{{ cms('home.appt_eyebrow') }}</span>
      <h2 style="margin:20px 0 24px">{{ cms('home.appt_title') }}</h2>
      <p class="lead">{{ cms('home.appt_lead') }}</p>
      <div class="divider"></div>
      <span class="mono muted">Office</span>
      <p style="margin:12px 0 6px;display:flex;gap:10px"><span class="copper" id="pinIc"><x-icon name="pin" /></span><span>{{ config('cospark.office.address_line1') }}<br>{{ config('cospark.office.address_line2') }}</span></p>
      <span class="mono muted">{{ cms('home.appt_hours') }}</span>
    </div>
    <div class="appt-box" id="homeAppt" data-times='@json(array_values(array_filter(config('cospark.appointment_times'), fn ($t) => ! str_starts_with($t, 'After hours'))))'>
      <div class="appt-types">
        <button type="button" class="appt-type on" data-ht="Phone"><svg class="icon" viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/></svg><b>Phone</b><span>Speak with the team remotely.</span></button>
        <button type="button" class="appt-type" data-ht="In person"><svg class="icon" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c1-3.5 3.6-5.5 7-5.5s6 2 7 5.5M16 4.5a3.5 3.5 0 0 1 0 7"/></svg><b>In Person</b><span>Visit the COSPARK office.</span></button>
      </div>
      <div class="two-col" style="gap:30px;margin-top:28px;align-items:start;grid-template-columns:1.2fr 1fr">
        <div><span class="mono muted">Choose a date</span><div class="cal" id="cal" style="margin-top:14px"></div></div>
        <div><span class="mono muted">Choose a time</span><div class="times" id="times" style="margin-top:14px"></div></div>
      </div>
      <div class="appt-foot"><span class="mono muted" id="hSum">Phone · choose a date</span><button class="btn btn-dark" id="hConfirm">Confirm Appointment →</button></div>
    </div>
  </div>
</section>

<!-- ECOSYSTEM -->
<section class="section dark eco on-dark">
  <div class="wrap" style="position:relative">
    <div class="sec-num"><b>09</b><i></i>{{ cms('home.eco_label') }}</div>
    <div class="sec-head" style="margin-bottom:0">
      <div><span class="eyebrow">{{ cms('home.eco_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.eco_title') }}</h2></div>
      <p class="lead">{{ cms('home.eco_lead') }}</p>
    </div>
    <div class="eco-map" id="ecoMap" data-href="{{ route('ecosystem') }}">
      <div class="ring" style="inset:0"></div><div class="ring" style="inset:13%"></div>
      <div class="eco-center"><div><b>COSPARK</b><span class="mono copper">Ecosystem</span></div></div>
    </div>
    <div style="text-align:center;margin-top:20px"><a href="{{ route('ecosystem') }}" class="link-arrow" style="color:#fff">{{ cms('home.eco_cta') }}</a></div>
  </div>
</section>

<!-- QR -->
<section class="section">
  <div class="wrap two-col">
    <div>
      <div class="sec-num"><b>10</b><i></i>{{ cms('home.qr_label') }}</div>
      <span class="eyebrow">{{ cms('home.qr_eyebrow') }}</span>
      <h2 style="margin:20px 0 24px">{{ cms('home.qr_title') }}</h2>
      <p class="lead">{{ cms('home.qr_lead') }}</p>
      <p class="mono muted" style="margin:22px 0 30px;font-size:10px;line-height:1.8">{{ cms('home.qr_features') }}</p>
      <a href="{{ route('ecosystem') }}#partners" class="link-arrow">{{ cms('home.qr_cta') }}</a>
    </div>
    <div class="phone rv">
      <div class="phone-in">
        <div class="phone-notch"></div>
        <span class="mono" style="color:#97A1AF;font-size:8.5px">COSPARK member benefit</span>
        <div style="font-size:17px;font-weight:500;margin:10px 0 12px">Monthly Dining Credit</div>
        <span class="pill green" style="font-size:8px">Eligible</span>
        <div class="qr" id="qrHome"></div>
        <p class="mono" style="text-align:center;color:#97A1AF;font-size:8.5px">Valid this month</p>
        <button class="btn btn-copper btn-block btn-sm" style="margin-top:12px" data-toast="Show this code to the partner to redeem.">Redeem Benefit →</button>
      </div>
    </div>
  </div>
</section>

<!-- PRODUCER -->
<section class="section bg-2">
  <div class="wrap">
    <div class="sec-num"><b>11</b><i></i>{{ cms('home.producer_label') }}</div>
    <div class="sec-head">
      <div><span class="eyebrow">{{ cms('home.producer_eyebrow') }}</span><h2 style="margin-top:20px">{{ cms('home.producer_title') }}</h2></div>
      <p class="lead">{{ cms('home.producer_lead') }}</p>
    </div>
    <div class="stats">@foreach (cms_rows('home.producer_stats', 2) as [$value, $label])<div><b>{{ $value }}</b><span class="mono muted">{{ $label }}</span></div>@endforeach</div>
    <div style="display:flex;gap:12px;margin-top:36px;flex-wrap:wrap"><a href="{{ route('producer') }}#apply" class="btn btn-dark">{{ cms('home.producer_cta_primary') }} →</a><a href="{{ route('producer') }}" class="btn btn-line">{{ cms('home.producer_cta_secondary') }}</a></div>
    <p class="mono muted" style="margin-top:22px;font-size:9.5px">{{ cms('home.producer_note') }}</p>
  </div>
</section>

<!-- FINAL CTA -->
<section class="final">
  <div class="wrap">
    <span class="eyebrow">{{ cms('home.final_eyebrow') }}</span>
    <h2 style="margin-top:22px">{{ cms('home.final_title') }}</h2>
    <p class="lead" style="margin:24px auto 0">{{ cms('home.final_lead') }}</p>
    <div class="ctas"><a href="{{ route('quote') }}" class="btn btn-copper">Get a Quote →</a><a href="{{ route('membership') }}" class="btn btn-line">Explore Membership</a><button class="btn" style="background:none" data-appt>Schedule an Appointment</button></div>
    <div class="watermark" aria-hidden="true">COSPARK</div>
    <div class="final-line"></div>
    <p class="mono muted" style="margin-top:22px;font-size:9.5px">{!! collect(cms_rows('home.final_pillars'))->map(fn ($p) => e($p[0]))->implode(' <span class="copper">+</span> ') !!}</p>
  </div>
</section>
@endsection

@push('scripts')
<x-script src="forms.js" />
<x-script src="pages/home.js" />
@endpush
