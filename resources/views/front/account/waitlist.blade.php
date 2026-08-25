@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-front.layout :title="($isAr ? 'قائمة الانتظار' : 'Waitlist') . ' | GlowRez'" :mapFab="false" :noindex="true">
<x-slot:styles>
<style>
.bkf-acc{ padding:calc(var(--bk-nav-h) + var(--bk-s8)) 0 var(--bk-s20); }
.bkf-acc-wrap{ max-width:720px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-acc-head{ margin-bottom:var(--bk-s6); }
.bkf-acc-head h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); color:var(--bk-text); margin:0 0 4px; }
.bkf-acc-head p{ color:var(--bk-text-muted); font-size:var(--bk-fs-sm); margin:0; }
.bkf-flash{ display:flex; align-items:center; gap:10px; padding:13px 16px; border-radius:var(--bk-r); margin-bottom:var(--bk-s5); font-size:.9rem; font-weight:600; }
.bkf-flash.ok{ background:var(--bk-success-bg); color:var(--bk-success); }
.wl-list{ display:flex; flex-direction:column; gap:var(--bk-s3); }
.wl-card{ display:flex; gap:14px; align-items:center; padding:16px; border-radius:var(--bk-r-lg);
  background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-xs); }
.wl-ic{ flex:0 0 auto; width:46px; height:46px; border-radius:var(--bk-r); display:grid; place-items:center;
  background:var(--bk-accent-wash); color:var(--bk-accent); }
.wl-main{ flex:1 1 auto; min-width:0; }
.wl-venue{ font-weight:700; font-size:1rem; color:var(--bk-text); }
.wl-svc{ font-size:.9rem; color:var(--bk-text-soft); }
.wl-meta{ display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:6px; font-size:.83rem; color:var(--bk-text-muted); }
.wl-meta span{ display:inline-flex; align-items:center; gap:5px; }
.wl-pill{ flex:0 0 auto; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:var(--bk-r-pill); }
.wl-pill.waiting{ color:var(--bk-gold-strong); background:var(--bk-gold-soft); }
.wl-pill.notified{ color:var(--bk-success); background:var(--bk-success-bg); }
.wl-leave{ appearance:none; border:1px solid var(--bk-border); background:var(--bk-surface); cursor:pointer;
  color:var(--bk-danger); border-radius:var(--bk-r-pill); padding:7px 14px; font-family:var(--bk-font-ui); font-weight:600; font-size:.82rem; }
.wl-leave:hover{ border-color:var(--bk-danger); }
.bkf-empty{ text-align:center; padding:var(--bk-s16) var(--bk-s6); }
.bkf-empty-ic{ width:64px; height:64px; margin:0 auto var(--bk-s5); border-radius:var(--bk-r-lg); display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.bkf-empty h3{ font-size:1.1rem; color:var(--bk-text); margin:0 0 6px; }
.bkf-empty p{ color:var(--bk-text-muted); font-size:.92rem; margin:0 auto var(--bk-s6); max-width:340px; line-height:1.7; }
</style>
</x-slot:styles>

<section class="bkf-acc">
  <div class="bkf-acc-wrap">
    @if(session('account_success'))
      <div class="bkf-flash ok"><x-icon name="check-circle" :size="18"/>{{ session('account_success') }}</div>
    @endif

    <div class="bkf-acc-head">
      <h1>{{ $isAr ? 'قائمة الانتظار' : 'Waitlist' }}</h1>
      <p>{{ $isAr ? 'سنُعلمك فور توفّر موعد في الأيام التي تنتظرها.' : 'We’ll tell you the moment a slot opens on the days you’re waiting for.' }}</p>
    </div>

    @forelse($entries as $e)
      @php
        $venue = $isAr ? ($e->branch->name_ar ?? $e->branch->name_en) : ($e->branch->name_en ?? $e->branch->name_ar);
        $svc   = $e->service ? ($isAr ? ($e->service->name_ar ?? $e->service->name_en) : ($e->service->name_en ?? $e->service->name_ar)) : ($isAr ? 'أي خدمة' : 'Any service');
        $win   = ($e->pref_from && $e->pref_to) ? ' · ' . \Illuminate\Support\Carbon::parse($e->pref_from)->format('g:i A') . '–' . \Illuminate\Support\Carbon::parse($e->pref_to)->format('g:i A') : '';
      @endphp
      <div class="wl-list">
        <div class="wl-card">
          <span class="wl-ic"><x-icon name="clock" :size="22"/></span>
          <div class="wl-main">
            <div class="wl-venue">{{ $venue }}</div>
            <div class="wl-svc">{{ $svc }}</div>
            <div class="wl-meta">
              <span><x-icon name="calendar" :size="15"/>{{ $e->preferred_date->translatedFormat($isAr ? 'l d/m' : 'D, d/m') }}{{ $win }}</span>
              <span class="wl-pill {{ $e->status }}">{{ $e->status === 'notified' ? ($isAr ? 'توفّر موعد!' : 'Slot opened!') : ($isAr ? 'بالانتظار' : 'Waiting') }}</span>
            </div>
          </div>
          <form method="POST" action="{{ route('account.waitlist.leave', $e) }}">
            @csrf @method('DELETE')
            <button type="submit" class="wl-leave">{{ $isAr ? 'مغادرة' : 'Leave' }}</button>
          </form>
        </div>
      </div>
    @empty
      <div class="bkf-empty">
        <div class="bkf-empty-ic"><x-icon name="clock" :size="30"/></div>
        <h3>{{ $isAr ? 'قائمة انتظارك فارغة' : 'Your waitlist is empty' }}</h3>
        <p>{{ $isAr ? 'إذا كان اليوم الذي تريده ممتلئاً، اضغط «أخبرني عند التوفّر» أثناء الحجز.' : 'If a day you want is full, tap “Notify me” while booking.' }}</p>
        <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-primary">{{ $isAr ? 'استكشف الأماكن' : 'Explore venues' }}<x-icon name="arrow-right" :size="18"/></a>
      </div>
    @endforelse
  </div>
</section>
</x-front.layout>
