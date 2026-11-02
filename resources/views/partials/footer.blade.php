<footer class="site-footer"><div class="wrap">
    <div class="foot-grid">
      <div>
        <a class="logo" href="{{ route('home') }}" style="color:#fff">@include('partials.logo-mark')<span class="logo-t"><b>COSPARK</b><span>INSURANCE GROUP</span></span></a>
        <p style="margin:22px 0 6px">{{ cms('layout.footer_tagline') }}</p>
        <p class="small" style="color:#8C96A4;margin:0">{{ $office['address_line1'] }}<br>{{ $office['address_line2'] }}<br><a href="tel:{{ $office['phone_tel'] }}" style="display:inline;padding:0">{{ $office['phone'] }}</a></p>
        <p class="mono" style="color:#7E8896;margin-top:14px;font-size:9.5px">{{ $office['hours'] }}</p>
        <button class="link-arrow" data-appt style="color:#E3A876;margin-top:12px">{{ cms('layout.footer_meeting') }}</button>
        <div class="socials">
          @if ($socialUrl = \App\Support\PublicLink::external(config('cospark.social.facebook')))
          <a href="{{ $socialUrl }}" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/></svg></a>
          @endif
          @if ($socialUrl = \App\Support\PublicLink::external(config('cospark.social.instagram')))
          <a href="{{ $socialUrl }}" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/></svg></a>
          @endif
          @if ($socialUrl = \App\Support\PublicLink::external(config('cospark.social.linkedin')))
          <a href="{{ $socialUrl }}" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 9h4v12H4zM6 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10 9h4v1.7c.6-1 2-2 4-2 4 0 4 2.7 4 6.3v6h-4v-5.3c0-1.3 0-3-1.8-3S14 14 14 15.6V21h-4z"/></svg></a>
          @endif
        </div>
      </div>
      <div><h6>Quick links</h6>@foreach ($nav->links('footer_quick') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div>
      <div><h6>COSPARK ecosystem</h6>@foreach ($nav->links('footer_ecosystem') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div>
      <div><h6>Appointments</h6><a href="#" data-appt>Schedule Appointment</a><a href="#" data-appt="phone">Phone Appointment</a><a href="#" data-appt="inperson">In-Person Appointment</a>
        <h6 style="margin-top:28px">Members</h6><a href="{{ route('login') }}">Sign In</a><a href="{{ route('register') }}">Join COSPARK</a><a href="{{ route('dashboard') }}">Member Dashboard</a><a href="{{ auth()->user()?->isProducer() ? route('producer.dashboard') : route('login', ['role' => 'producer']) }}">Producer Portal</a></div>
      <div><h6>Legal</h6>@foreach ($nav->links('footer_legal') as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div>
    </div>
    <p class="disclaimer">{{ cms('layout.footer_disclaimer') }}</p>
    <div class="foot-bottom"><span>© {{ date('Y') }} COSPARK Insurance Group · {{ $office['dba'] }}</span><span><a href="{{ route('legal') }}#privacy">Privacy Policy</a><a href="{{ route('legal') }}#terms">Terms & Conditions</a></span></div>
  </div></footer>
