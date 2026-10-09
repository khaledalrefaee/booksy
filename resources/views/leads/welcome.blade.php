@extends('leads.layout')

@section('title', $isAr ? 'GlowRez — إدارة أذكى لأعمال الجمال والرفاهية' : 'GlowRez — Smarter management for beauty & wellness')
@section('description', $isAr
    ? 'GlowRez يجمع الحجوزات والعملاء والموظفين والإدارة في تجربة واحدة. اختر طريقك: عميل، صاحب منشأة، أو مهتم بالانضمام مبكراً.'
    : 'GlowRez brings bookings, clients, staff and management into one experience. Choose your way in: client, business owner, or early-interest.')
@section('body-class', 'gl-page-welcome')

@php
    // Headline split into words so it can rise in one by one; the starred word carries the gold.
    $words = $isAr
        ? [['إدارة'], ['أذكى', 1], ['لأعمال'], ['الجمال'], ['والرفاهية.']]
        : [['Smarter', 1], ['management'], ['for'], ['beauty'], ['&'], ['wellness.']];
@endphp

@section('content')
<div class="gw">

  <h1 class="gw-h1" aria-label="{{ $t('إدارة أذكى لأعمال الجمال والرفاهية.', 'Smarter management for beauty & wellness.') }}">
    @foreach($words as $i => $w)
      <span class="gw-w {{ ! empty($w[1]) ? 'is-gold' : '' }}" style="--i:{{ $i }}" aria-hidden="true">{{ $w[0] }}</span>
    @endforeach
  </h1>

  <p class="gw-lead gl-rise" style="--i:5">
    {{ $t('GlowRez يجمع الحجوزات والعملاء والموظفين والإدارة في تجربة واحدة.', 'GlowRez brings bookings, clients, staff and management into one experience.') }}
  </p>

  <nav class="gw-paths" aria-label="{{ $t('اختر طريقك', 'Choose your way in') }}">
    <a class="gw-path gl-rise" style="--i:6" href="{{ route('front.index', ['enter' => 'customer']) }}" data-path="customer">
      <span class="gw-path-ic"><x-leads.icon name="sparkles" :size="22"/></span>
      <span class="gw-path-txt">
        <strong>{{ $t('أنا عميل', 'I’m a client') }}</strong>
        <span>{{ $t('اكتشف تجربة GlowRez', 'Discover the GlowRez experience') }}</span>
      </span>
      <span class="gw-path-go"><x-leads.icon name="arrow" :size="18" class="gl-flip"/></span>
    </a>

    <a class="gw-path gl-rise" style="--i:7" href="{{ route('front.business') }}" data-path="business">
      <span class="gw-path-ic"><x-leads.icon name="store" :size="22"/></span>
      <span class="gw-path-txt">
        <strong>{{ $t('أنا صاحب منشأة', 'I own a business') }}</strong>
        <span>{{ $t('اكتشف حلول GlowRez للأعمال', 'Explore GlowRez for business') }}</span>
      </span>
      <span class="gw-path-go"><x-leads.icon name="arrow" :size="18" class="gl-flip"/></span>
    </a>

    <a class="gw-path gw-path--primary gl-rise" style="--i:8" href="{{ route('leads.join') }}" data-path="interest">
      <span class="gw-path-ic"><x-leads.icon name="briefcase" :size="22"/></span>
      <span class="gw-path-txt">
        <strong>{{ $t('أنا مهتم', 'I’m interested') }}</strong>
        <span>{{ $t('كن من أوائل المنشآت على GlowRez', 'Be among the first businesses on GlowRez') }}</span>
      </span>
      <span class="gw-path-go"><x-leads.icon name="arrow" :size="18" class="gl-flip"/></span>
    </a>
  </nav>

  <div class="gw-statuses">
  <p class="gw-status gl-rise" style="--i:9">
    <span class="gw-dot" aria-hidden="true"></span>
    {{ $t('قريباً — الانضمام المبكر مفتوح', 'Coming soon — early access is open') }}
  </p>
  <p class="gw-status gl-rise" style="--i:10">
    <x-leads.icon name="lock" :size="15"/>{{ $t('بدون حساب أو كلمة مرور', 'No account or password needed') }}
  </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
/* A soft light follows the finger / cursor across each path (decorative; links work without it). */
(function () {
  document.querySelectorAll('.gw-path').forEach(function (el) {
    function move(e) {
      var r = el.getBoundingClientRect();
      el.style.setProperty('--x', (e.clientX - r.left) + 'px');
      el.style.setProperty('--y', (e.clientY - r.top) + 'px');
    }
    el.addEventListener('pointerenter', move);
    el.addEventListener('pointermove', move);
    el.addEventListener('pointerdown', function (e) { move(e); el.classList.add('is-lit'); });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (n) { el.addEventListener(n, function () { el.classList.remove('is-lit'); }); });
  });
})();
</script>
@endpush
