{{-- Free / Associate / Premium cards from the admin-edited plans. $page ("home" or "membership") picks
     the CMS feature lists; the membership page also gets the price markup its frequency switch updates.
     A signed-in member sees their plan marked Active; a paid plan locks the other cards (plan changes
     are made from the dashboard), and a Free member upgrades from the dashboard. --}}
@php
    $styles = ['free' => ['plan free', 'btn-line', 'Start'], 'associate' => ['plan', 'btn-dark', 'Choose'], 'premium' => ['plan premium', 'btn-copper', 'Choose']];
    $switcher = $page === 'membership';
    $viewer = auth()->user();
    $owner = $viewer?->isSubAccount() ? $viewer->parent : $viewer;
    $current = $owner && ! $viewer->isAdmin() && ! $viewer->needsOnboarding()
        ? ($owner->membership?->status === 'active' ? $owner->membership->plan : 'free')
        : null;
    // Sub-accounts share the parent's plan and can't change it
    $manage = ! $current || $viewer->isSubAccount() ? null : ($viewer->isProducer() ? $viewer->homeRoute() : route('dashboard').'#membership');
@endphp
<div class="plans">
  @foreach ($styles as $key => [$class, $button, $verb])
  @php
      $plan = config("membership.plans.{$key}");
      $price = \App\Models\MembershipPlan::money($plan['monthly_fee']);
  @endphp
  <div class="{{ $class }}" style="position:relative"><span class="mono{{ $key === 'premium' ? ' tier' : '' }}">{{ $plan['name'] }}</span>@if ($key === $current)<span class="badge-pop">ACTIVE</span>@elseif ($key === 'premium')<span class="badge-pop">POPULAR</span>@endif<div class="plan-price">@if ($key === 'free'){{ $price }}@elseif ($switcher)<span data-p="{{ $plan['monthly_fee'] }}">{{ $price }}</span><small class="per">/ MONTH</small>@else{{ $price }}<small>/ MONTH</small>@endif</div><span class="mono">{{ $plan['tagline'] }}</span><p class="plan-desc">{{ $plan['description'] }}</p>@if ($key !== 'free')<div class="plan-tokens">{{ $plan['dues_tokens'] }}<small>{{ $switcher ? 'DT' : 'DUES TOKENS' }} / MONTH</small></div>@endif<ul>@foreach (cms_rows("{$page}.plan_{$key}_features") as [$feature])<li>{{ $feature }}</li>@endforeach</ul>@if (! $current)<a href="{{ route('register', ['plan' => $key]) }}" class="btn {{ $button }} btn-block">{{ $verb }} {{ $plan['name'] }} →</a>@elseif ($key === $current && $manage)<a href="{{ $manage }}" class="btn {{ $button }} btn-block">Manage membership →</a>@elseif ($current === 'free' && $manage)<a href="{{ $manage }}" class="btn {{ $button }} btn-block">Upgrade to {{ $plan['name'] }} →</a>@else<button type="button" class="btn {{ $button }} btn-block" disabled>{{ $verb }} {{ $plan['name'] }} →</button>@endif</div>
  @endforeach
</div>
