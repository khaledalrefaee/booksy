@php $isAr = app()->getLocale() === 'ar'; @endphp
<x-front.layout :title="($isAr ? 'مواعيدي' : 'My Appointments') . ' | GlowRez'" :mapFab="false" :noindex="true">
<x-slot:styles>
<style>
.bkf-acc{ padding:calc(var(--bk-nav-h) + var(--bk-s8)) 0 var(--bk-s20); }
.bkf-acc-wrap{ max-width:720px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-acc-head{ margin-bottom:var(--bk-s6); }
.bkf-acc-head h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h2); color:var(--bk-text); margin:0 0 4px; }
.bkf-acc-head p{ color:var(--bk-text-muted); font-size:var(--bk-fs-sm); margin:0; }

/* Segmented tabs — sticky under the nav, thumb-friendly */
.bkf-seg{ position:sticky; top:calc(var(--bk-nav-h) + 8px); z-index:5;
  display:grid; grid-template-columns:1fr 1fr; gap:4px; padding:5px;
  background:var(--bk-surface-2); border:1px solid var(--bk-border); border-radius:var(--bk-r-pill);
  margin-bottom:var(--bk-s6); }
.bkf-seg button{ appearance:none; border:none; cursor:pointer; min-height:44px; border-radius:var(--bk-r-pill);
  font-family:var(--bk-font-ui); font-weight:600; font-size:.92rem; color:var(--bk-text-soft);
  background:transparent; transition:color var(--bk-t) ease,background var(--bk-t) ease; display:inline-flex; align-items:center; justify-content:center; gap:7px; }
.bkf-seg button .bkf-seg-count{ font-size:.75rem; font-weight:700; padding:1px 8px; border-radius:var(--bk-r-pill); background:var(--bk-surface-3); color:var(--bk-text-muted); }
.bkf-seg button.is-active{ background:var(--bk-surface); color:var(--bk-accent); box-shadow:var(--bk-shadow-xs); }
.bkf-seg button.is-active .bkf-seg-count{ background:var(--bk-accent-wash); color:var(--bk-accent); }

.bkf-appt-list{ display:flex; flex-direction:column; gap:var(--bk-s3); }
.bkf-appt-list[hidden]{ display:none; }

/* Appointment card */
.bkf-appt{ display:flex; gap:14px; padding:14px; border-radius:var(--bk-r-lg);
  background:var(--bk-surface); border:1px solid var(--bk-border); box-shadow:var(--bk-shadow-xs);
  text-decoration:none; color:inherit; transition:border-color var(--bk-t) ease,box-shadow var(--bk-t) ease,transform var(--bk-t) ease; }
.bkf-appt:hover{ border-color:var(--bk-border-strong); box-shadow:var(--bk-shadow-sm); transform:translateY(-2px); }
.bkf-appt:active{ transform:translateY(0); }
.bkf-appt-thumb{ flex:0 0 auto; width:74px; height:74px; border-radius:var(--bk-r); object-fit:cover; background:var(--bk-surface-3); }
.bkf-appt-thumb.ph{ display:grid; place-items:center; color:var(--bk-text-muted); }
.bkf-appt-main{ flex:1 1 auto; min-width:0; }
.bkf-appt-top{ display:flex; align-items:center; gap:8px; justify-content:space-between; margin-bottom:3px; }
.bkf-appt-venue{ font-weight:700; font-size:1rem; color:var(--bk-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bkf-appt-svc{ font-size:.9rem; color:var(--bk-text-soft); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bkf-appt-meta{ display:flex; flex-wrap:wrap; align-items:center; gap:6px 14px; margin-top:8px; font-size:.83rem; color:var(--bk-text-muted); }
.bkf-appt-meta span{ display:inline-flex; align-items:center; gap:5px; }
.bkf-appt-meta svg{ color:var(--bk-text-muted); }
.bkf-pill{ flex:0 0 auto; display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:700;
  padding:4px 10px; border-radius:var(--bk-r-pill); white-space:nowrap; }
.bkf-pill i{ width:6px; height:6px; border-radius:50%; background:currentColor; }

/* Empty state */
.bkf-empty{ text-align:center; padding:var(--bk-s16) var(--bk-s6); }
.bkf-empty-ic{ width:64px; height:64px; margin:0 auto var(--bk-s5); border-radius:var(--bk-r-lg);
  display:grid; place-items:center; background:var(--bk-accent-wash); color:var(--bk-accent); }
.bkf-empty h3{ font-size:1.1rem; color:var(--bk-text); margin:0 0 6px; }
.bkf-empty p{ color:var(--bk-text-muted); font-size:.92rem; margin:0 auto var(--bk-s6); max-width:340px; line-height:1.7; }

.bkf-flash{ display:flex; align-items:center; gap:10px; padding:13px 16px; border-radius:var(--bk-r); margin-bottom:var(--bk-s5); font-size:.9rem; font-weight:600; }
.bkf-flash.ok{ background:var(--bk-success-bg); color:var(--bk-success); }
</style>
</x-slot:styles>

@php
    // Status → tinted pill colours reuse the enum's single source of truth.
    $pill = function($status){
        $c = $status->color();
        return "color:$c;background:color-mix(in srgb, $c 14%, transparent);";
    };
    // One card per VISIT (a grouped multi-service booking is a single entry).
    $visitCard = function($v) use ($isAr){
        $a     = $v->primary;
        $img   = $a->branch?->images?->first();
        $venue = $isAr ? ($a->branch->name_ar ?? $a->branch->name_en) : ($a->branch->name_en ?? $a->branch->name_ar);
        $first = $v->service_names->first() ?? '';
        $more  = $v->service_count - 1;
        $svc   = $more > 0
            ? $first . ' ' . ($isAr ? "و+{$more}" : "+{$more}")
            : $first;
        return compact('a','img','venue','svc','v');
    };
@endphp

<section class="bkf-acc">
  <div class="bkf-acc-wrap">

    @if(session('account_success'))
      <div class="bkf-flash ok"><x-icon name="check-circle" :size="18"/>{{ session('account_success') }}</div>
    @endif

    <div class="bkf-acc-head">
      <h1>{{ $isAr ? 'مواعيدي' : 'My appointments' }}</h1>
      <p>{{ $isAr ? 'تابع حجوزاتك القادمة وسجلّ زياراتك السابقة.' : 'Track your upcoming bookings and past visits.' }}</p>
    </div>

    <div class="bkf-seg" role="tablist">
      <button role="tab" class="is-active" data-tab="up" aria-selected="true">
        {{ $isAr ? 'القادمة' : 'Upcoming' }}<span class="bkf-seg-count">{{ $upcomingVisits->count() }}</span>
      </button>
      <button role="tab" data-tab="past" aria-selected="false">
        {{ $isAr ? 'السابقة' : 'Past' }}<span class="bkf-seg-count">{{ $pastVisits->count() }}</span>
      </button>
    </div>

    {{-- Upcoming --}}
    <div class="bkf-appt-list" data-pane="up">
      @forelse($upcomingVisits as $v) @php($c = $visitCard($v))
        <a href="{{ route('account.appointment', $c['a']) }}" class="bkf-appt" data-status-url="{{ route('account.appointment.status', $c['a']) }}" data-status-start="{{ $c['a']->start_time->toDateTimeString() }}">
          @if($c['img'])
            <img src="{{ asset('storage/'.$c['img']->path) }}" alt="{{ $c['venue'] }}" class="bkf-appt-thumb" loading="lazy">
          @else
            <span class="bkf-appt-thumb ph"><x-icon name="scissors" :size="26"/></span>
          @endif
          <div class="bkf-appt-main">
            <div class="bkf-appt-top">
              <span class="bkf-appt-venue">{{ $c['venue'] }}</span>
              <span class="bkf-pill" style="color:{{ $v->status['color'] }};background:color-mix(in srgb, {{ $v->status['color'] }} 14%, transparent)" data-status-pill><i></i><span data-status-label>{{ $v->status['label'] }}</span></span>
            </div>
            <div class="bkf-appt-svc">{{ $c['svc'] }}</div>
            <div class="bkf-appt-meta">
              <span><x-icon name="calendar" :size="15"/>{{ $c['a']->start_time->translatedFormat($isAr ? 'l d/m' : 'D, d/m') }}</span>
              <span><x-icon name="clock" :size="15"/>{{ $c['a']->start_time->format('g:i A') }}</span>
              @if($v->service_count > 1)<span><x-icon name="tag" :size="15"/>{{ $v->service_count }} {{ $isAr ? 'خدمات' : 'services' }}</span>@endif
              @if($v->guest_count > 0)<span><x-icon name="user" :size="15"/>{{ $isAr ? 'أنت + '.$v->guest_count.' ضيف' : 'You + '.$v->guest_count.' guest'.($v->guest_count > 1 ? 's' : '') }}</span>@endif
            </div>
          </div>
        </a>
      @empty
        <div class="bkf-empty">
          <div class="bkf-empty-ic"><x-icon name="calendar" :size="30"/></div>
          <h3>{{ $isAr ? 'لا مواعيد قادمة' : 'No upcoming appointments' }}</h3>
          <p>{{ $isAr ? 'اكتشف أفضل الصالونات القريبة منك واحجز موعدك في ثوانٍ.' : 'Discover great venues near you and book in seconds.' }}</p>
          <a href="{{ route('front.venues') }}" class="bkf-btn bkf-btn-primary">{{ $isAr ? 'استكشف أماكن الجمال والعناية' : 'Explore Beauty & Wellness Venues' }}<x-icon name="arrow-right" :size="18"/></a>
        </div>
      @endforelse
    </div>

    {{-- Past --}}
    <div class="bkf-appt-list" data-pane="past" hidden>
      @forelse($pastVisits as $v) @php($c = $visitCard($v))
        <a href="{{ route('account.appointment', $c['a']) }}" class="bkf-appt">
          @if($c['img'])
            <img src="{{ asset('storage/'.$c['img']->path) }}" alt="{{ $c['venue'] }}" class="bkf-appt-thumb" loading="lazy">
          @else
            <span class="bkf-appt-thumb ph"><x-icon name="scissors" :size="26"/></span>
          @endif
          <div class="bkf-appt-main">
            <div class="bkf-appt-top">
              <span class="bkf-appt-venue">{{ $c['venue'] }}</span>
              <span class="bkf-pill" style="color:{{ $v->status['color'] }};background:color-mix(in srgb, {{ $v->status['color'] }} 14%, transparent)"><i></i>{{ $v->status['label'] }}</span>
            </div>
            <div class="bkf-appt-svc">{{ $c['svc'] }}</div>
            <div class="bkf-appt-meta">
              <span><x-icon name="calendar" :size="15"/>{{ $c['a']->start_time->translatedFormat($isAr ? 'l d/m Y' : 'D, d/m Y') }}</span>
              <span><x-icon name="clock" :size="15"/>{{ $c['a']->start_time->format('g:i A') }}</span>
              @if($v->service_count > 1)<span><x-icon name="tag" :size="15"/>{{ $v->service_count }} {{ $isAr ? 'خدمات' : 'services' }}</span>@endif
            </div>
          </div>
        </a>
      @empty
        <div class="bkf-empty">
          <div class="bkf-empty-ic"><x-icon name="clock" :size="30"/></div>
          <h3>{{ $isAr ? 'لا زيارات سابقة بعد' : 'No past visits yet' }}</h3>
          <p>{{ $isAr ? 'ستظهر هنا زياراتك المكتملة والملغاة.' : 'Your completed and cancelled visits will appear here.' }}</p>
        </div>
      @endforelse
    </div>

  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var seg = document.querySelector('.bkf-seg');
  if(!seg) return;
  var panes = { up: document.querySelector('[data-pane="up"]'), past: document.querySelector('[data-pane="past"]') };
  seg.addEventListener('click', function(e){
    var b = e.target.closest('[data-tab]'); if(!b) return;
    seg.querySelectorAll('button').forEach(function(x){ x.classList.remove('is-active'); x.setAttribute('aria-selected','false'); });
    b.classList.add('is-active'); b.setAttribute('aria-selected','true');
    var t = b.dataset.tab;
    panes.up.hidden = t !== 'up';
    panes.past.hidden = t !== 'past';
  });

  // Live status — poll each upcoming card so a venue confirm/cancel shows here
  // without a refresh (mirrors the detail page). Reloads once if anything changed.
  var cards = [].slice.call(document.querySelectorAll('[data-pane="up"] [data-status-url]'));
  if(cards.length){
    var poll = function(){
      cards.forEach(function(card){
        var pill = card.querySelector('[data-status-pill]');
        var label = card.querySelector('[data-status-label]');
        if(!pill || !label) return;
        var startWas = card.dataset.statusStart || '';
        fetch(card.dataset.statusUrl,{headers:{'X-Requested-With':'XMLHttpRequest'}})
          .then(function(r){ return r.ok ? r.json() : null; })
          .then(function(d){
            if(!d || !d.label) return;
            var labelChanged = d.label !== label.textContent.trim();
            var timeChanged  = d.start && startWas && d.start !== startWas; // venue rescheduled
            if(labelChanged || timeChanged){
              label.textContent = d.label;
              pill.style.color = d.color;
              pill.style.background = 'color-mix(in srgb, ' + d.color + ' 14%, transparent)';
              // A status change or reschedule can move/retime a card — resync once.
              setTimeout(function(){ window.location.reload(); }, 1500);
            }
          }).catch(function(){});
      });
    };
    setInterval(poll, 20000);
    document.addEventListener('visibilitychange', function(){ if(!document.hidden) poll(); });
  }
})();
</script>
</x-slot:scripts>
</x-front.layout>
