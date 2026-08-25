@php
    $isAr  = app()->getLocale() === 'ar';
    $a     = $appointment;
    $b     = $a->branch;
    $venue = $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar);
    $svc   = $a->service ? ($isAr ? ($a->service->name_ar ?? $a->service->name_en) : ($a->service->name_en ?? $a->service->name_ar)) : '';
    $emp   = $a->employee ? ($isAr ? ($a->employee->name_ar ?? $a->employee->name_en) : ($a->employee->name_en ?? $a->employee->name_ar)) : null;
    $img   = $b?->images?->first();
    $cur   = $isAr ? 'ل.س' : 'SYP';
    $sv    = \App\Http\Controllers\CustomerAccountController::statusView($a, $isAr);
    $sc    = $sv['color'];
    $mapUrl= ($b->latitude && $b->longitude)
        ? 'https://www.google.com/maps/search/?api=1&query='.$b->latitude.','.$b->longitude
        : ($b->address ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($b->address) : null);
@endphp
<x-front.layout :title="$venue . ' | GlowRez'" :mapFab="false" :noindex="true">
<x-slot:styles>
<style>
.bkf-ad{ padding:calc(var(--bk-nav-h) + var(--bk-s5)) 0 var(--bk-s20); }
.bkf-ad-wrap{ max-width:640px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-ad-back{ display:inline-flex; align-items:center; gap:6px; font-size:.88rem; font-weight:600; color:var(--bk-text-soft); margin-bottom:var(--bk-s5); text-decoration:none; }
.bkf-ad-back:hover{ color:var(--bk-accent); }

.bkf-ad-hero{ position:relative; border-radius:var(--bk-r-xl); overflow:hidden; aspect-ratio:16/9; background:var(--bk-surface-3); margin-bottom:-40px; }
.bkf-ad-hero img{ width:100%; height:100%; object-fit:cover; }
.bkf-ad-hero.ph{ display:grid; place-items:center; color:var(--bk-text-muted); background:var(--bk-grad-accent-soft); }
.bkf-ad-hero::after{ content:''; position:absolute; inset:0; background:var(--bk-grad-sheen); }
.bkf-ad-status{ position:absolute; top:14px; inset-inline-end:14px; z-index:2; display:inline-flex; align-items:center; gap:6px;
  font-size:.78rem; font-weight:700; padding:6px 13px; border-radius:var(--bk-r-pill);
  background:var(--bk-surface); box-shadow:var(--bk-shadow-sm); }
.bkf-ad-status i{ width:7px; height:7px; border-radius:50%; }

.bkf-ad-card{ position:relative; z-index:2; background:var(--bk-surface); border:1px solid var(--bk-border);
  border-radius:var(--bk-r-xl); box-shadow:var(--bk-shadow); padding:var(--bk-s6) var(--bk-s6) var(--bk-s5); }
.bkf-ad-venue{ font-family:var(--bk-font-display); font-size:1.5rem; color:var(--bk-text); margin:0 0 2px; }
.bkf-ad-svc{ color:var(--bk-text-soft); font-size:.98rem; margin-bottom:var(--bk-s5); }

.bkf-ad-services{ display:flex; flex-direction:column; gap:8px; margin-bottom:var(--bk-s5); }
.bkf-ad-svc-row{ display:flex; align-items:center; gap:12px; padding:11px 13px; border-radius:var(--bk-r); background:var(--bk-surface-2); border:1px solid var(--bk-border); }
.bkf-ad-svc-row .t{ flex:0 0 auto; font-weight:700; font-size:.82rem; color:var(--bk-accent-strong); font-variant-numeric:tabular-nums; min-width:64px; }
.bkf-ad-svc-row .m{ flex:1 1 auto; min-width:0; display:flex; flex-direction:column; }
.bkf-ad-svc-row .n{ font-weight:600; font-size:.92rem; color:var(--bk-text); }
.bkf-ad-svc-row .s{ font-size:.78rem; color:var(--bk-text-muted); }
.bkf-ad-svc-row .p{ flex:0 0 auto; font-weight:700; font-size:.85rem; color:var(--bk-text-soft); font-variant-numeric:tabular-nums; }
.bkf-ad-when{ display:flex; align-items:center; gap:14px; padding:16px; border-radius:var(--bk-r-lg);
  background:var(--bk-accent-wash); margin-bottom:var(--bk-s5); }
.bkf-ad-when .ic{ flex:0 0 auto; width:46px; height:46px; border-radius:var(--bk-r); display:grid; place-items:center; background:var(--bk-surface); color:var(--bk-accent); box-shadow:var(--bk-shadow-xs); }
.bkf-ad-when b{ display:block; font-size:1.05rem; color:var(--bk-text); }
.bkf-ad-when small{ color:var(--bk-text-muted); font-size:.85rem; }

.bkf-ad-rows{ display:flex; flex-direction:column; gap:2px; }
.bkf-ad-row{ display:flex; align-items:center; gap:12px; padding:12px 4px; border-top:1px solid var(--bk-border); }
.bkf-ad-row:first-child{ border-top:none; }
.bkf-ad-row svg{ flex:0 0 auto; color:var(--bk-text-muted); }
.bkf-ad-row .k{ font-size:.85rem; color:var(--bk-text-muted); }
.bkf-ad-row .v{ margin-inline-start:auto; font-weight:600; color:var(--bk-text); font-size:.92rem; text-align:end; }
.bkf-ad-row a.v{ color:var(--bk-accent); text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
.bkf-ad-price .v{ font-size:1.05rem; color:var(--bk-accent-strong); }
.bkf-ad-notes{ margin-top:var(--bk-s4); padding:12px 14px; border-radius:var(--bk-r); background:var(--bk-surface-2); font-size:.9rem; color:var(--bk-text-soft); line-height:1.6; }

.bkf-ad-actions{ display:flex; flex-direction:column; gap:10px; margin-top:var(--bk-s6); }
.bkf-ad-mini{ display:flex; gap:10px; }
.bkf-ad-mini > *{ flex:1; }
@media (max-width:380px){ .bkf-ad-mini{ flex-direction:column; } }
.bkf-btn-danger-soft{ background:var(--bk-danger-bg); color:var(--bk-danger); border-color:transparent; }
.bkf-btn-danger-soft:hover{ filter:brightness(.98); transform:translateY(-1px); }
.bkf-btn-danger{ background:var(--bk-danger); color:#fff; border-color:transparent; }

/* Two-step cancel confirm */
.bkf-ad-confirm{ margin-top:var(--bk-s6); padding:18px; border-radius:var(--bk-r-lg); background:var(--bk-danger-bg); border:1px solid color-mix(in srgb,var(--bk-danger) 30%,transparent); }
.bkf-ad-confirm[hidden]{ display:none; }
.bkf-ad-confirm p{ display:flex; align-items:flex-start; gap:9px; margin:0 0 14px; font-size:.92rem; color:var(--bk-text); line-height:1.6; }
.bkf-ad-confirm p svg{ flex:0 0 auto; color:var(--bk-danger); margin-top:1px; }
.bkf-ad-confirm-row{ display:flex; gap:10px; }
.bkf-ad-confirm-row .bkf-btn{ flex:1; }
.bkf-ad-policy{ display:flex; gap:9px; align-items:flex-start; padding:10px 12px; border-radius:var(--bk-r); margin-bottom:14px;
  background:var(--bk-success-bg); color:var(--bk-success); font-size:.85rem; line-height:1.55; }
.bkf-ad-policy svg{ flex:0 0 auto; margin-top:1px; }
.bkf-ad-policy.is-late{ background:var(--bk-danger-bg); color:var(--bk-danger); }
.bkf-ad-reasons{ display:flex; flex-direction:column; gap:8px; margin-bottom:12px; }
.bkf-ad-reason{ display:flex; align-items:center; gap:10px; padding:11px 13px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); background:var(--bk-surface); cursor:pointer; font-size:.92rem; color:var(--bk-text); transition:border-color var(--bk-t) ease, background var(--bk-t) ease; }
.bkf-ad-reason:hover{ border-color:var(--bk-danger); }
.bkf-ad-reason input{ accent-color:var(--bk-danger); width:17px; height:17px; flex-shrink:0; }
.bkf-ad-reason.sel{ border-color:var(--bk-danger); background:var(--bk-danger-bg); }
.bkf-ad-reason-note{ width:100%; min-height:64px; padding:10px 12px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.92rem; resize:vertical; outline:none; transition:border-color var(--bk-t) ease; }
.bkf-ad-reason-note:focus{ border-color:var(--bk-danger); }

/* reschedule slots */
.bkf-resched-slots{ margin-top:12px; min-height:44px; }
.bkf-resched-hint{ margin:0; font-size:.85rem; color:var(--bk-text-muted); }
.bkf-slot-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(74px,1fr)); gap:8px; }
.bkf-slot{ padding:9px 6px; border-radius:var(--bk-r-sm); border:1.5px solid var(--bk-border); background:var(--bk-surface);
  color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.84rem; font-weight:600; cursor:pointer; text-align:center;
  font-variant-numeric:tabular-nums; transition:border-color var(--bk-t) ease, background var(--bk-t) ease; }
.bkf-slot:hover{ border-color:var(--bk-accent); }
.bkf-slot.sel{ border-color:var(--bk-accent); background:var(--bk-accent); color:#fff; }

/* timeline */
.bkf-timeline-card{ margin-top:var(--bk-s5); }
.bkf-tl-title{ font-family:var(--bk-font-display); font-size:1.15rem; color:var(--bk-text); margin:0 0 var(--bk-s4); }
.bkf-tl{ list-style:none; margin:0; padding:0; position:relative; }
.bkf-tl::before{ content:""; position:absolute; inset-block:6px 6px; inset-inline-start:6px; width:2px; background:var(--bk-border); }
.bkf-tl-item{ position:relative; padding-inline-start:26px; padding-block:8px; }
.bkf-tl-dot{ position:absolute; inset-inline-start:0; top:13px; width:14px; height:14px; border-radius:50%; background:var(--bk-surface); border:3px solid var(--bk-accent); }
.bkf-tl-when{ font-size:.76rem; color:var(--bk-text-muted); font-variant-numeric:tabular-nums; }
.bkf-tl-what{ font-size:.95rem; font-weight:600; color:var(--bk-text); }
.bkf-tl-reason{ font-size:.84rem; color:var(--bk-text-soft); margin-top:2px; }

.bkf-flash{ display:flex; align-items:center; gap:10px; padding:13px 16px; border-radius:var(--bk-r); margin-bottom:var(--bk-s5); font-size:.9rem; font-weight:600; }
.bkf-flash.ok{ background:var(--bk-success-bg); color:var(--bk-success); }
.bkf-flash.err{ background:var(--bk-danger-bg); color:var(--bk-danger); }

/* Review block */
.bkf-ad-review{ margin-top:var(--bk-s5); padding:18px; border-radius:var(--bk-r-lg); background:var(--bk-surface-2); border:1px solid var(--bk-border); }
.bkf-ad-review h3{ font-size:1.02rem; color:var(--bk-text); margin:0 0 3px; }
.bkf-ad-review .sub{ font-size:.85rem; color:var(--bk-text-muted); margin:0 0 14px; }
.bkf-stars{ display:flex; gap:4px; margin-bottom:14px; }
.bkf-star{ display:grid; place-items:center; width:46px; height:46px; border:none; background:transparent; cursor:pointer; color:var(--bk-border-strong); transition:color var(--bk-t-fast) ease,transform var(--bk-t-fast) var(--bk-spring); }
.bkf-star:active{ transform:scale(.9); }
.bkf-star.on{ color:var(--bk-star); }
.bkf-star svg{ width:34px; height:34px; }
.bkf-review-comment{ width:100%; min-height:88px; padding:12px 14px; border-radius:var(--bk-r); border:1.5px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.95rem; line-height:1.6; resize:vertical; outline:none; transition:border-color var(--bk-t) ease; }
.bkf-review-comment:focus{ border-color:var(--bk-accent); }
.bkf-ad-review .bkf-btn{ margin-top:12px; }
.bkf-stars-static{ display:flex; gap:3px; color:var(--bk-star); margin-bottom:8px; }
.bkf-ad-review.done p{ font-size:.95rem; color:var(--bk-text-soft); line-height:1.7; margin:0; }
</style>
</x-slot:styles>

<section class="bkf-ad">
  <div class="bkf-ad-wrap">
    <a href="{{ route('account.appointments') }}" class="bkf-ad-back">
      <x-icon name="{{ $isAr ? 'chevron-right' : 'chevron-left' }}" :size="18"/>{{ $isAr ? 'مواعيدي' : 'My appointments' }}
    </a>

    @if(session('account_success'))
      <div class="bkf-flash ok"><x-icon name="check-circle" :size="18"/>{{ session('account_success') }}</div>
    @endif
    @if(session('account_error'))
      <div class="bkf-flash err"><x-icon name="alert" :size="18"/>{{ session('account_error') }}</div>
    @endif

    <div class="bkf-ad-hero {{ $img ? '' : 'ph' }}">
      @if($img)<img src="{{ asset('storage/'.$img->path) }}" alt="{{ $venue }}">@else<x-icon name="scissors" :size="46"/>@endif
      <span class="bkf-ad-status" style="color:{{ $sc }}" data-status-badge data-status-url="{{ route('account.appointment.status', $a) }}" data-status-start="{{ $a->start_time->toDateTimeString() }}"><i style="background:{{ $sc }}" data-status-dot></i><span data-status-label>{{ $sv['label'] }}</span></span>
    </div>

    <div class="bkf-ad-card">
      <h1 class="bkf-ad-venue">{{ $venue }}</h1>
      <div class="bkf-ad-svc">
        @if($visitRows->count() > 1){{ $visitRows->count() }} {{ $isAr ? 'خدمات في زيارة واحدة' : 'services in one visit' }}@else{{ $svc }}@endif
      </div>

      @if($visitRows->count() > 1)
      <div class="bkf-ad-services">
        @foreach($visitRows as $r)
          @php
            $rsvc = $r->service ? ($isAr ? ($r->service->name_ar ?? $r->service->name_en) : ($r->service->name_en ?? $r->service->name_ar)) : '';
            $remp = ($r->employee_requested && $r->employee) ? ($isAr ? ($r->employee->name_ar ?? $r->employee->name_en) : ($r->employee->name_en ?? $r->employee->name_ar)) : null;
            $rmeta = ($r->service?->duration_minutes) . ' ' . ($isAr ? 'دقيقة' : 'min');
            if ($remp) { $rmeta .= ' · 👤 ' . $remp; }
            if ($r->customer_name) { $rmeta .= ' · 🧑‍🤝‍🧑 ' . $r->customer_name; }
          @endphp
          <div class="bkf-ad-svc-row">
            <span class="t">{{ $r->start_time->format('g:i A') }}</span>
            <div class="m">
              <span class="n">{{ $rsvc }}</span>
              <span class="s">{{ $rmeta }}</span>
            </div>
            <span class="p">{{ number_format((float)$r->total_price) }} {{ $cur }}</span>
          </div>
        @endforeach
      </div>
      @endif

      <div class="bkf-ad-when">
        <span class="ic"><x-icon name="calendar-check" :size="24"/></span>
        <div>
          <b>{{ $a->start_time->translatedFormat('l') }} · {{ $a->start_time->format('d/m') }} — {{ $a->start_time->format('g:i A') }}</b>
          <small>{{ $a->start_time->format('g:i A') }} – {{ $a->end_time->format('g:i A') }} · {{ $a->start_time->translatedFormat('d F Y') }}</small>
        </div>
      </div>

      <div class="bkf-ad-rows">
        @if($visitRows->count() === 1)
          @if($emp)
          <div class="bkf-ad-row"><x-icon name="user" :size="18"/><span class="k">{{ $isAr ? 'الموظف' : 'Specialist' }}</span><span class="v">{{ $emp }}</span></div>
          @endif
          <div class="bkf-ad-row"><x-icon name="clock" :size="18"/><span class="k">{{ $isAr ? 'المدة' : 'Duration' }}</span><span class="v">{{ $a->service?->duration_minutes }} {{ $isAr ? 'دقيقة' : 'min' }}</span></div>
        @endif
        @if($a->reference)
        <div class="bkf-ad-row"><x-icon name="tag" :size="18"/><span class="k">{{ $isAr ? 'رقم الحجز' : 'Booking ref' }}</span><span class="v" style="font-variant-numeric:tabular-nums;letter-spacing:.03em">{{ $a->reference }}</span></div>
        @endif
        <div class="bkf-ad-row bkf-ad-price"><x-icon name="tag" :size="18"/><span class="k">{{ $isAr ? ($visitRows->count() > 1 ? 'الإجمالي' : 'السعر') : ($visitRows->count() > 1 ? 'Total' : 'Price') }}</span><span class="v">{{ number_format($visitTotal) }} {{ $cur }}</span></div>
        @if($b->address)
        <div class="bkf-ad-row"><x-icon name="map-pin" :size="18"/><span class="k">{{ $isAr ? 'العنوان' : 'Address' }}</span>
          @if($mapUrl)<a class="v" href="{{ $mapUrl }}" target="_blank" rel="noopener">{{ $isAr ? 'الخريطة' : 'View map' }}<x-icon name="arrow-up-right" :size="14"/></a>@else<span class="v">{{ $b->address }}</span>@endif
        </div>
        @endif
      </div>

      @if($a->notes)
        <div class="bkf-ad-notes"><strong>{{ $isAr ? 'ملاحظات: ' : 'Notes: ' }}</strong>{{ $a->notes }}</div>
      @endif

      @if(!empty($cancelReason))
        <div class="bkf-ad-notes" style="background:var(--bk-danger-bg);color:var(--bk-danger)">
          <strong>{{ $isAr ? 'سبب الإلغاء: ' : 'Cancellation reason: ' }}</strong>{{ $cancelReason }}
        </div>
      @endif

      @if($canReview)
        <div class="bkf-ad-review">
          <h3>{{ $isAr ? 'قيّم زيارتك' : 'Rate your visit' }}</h3>
          <p class="sub">{{ $isAr ? 'رأيك يساعد عملاء آخرين ويحفّز المكان.' : 'Your feedback helps other customers and the venue.' }}</p>
          <form method="POST" action="{{ route('account.appointment.review', $a) }}" data-review-form>
            @csrf
            <input type="hidden" name="rating" value="" data-rating-value required>
            <div class="bkf-stars" data-stars role="radiogroup" aria-label="{{ $isAr ? 'التقييم' : 'Rating' }}">
              @for($i = 1; $i <= 5; $i++)
                <button type="button" class="bkf-star" data-val="{{ $i }}" role="radio" aria-checked="false" aria-label="{{ $i }} {{ $isAr ? 'نجوم' : 'stars' }}"><x-icon name="star-fill" :size="34"/></button>
              @endfor
            </div>
            <textarea name="comment" class="bkf-review-comment" maxlength="1000" placeholder="{{ $isAr ? 'اكتب رأيك (اختياري)…' : 'Write your thoughts (optional)…' }}"></textarea>
            <button type="submit" class="bkf-btn bkf-btn-primary bkf-btn-block" data-review-submit disabled>{{ $isAr ? 'نشر التقييم' : 'Post review' }}</button>
          </form>
        </div>
      @elseif($a->review)
        <div class="bkf-ad-review done">
          <h3>{{ $isAr ? 'تقييمك' : 'Your review' }}</h3>
          <div class="bkf-stars-static">@for($s = 1; $s <= 5; $s++)<x-icon name="{{ $s <= $a->review->rating ? 'star-fill' : 'star' }}" :size="20"/>@endfor</div>
          @if($a->review->comment)<p>{{ $a->review->comment }}</p>@endif
        </div>
      @endif

      <div class="bkf-ad-actions">
        <a href="{{ route('front.branch', $b) }}" class="bkf-btn bkf-btn-primary bkf-btn-block">
          <x-icon name="rotate" :size="18"/>{{ $isAr ? 'احجز مجدداً' : 'Book again' }}
        </a>

        <div class="bkf-ad-mini">
          @if($a->start_time->isFuture())
            <a href="{{ route('account.appointment.calendar', $a) }}" class="bkf-btn bkf-btn-soft">
              <x-icon name="calendar" :size="17"/>{{ $isAr ? 'أضف للتقويم' : 'Add to calendar' }}
            </a>
          @endif
          <button type="button" class="bkf-btn bkf-btn-soft" data-share
                  data-share-url="{{ route('front.branch', $b) }}"
                  data-share-title="{{ $venue }}"
                  data-share-text="{{ $isAr ? 'موعدي في '.$venue.' عبر غلوريز' : 'My appointment at '.$venue.' via GlowRez' }}">
            <x-icon name="arrow-up-right" :size="17"/>{{ $isAr ? 'مشاركة' : 'Share' }}
          </button>
        </div>

        @if($canReschedule)
          <button type="button" class="bkf-btn bkf-btn-soft bkf-btn-block" data-resched-open
                  data-service="{{ $a->service_id }}" data-employee="{{ $a->employee_id }}"
                  data-slots-url="{{ route('booking.slots') }}">
            <x-icon name="rotate" :size="18"/>{{ $isAr ? 'إعادة جدولة الموعد' : 'Reschedule' }}
          </button>
        @endif

        @if($canCancel)
          <button type="button" class="bkf-btn bkf-btn-danger-soft bkf-btn-block" data-cancel-open>
            <x-icon name="x" :size="18"/>{{ $isAr ? 'إلغاء الموعد' : 'Cancel appointment' }}
          </button>
        @endif
      </div>

      @if($canReschedule)
      <div class="bkf-ad-confirm" data-resched-panel hidden style="background:var(--bk-accent-wash);border-color:color-mix(in srgb,var(--bk-accent) 30%,transparent)">
        <p><x-icon name="calendar" :size="20" style="color:var(--bk-accent)"/>{{ $isAr ? 'اختر يوماً ووقتاً جديدين لموعدك.' : 'Pick a new day and time for your visit.' }}</p>
        <form method="POST" action="{{ route('account.appointment.reschedule', $a) }}" data-resched-form>
          @csrf
          <input type="hidden" name="start_time" data-resched-start>
          <input type="date" class="bkf-ad-reason-note" data-resched-date
                 min="{{ now()->format('Y-m-d') }}" value="{{ $a->start_time->format('Y-m-d') }}" style="min-height:auto;padding:11px 12px">
          <div class="bkf-resched-slots" data-resched-slots><p class="bkf-resched-hint">{{ $isAr ? 'اختر يوماً لعرض الأوقات المتاحة.' : 'Choose a day to see available times.' }}</p></div>
          <div class="bkf-ad-confirm-row" style="margin-top:12px">
            <button type="button" class="bkf-btn bkf-btn-soft" data-resched-close>{{ $isAr ? 'تراجع' : 'Cancel' }}</button>
            <button type="submit" class="bkf-btn bkf-btn-primary bkf-btn-block" data-resched-submit disabled>{{ $isAr ? 'تأكيد الوقت الجديد' : 'Confirm new time' }}</button>
          </div>
        </form>
      </div>
      @endif

      @if($canCancel)
      <div class="bkf-ad-confirm" data-cancel-panel hidden>
        <p><x-icon name="alert" :size="20"/>{{ $isAr ? 'يؤسفنا ذلك. أخبرنا بالسبب حتى نتحسّن — لا يمكن التراجع بعد الإلغاء.' : 'Sorry to see you go. Tell us why so we can improve — this cannot be undone.' }}</p>
        <div class="bkf-ad-policy {{ $isLateCancel ? 'is-late' : '' }}">
          <x-icon name="{{ $isLateCancel ? 'alert' : 'shield' }}" :size="16"/>
          <span>
            @if($isLateCancel)
              {{ $isAr ? 'أنت تلغي بعد مهلة الإلغاء المجانية (' . $cancelWindowHours . ' ساعة قبل الموعد). قد يطبّق المركز سياسته.' : 'You are cancelling after the free window (' . $cancelWindowHours . 'h before). The venue may apply its policy.' }}
            @else
              {{ $isAr ? 'الإلغاء مجاني حتى ' . $cancelWindowHours . ' ساعة قبل الموعد — أي قبل ' . $freeUntil->translatedFormat('l') . ' ' . $freeUntil->format('d/m g:i A') . '.' : 'Free cancellation until ' . $cancelWindowHours . 'h before — that is before ' . $freeUntil->translatedFormat('D') . ' ' . $freeUntil->format('d/m g:i A') . '.' }}
            @endif
          </span>
        </div>
        <form method="POST" action="{{ route('account.appointment.cancel', $a) }}" data-cancel-form>
          @csrf
          <div class="bkf-ad-reasons">
            @foreach($cancelReasons as $key => $label)
              <label class="bkf-ad-reason">
                <input type="radio" name="reason" value="{{ $key }}" required>
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
          <textarea name="note" maxlength="400" class="bkf-ad-reason-note" placeholder="{{ $isAr ? 'أي تفاصيل إضافية… (اختياري)' : 'Any extra details… (optional)' }}"></textarea>
          <div class="bkf-ad-confirm-row" style="margin-top:12px">
            <button type="button" class="bkf-btn bkf-btn-soft" data-cancel-close>{{ $isAr ? 'الاحتفاظ بالموعد' : 'Keep it' }}</button>
            <button type="submit" class="bkf-btn bkf-btn-danger bkf-btn-block" data-cancel-submit>{{ $isAr ? 'تأكيد الإلغاء' : 'Confirm cancellation' }}</button>
          </div>
        </form>
      </div>
      @endif
    </div>

    @if($timeline->isNotEmpty())
    <div class="bkf-ad-card bkf-timeline-card">
      <h2 class="bkf-tl-title">{{ $isAr ? 'سجل الموعد' : 'Booking history' }}</h2>
      <ol class="bkf-tl">
        @foreach($timeline as $ev)
          <li class="bkf-tl-item">
            <span class="bkf-tl-dot"></span>
            <div class="bkf-tl-body">
              <div class="bkf-tl-when">{{ $ev['at']->translatedFormat('d/m') }} · {{ $ev['at']->format('g:i A') }}</div>
              <div class="bkf-tl-what">{{ $ev['label'] }}</div>
              @if($ev['reason'])<div class="bkf-tl-reason">{{ $ev['reason'] }}</div>@endif
            </div>
          </li>
        @endforeach
      </ol>
    </div>
    @endif
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var isAr = document.documentElement.lang === 'ar';
  var open = document.querySelector('[data-cancel-open]');
  var panel = document.querySelector('[data-cancel-panel]');
  var close = document.querySelector('[data-cancel-close]');
  if(open && panel){
    open.addEventListener('click', function(){ panel.hidden = false; open.style.display='none'; panel.scrollIntoView({behavior:'smooth',block:'center'}); });
  }
  if(close && panel && open){
    close.addEventListener('click', function(){ panel.hidden = true; open.style.display=''; });
  }
  // Reason radios: highlight the chosen one, block submit until one is picked.
  var reasons = document.querySelectorAll('[data-cancel-panel] .bkf-ad-reason input');
  reasons.forEach(function(r){
    r.addEventListener('change', function(){
      document.querySelectorAll('[data-cancel-panel] .bkf-ad-reason').forEach(function(el){ el.classList.remove('sel'); });
      if(r.checked) r.closest('.bkf-ad-reason').classList.add('sel');
    });
  });
  var cform = document.querySelector('[data-cancel-form]');
  var submit = document.querySelector('[data-cancel-submit]');
  if(cform){
    cform.addEventListener('submit', function(e){
      if(!cform.querySelector('input[name="reason"]:checked')){ e.preventDefault(); return; }
      if(submit){ submit.disabled = true; submit.textContent = '…'; }
    });
  }

  // Reschedule: open panel, load available slots for the chosen day, submit.
  var rOpen = document.querySelector('[data-resched-open]');
  var rPanel = document.querySelector('[data-resched-panel]');
  if(rOpen && rPanel){
    var rClose = document.querySelector('[data-resched-close]');
    var rDate = document.querySelector('[data-resched-date]');
    var rSlots = document.querySelector('[data-resched-slots]');
    var rStart = document.querySelector('[data-resched-start]');
    var rSubmit = document.querySelector('[data-resched-submit]');
    var svc = rOpen.dataset.service, emp = rOpen.dataset.employee, slotsUrl = rOpen.dataset.slotsUrl;
    rOpen.addEventListener('click', function(){ rPanel.hidden=false; rOpen.style.display='none'; if(rDate.value) loadSlots(); rPanel.scrollIntoView({behavior:'smooth',block:'center'}); });
    if(rClose) rClose.addEventListener('click', function(){ rPanel.hidden=true; rOpen.style.display=''; });
    function loadSlots(){
      rStart.value=''; if(rSubmit) rSubmit.disabled=true;
      rSlots.innerHTML='<p class="bkf-resched-hint">'+(isAr?'جارٍ التحميل…':'Loading…')+'</p>';
      var q = slotsUrl+'?employee_id='+encodeURIComponent(emp)+'&service_id='+encodeURIComponent(svc)+'&date='+encodeURIComponent(rDate.value);
      fetch(q,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json();}).then(function(d){
        var slots=(d&&d.slots)||[];
        if(!slots.length){ rSlots.innerHTML='<p class="bkf-resched-hint">'+(isAr?'لا أوقات متاحة في هذا اليوم.':'No times available on this day.')+'</p>'; return; }
        var g=document.createElement('div'); g.className='bkf-slot-grid';
        slots.forEach(function(s){
          var b=document.createElement('button'); b.type='button'; b.className='bkf-slot'; b.textContent=fmt(s.time); b.dataset.start=s.start;
          b.addEventListener('click', function(){
            [].forEach.call(g.querySelectorAll('.bkf-slot'),function(x){x.classList.remove('sel');});
            b.classList.add('sel'); rStart.value=s.start; if(rSubmit) rSubmit.disabled=false;
          });
          g.appendChild(b);
        });
        rSlots.innerHTML=''; rSlots.appendChild(g);
      }).catch(function(){ rSlots.innerHTML='<p class="bkf-resched-hint">'+(isAr?'تعذّر التحميل.':'Could not load.')+'</p>'; });
    }
    function fmt(t){ var p=t.split(':'),h=+p[0],m=p[1],ap=h>=12?'PM':'AM',h12=h%12||12; return h12+':'+m+' '+ap; }
    rDate.addEventListener('change', loadSlots);
    var rForm=document.querySelector('[data-resched-form]');
    if(rForm) rForm.addEventListener('submit', function(e){ if(!rStart.value){ e.preventDefault(); return; } rSubmit.disabled=true; rSubmit.textContent='…'; });
  }

  // Live status — poll so a confirm/cancel by the venue shows without a refresh.
  var badge = document.querySelector('[data-status-badge]');
  if(badge && badge.dataset.statusUrl){
    var lbl = badge.querySelector('[data-status-label]');
    var dot = badge.querySelector('[data-status-dot]');
    var lastLabel = lbl ? lbl.textContent.trim() : '';
    var lastStart = badge.dataset.statusStart || '';
    var poll = function(){
      fetch(badge.dataset.statusUrl, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(d){
          if(!d) return;
          var labelChanged = d.label && d.label !== lastLabel;
          var timeChanged  = d.start && lastStart && d.start !== lastStart; // venue rescheduled
          if(labelChanged || timeChanged){
            lastLabel = d.label; lastStart = d.start;
            if(lbl) lbl.textContent = d.label;
            if(dot) dot.style.background = d.color;
            badge.style.color = d.color;
            badge.classList.add('is-live-updated');
            // Status change or a venue reschedule changes the time + valid actions;
            // refresh once so the whole card matches the new state.
            setTimeout(function(){ window.location.reload(); }, 1400);
          }
        }).catch(function(){});
    };
    setInterval(poll, 20000);
    document.addEventListener('visibilitychange', function(){ if(!document.hidden) poll(); });
  }

  // Share (Web Share API → clipboard fallback)
  var share = document.querySelector('[data-share]');
  if(share){
    var ar = document.documentElement.lang === 'ar';
    share.addEventListener('click', async function(){
      var url = share.dataset.shareUrl, title = share.dataset.shareTitle, text = share.dataset.shareText;
      if(navigator.share){
        try { await navigator.share({ title: title, text: text, url: url }); } catch(e){}
        return;
      }
      try {
        await navigator.clipboard.writeText(url);
        var label = share.querySelector('svg') ? share.innerHTML : share.textContent;
        share.textContent = ar ? 'تم نسخ الرابط ✓' : 'Link copied ✓';
        setTimeout(function(){ share.innerHTML = label; }, 1800);
      } catch(e){ prompt(ar ? 'انسخ الرابط:' : 'Copy link:', url); }
    });
  }

  // Star rating widget
  var stars = document.querySelector('[data-stars]');
  if(stars){
    var input = document.querySelector('[data-rating-value]');
    var post = document.querySelector('[data-review-submit]');
    var btns = [].slice.call(stars.querySelectorAll('.bkf-star'));
    function paint(v){ btns.forEach(function(b){ var on = +b.dataset.val <= v; b.classList.toggle('on', on); b.setAttribute('aria-checked', +b.dataset.val === v ? 'true' : 'false'); }); }
    btns.forEach(function(b){
      b.addEventListener('mouseenter', function(){ paint(+b.dataset.val); });
      b.addEventListener('click', function(){ input.value = b.dataset.val; paint(+b.dataset.val); if(post) post.disabled = false; });
    });
    stars.addEventListener('mouseleave', function(){ paint(+input.value || 0); });
    var rform = document.querySelector('[data-review-form]');
    if(rform) rform.addEventListener('submit', function(e){ if(!input.value){ e.preventDefault(); return; } post.disabled = true; post.textContent = '…'; });
  }
})();
</script>
</x-slot:scripts>
</x-front.layout>
