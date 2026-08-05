@php
    $isAr  = app()->getLocale() === 'ar';
    $a     = $appointment;
    $b     = $a->branch;
    $venue = $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar);
    $svc   = $a->service ? ($isAr ? ($a->service->name_ar ?? $a->service->name_en) : ($a->service->name_en ?? $a->service->name_ar)) : '';
    $emp   = $a->employee ? ($isAr ? ($a->employee->name_ar ?? $a->employee->name_en) : ($a->employee->name_en ?? $a->employee->name_ar)) : null;
    $img   = $b?->images?->first();
    $cur   = $isAr ? 'ل.س' : 'SYP';
    $sc    = $a->status->color();
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
      <span class="bkf-ad-status" style="color:{{ $sc }}"><i style="background:{{ $sc }}"></i>{{ $a->status->label() }}</span>
    </div>

    <div class="bkf-ad-card">
      <h1 class="bkf-ad-venue">{{ $venue }}</h1>
      <div class="bkf-ad-svc">{{ $svc }}</div>

      <div class="bkf-ad-when">
        <span class="ic"><x-icon name="calendar-check" :size="24"/></span>
        <div>
          <b>{{ $a->start_time->translatedFormat($isAr ? 'l، d F Y' : 'l, d F Y') }}</b>
          <small>{{ $a->start_time->translatedFormat('h:i A') }} – {{ $a->end_time->translatedFormat('h:i A') }}</small>
        </div>
      </div>

      <div class="bkf-ad-rows">
        @if($emp)
        <div class="bkf-ad-row"><x-icon name="user" :size="18"/><span class="k">{{ $isAr ? 'الموظف' : 'Specialist' }}</span><span class="v">{{ $emp }}</span></div>
        @endif
        <div class="bkf-ad-row"><x-icon name="clock" :size="18"/><span class="k">{{ $isAr ? 'المدة' : 'Duration' }}</span><span class="v">{{ $a->service?->duration_minutes }} {{ $isAr ? 'دقيقة' : 'min' }}</span></div>
        <div class="bkf-ad-row bkf-ad-price"><x-icon name="tag" :size="18"/><span class="k">{{ $isAr ? 'السعر' : 'Price' }}</span><span class="v">{{ number_format((float)$a->total_price) }} {{ $cur }}</span></div>
        @if($b->address)
        <div class="bkf-ad-row"><x-icon name="map-pin" :size="18"/><span class="k">{{ $isAr ? 'العنوان' : 'Address' }}</span>
          @if($mapUrl)<a class="v" href="{{ $mapUrl }}" target="_blank" rel="noopener">{{ $isAr ? 'الخريطة' : 'View map' }}<x-icon name="arrow-up-right" :size="14"/></a>@else<span class="v">{{ $b->address }}</span>@endif
        </div>
        @endif
      </div>

      @if($a->notes)
        <div class="bkf-ad-notes"><strong>{{ $isAr ? 'ملاحظات: ' : 'Notes: ' }}</strong>{{ $a->notes }}</div>
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
                  data-share-text="{{ $isAr ? 'موعدي في '.$venue.' عبر بوكسي' : 'My appointment at '.$venue.' via Booksy' }}">
            <x-icon name="arrow-up-right" :size="17"/>{{ $isAr ? 'مشاركة' : 'Share' }}
          </button>
        </div>

        @if($canCancel)
          <button type="button" class="bkf-btn bkf-btn-danger-soft bkf-btn-block" data-cancel-open>
            <x-icon name="x" :size="18"/>{{ $isAr ? 'إلغاء الموعد' : 'Cancel appointment' }}
          </button>
        @endif
      </div>

      @if($canCancel)
      <div class="bkf-ad-confirm" data-cancel-panel hidden>
        <p><x-icon name="alert" :size="20"/>{{ $isAr ? 'هل أنت متأكد من إلغاء هذا الموعد؟ لا يمكن التراجع بعد الإلغاء.' : 'Are you sure you want to cancel this appointment? This cannot be undone.' }}</p>
        <div class="bkf-ad-confirm-row">
          <button type="button" class="bkf-btn bkf-btn-soft" data-cancel-close>{{ $isAr ? 'تراجع' : 'Keep it' }}</button>
          <form method="POST" action="{{ route('account.appointment.cancel', $a) }}" style="flex:1;">
            @csrf
            <button type="submit" class="bkf-btn bkf-btn-danger bkf-btn-block" data-cancel-submit>{{ $isAr ? 'نعم، ألغِ' : 'Yes, cancel' }}</button>
          </form>
        </div>
      </div>
      @endif
    </div>
  </div>
</section>

<x-slot:scripts>
<script>
(function(){
  var open = document.querySelector('[data-cancel-open]');
  var panel = document.querySelector('[data-cancel-panel]');
  var close = document.querySelector('[data-cancel-close]');
  if(open && panel){
    open.addEventListener('click', function(){ panel.hidden = false; open.style.display='none'; panel.scrollIntoView({behavior:'smooth',block:'center'}); });
  }
  if(close && panel && open){
    close.addEventListener('click', function(){ panel.hidden = true; open.style.display=''; });
  }
  var submit = document.querySelector('[data-cancel-submit]');
  if(submit){ submit.closest('form').addEventListener('submit', function(){ submit.disabled = true; submit.textContent = '…'; }); }

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
