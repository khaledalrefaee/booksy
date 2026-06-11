{{-- booking-modal.blade.php — include once per page --}}
@php $isAr = app()->getLocale() === 'ar'; @endphp

<style>
/* ── Overlay ── */
#bk-overlay{
    display:none;position:fixed;inset:0;z-index:10500;
    background:rgba(0,0,0,.55);backdrop-filter:blur(6px);
}
/* ── Modal sheet ── */
#bk-modal{
    display:none; /* JS sets to flex when open */
    position:fixed;bottom:0;
    left:50%;transform:translateX(-50%);
    z-index:10501;
    width:100%;max-width:520px;
    flex-direction:column;
    background:var(--bk-bg,#ffffff);
    border-radius:24px 24px 0 0;
    box-shadow:0 -8px 48px rgba(0,0,0,.25);
    max-height:92vh;
    overflow:hidden;
}
[data-theme="dark"] #bk-modal{
    --bk-bg:#151515;--bk-card:#1e1e1e;--bk-border:rgba(255,255,255,.08);
    --bk-text:#f0f0f0;--bk-text2:rgba(255,255,255,.45);
    --bk-sel:#C9A227;--bk-sel-text:#0a0a0a;
    --bk-slot-bg:#1e1e1e;--bk-slot-border:rgba(255,255,255,.1);
    --bk-gold:#C9A227;
}
#bk-modal{
    --bk-bg:#ffffff;--bk-card:#f5f5f5;--bk-border:rgba(0,0,0,.09);
    --bk-text:#111111;--bk-text2:rgba(0,0,0,.45);
    --bk-sel:#0a7aff;--bk-sel-text:#ffffff;
    --bk-slot-bg:#f0f0f0;--bk-slot-border:rgba(0,0,0,.1);
    --bk-gold:#C9A227;
}
#bk-modal *{box-sizing:border-box;font-family:'{{ $isAr ? "Tajawal" : "Poppins" }}',sans-serif;}

/* handle + header */
.bk-handle{width:36px;height:4px;border-radius:4px;background:var(--bk-border);margin:10px auto 0;}
.bk-header{display:flex;align-items:center;justify-content:space-between;padding:12px 20px 6px;}
.bk-title{font-size:.95rem;font-weight:700;color:var(--bk-text);}
.bk-progress{font-size:.72rem;color:var(--bk-gold);font-weight:600;background:rgba(201,162,39,.1);padding:3px 10px;border-radius:20px;}
.bk-close-btn{width:30px;height:30px;border-radius:50%;border:none;background:var(--bk-card);color:var(--bk-text2);cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center;}

/* scroll body */
.bk-body{overflow-y:auto;flex:1;-webkit-overflow-scrolling:touch;}

/* month row */
.bk-month-row{display:flex;align-items:center;justify-content:space-between;padding:8px 20px 10px;}
.bk-month-label{font-size:.9rem;font-weight:700;color:var(--bk-text);}
.bk-nav-btns{display:flex;gap:6px;}
.bk-nav-btn{width:32px;height:32px;border-radius:50%;border:1.5px solid var(--bk-border);background:var(--bk-card);color:var(--bk-text);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .2s;}
.bk-nav-btn:hover{border-color:var(--bk-gold);color:var(--bk-gold);}
.bk-nav-btn:disabled{opacity:.3;cursor:not-allowed;}

/* day strip */
.bk-day-strip{display:flex;gap:10px;overflow-x:auto;padding:0 20px 14px;scrollbar-width:none;}
.bk-day-strip::-webkit-scrollbar{display:none;}
.bk-day-item{flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:3px;cursor:pointer;user-select:none;}
.bk-day-circle{
    width:44px;height:44px;border-radius:50%;
    background:var(--bk-slot-bg);border:2px solid transparent;
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;font-weight:700;color:var(--bk-text);
    transition:all .2s;position:relative;
}
.bk-day-item.selected .bk-day-circle{background:var(--bk-sel);color:var(--bk-sel-text);border-color:var(--bk-sel);}
.bk-day-item.today:not(.selected) .bk-day-circle{border-color:var(--bk-gold);}
.bk-day-item.past{opacity:.35;pointer-events:none;}
.bk-day-name{font-size:.6rem;color:var(--bk-text2);text-transform:uppercase;letter-spacing:.3px;}
.bk-dot{width:5px;height:5px;border-radius:50%;position:absolute;bottom:-8px;}
.bk-dot.free{background:#22c55e;}
.bk-dot.full{background:#ef4444;}

/* slots */
.bk-slots-section{padding:0 20px 14px;}
.bk-slots-lbl{font-size:.7rem;font-weight:700;color:var(--bk-text2);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;}
.bk-slots-grid{display:flex;flex-wrap:wrap;gap:8px;}
.bk-slot{
    padding:9px 16px;border-radius:25px;border:1.5px solid var(--bk-slot-border);
    background:var(--bk-slot-bg);color:var(--bk-text);
    font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;
}
.bk-slot:hover{border-color:var(--bk-sel);color:var(--bk-sel);}
.bk-slot.selected{background:var(--bk-sel);color:var(--bk-sel-text);border-color:var(--bk-sel);}

/* unavailable state */
.bk-unavail{text-align:center;padding:28px 20px;}
.bk-unavail-av{width:56px;height:56px;border-radius:50%;background:var(--bk-card);border:2px solid var(--bk-border);margin:0 auto 10px;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--bk-text2);}
.bk-unavail-av img{width:100%;height:100%;object-fit:cover;}
.bk-unavail-msg{font-size:.88rem;font-weight:700;color:var(--bk-text);margin-bottom:6px;}
.bk-unavail-hours{font-size:.75rem;color:var(--bk-text2);margin-bottom:14px;}
.bk-next-avail-btn{background:var(--bk-sel);color:var(--bk-sel-text);border:none;border-radius:10px;padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:inherit;}

/* skeleton */
.bk-skel{height:38px;border-radius:25px;background:linear-gradient(90deg,var(--bk-card) 25%,var(--bk-border) 50%,var(--bk-card) 75%);background-size:200% 100%;animation:bkshimmer 1.2s infinite;}
@keyframes bkshimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
@keyframes bkBounce{0%{transform:scale(.3)}60%{transform:scale(1.15)}100%{transform:scale(1)}}
@keyframes bkspin{to{transform:rotate(360deg)}}

/* employee chip */
.bk-emp-chip{display:flex;align-items:center;gap:10px;padding:10px 20px 6px;}
.bk-emp-av{width:34px;height:34px;border-radius:50%;background:var(--bk-card);border:1.5px solid var(--bk-border);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--bk-text2);}
.bk-emp-av img{width:100%;height:100%;object-fit:cover;}
.bk-emp-lbl{font-size:.7rem;color:var(--bk-text2);}
.bk-emp-nm{font-size:.85rem;font-weight:600;color:var(--bk-text);}

/* service summary */
.bk-svc-card{margin:6px 20px 14px;background:var(--bk-card);border:1px solid var(--bk-border);border-radius:12px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;}
.bk-svc-nm{font-size:.88rem;font-weight:700;color:var(--bk-text);}
.bk-svc-dur{font-size:.72rem;color:var(--bk-text2);margin-top:2px;}
.bk-svc-price{font-size:.95rem;font-weight:800;color:var(--bk-text);text-align:{{ $isAr ? 'left' : 'right' }};}

/* footer */
.bk-footer{padding:12px 20px 16px;border-top:1px solid var(--bk-border);display:flex;align-items:center;justify-content:space-between;background:var(--bk-bg);flex-shrink:0;}
.bk-footer-meta small{display:block;font-size:.68rem;color:var(--bk-text2);}
.bk-footer-meta strong{font-size:.9rem;color:var(--bk-text);}
.bk-confirm{
    background:var(--bk-sel);color:var(--bk-sel-text);
    border:none;border-radius:12px;padding:12px 28px;
    font-size:.88rem;font-weight:700;cursor:pointer;
    opacity:.45;pointer-events:none;
    transition:all .2s;font-family:inherit;
}
.bk-confirm.on{opacity:1;pointer-events:all;}
.bk-confirm.on:hover{filter:brightness(1.08);transform:translateY(-1px);}
.bk-spinner{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:bkspin .7s linear infinite;margin:0 auto;}
</style>

{{-- Overlay --}}
<div id="bk-overlay" onclick="BookingModal.close()"></div>

{{-- Modal --}}
<div id="bk-modal" role="dialog" aria-modal="true">
  <div class="bk-handle"></div>
  <div class="bk-header">
    <span class="bk-title" id="bk-title">{{ $isAr ? 'اختر التاريخ والوقت' : 'Select Date & Time' }}</span>
    <div style="display:flex;align-items:center;gap:8px;">
      <span class="bk-progress" id="bk-progress" style="display:none;"></span>
      <button class="bk-close-btn" onclick="BookingModal.close()">✕</button>
    </div>
  </div>

  <div class="bk-body" id="bk-body">
    {{-- Month nav --}}
    <div class="bk-month-row">
      <span class="bk-month-label" id="bk-month-lbl"></span>
      <div class="bk-nav-btns">
        <button class="bk-nav-btn" id="bk-prev" onclick="BookingModal.shiftWeek(-7)" disabled>
          <i class="fas fa-chevron-{{ $isAr ? 'right' : 'left' }}" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        </button>
        <button class="bk-nav-btn" id="bk-next" onclick="BookingModal.shiftWeek(7)">
          <i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}" style="font-family:'Font Awesome 5 Free'!important;font-weight:900!important;"></i>
        </button>
      </div>
    </div>

    {{-- Day strip --}}
    <div class="bk-day-strip" id="bk-days"></div>

    {{-- Slots --}}
    <div class="bk-slots-section">
      <div class="bk-slots-lbl">{{ $isAr ? 'الأوقات المتاحة' : 'Available Times' }}</div>
      <div class="bk-slots-grid" id="bk-slots">
        <div class="bk-skel" style="width:80px;"></div>
        <div class="bk-skel" style="width:72px;"></div>
        <div class="bk-skel" style="width:88px;"></div>
        <div class="bk-skel" style="width:76px;"></div>
      </div>
    </div>

    {{-- Employee chip --}}
    <div class="bk-emp-chip" id="bk-emp"></div>

    {{-- Service card --}}
    <div class="bk-svc-card" id="bk-svc"></div>
  </div>

  <div class="bk-footer">
    <div class="bk-footer-meta">
      <small id="bk-date-lbl">{{ $isAr ? 'اختر وقتاً' : 'Pick a time' }}</small>
      <strong id="bk-price-lbl">—</strong>
    </div>
    <button class="bk-confirm" id="bk-confirm" onclick="BookingModal._confirm()">
      {{ $isAr ? 'تأكيد' : 'Confirm' }}
    </button>
  </div>
</div>

{{-- Auth modal --}}
@include('front.partials.customer-auth-modal')

<script>
window.BookingModal = (function(){
    const IS_AR  = {{ $isAr ? 'true' : 'false' }};
    const S_URL  = '{{ route('booking.slots') }}';
    const B_URL  = '{{ route('booking.book') }}';
    const ME_URL = '{{ route('customer.me') }}';
    const TOKEN  = '{{ csrf_token() }}';

    const MONTHS = IS_AR
        ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
        : ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DAYS = IS_AR
        ? ['أح','إث','ثل','أر','خم','جم','سب']
        : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    let s = {
        service: null, employee: null, branch: null,
        onSuccess: null,
        weekOffset: 0,
        date: null, slot: null,
        cache: {},
    };

    const el = id => document.getElementById(id);

    /* ── PUBLIC ── */
    function open(opts) {
        s.service    = opts.service;
        s.employee   = opts.employee;
        s.branch     = opts.branch;
        s.onSuccess  = opts.onSuccess || null;
        s.weekOffset = 0;
        s.date = null; s.slot = null; s.cache = {};

        // Progress label (e.g. "2 of 3")
        if (opts.progressLabel) {
            el('bk-progress').textContent = opts.progressLabel;
            el('bk-progress').style.display = 'inline-block';
        } else {
            el('bk-progress').style.display = 'none';
        }

        el('bk-title').textContent = IS_AR ? 'اختر التاريخ والوقت' : 'Select Date & Time';
        el('bk-date-lbl').textContent = IS_AR ? 'اختر وقتاً' : 'Pick a time';
        el('bk-confirm').classList.remove('on');

        _renderEmp();
        _renderSvc();
        _buildStrip();
        _selectDay(_today());

        el('bk-overlay').style.display = 'block';
        el('bk-modal').style.display   = 'flex';
        document.body.style.overflow   = 'hidden';
    }

    function close() {
        el('bk-overlay').style.display = 'none';
        el('bk-modal').style.display   = 'none';
        document.body.style.overflow   = '';
    }

    function shiftWeek(n) {
        s.weekOffset = Math.max(0, s.weekOffset + n);
        el('bk-prev').disabled = (s.weekOffset === 0);
        s.slot = null;
        _buildStrip();
        _selectDay(_addDays(_today(), s.weekOffset));
    }

    function goToDate(dateStr) {
        const diff = Math.floor((new Date(dateStr+'T00:00:00') - new Date(_today()+'T00:00:00')) / 86400000);
        s.weekOffset = Math.max(0, diff - 2);
        el('bk-prev').disabled = (s.weekOffset === 0);
        _buildStrip();
        _selectDay(dateStr);
        el('bk-body').scrollIntoView({behavior:'smooth', block:'start'});
    }

    function resetForNext(progressLabel) {
        s.date = null; s.slot = null; s.cache = {};
        s.weekOffset = 0;

        el('bk-slots').innerHTML = _skels();
        el('bk-date-lbl').textContent = IS_AR ? 'اختر وقتاً' : 'Pick a time';
        el('bk-confirm').classList.remove('on');
        if (progressLabel) {
            el('bk-progress').textContent = progressLabel;
            el('bk-progress').style.display = 'inline-block';
        }

        _renderEmp();
        _renderSvc();
        _buildStrip();
        _selectDay(_today());
    }

    /* ── PRIVATE ── */
    function _today() {
        return new Date().toISOString().slice(0,10);
    }

    function _addDays(str, n) {
        const d = new Date(str+'T00:00:00');
        d.setDate(d.getDate() + n);
        return d.toISOString().slice(0,10);
    }

    function _buildStrip() {
        const strip = el('bk-days');
        strip.innerHTML = '';
        const today = _today();

        for (let i = 0; i < 14; i++) {
            const dateStr = _addDays(today, s.weekOffset + i);
            const d       = new Date(dateStr + 'T00:00:00');
            const isPast  = dateStr < today;

            const item = document.createElement('div');
            item.className = 'bk-day-item'
                + (isPast ? ' past' : '')
                + (dateStr === today ? ' today' : '');
            item.dataset.date = dateStr;

            item.innerHTML = `
                <div class="bk-day-circle">${d.getDate()}</div>
                <div class="bk-day-name">${DAYS[d.getDay()]}</div>
            `;

            if (!isPast) {
                item.addEventListener('click', () => _selectDay(dateStr));
                // Prefetch to show dot
                _fetchSlots(dateStr, true);
            }
            strip.appendChild(item);
        }

        // Update month label
        const firstD = new Date(_addDays(today, s.weekOffset) + 'T00:00:00');
        el('bk-month-lbl').textContent = MONTHS[firstD.getMonth()] + ' ' + firstD.getFullYear();
    }

    function _selectDay(dateStr) {
        s.date = dateStr;
        s.slot = null;
        el('bk-confirm').classList.remove('on');

        document.querySelectorAll('#bk-days .bk-day-item').forEach(it => {
            it.classList.toggle('selected', it.dataset.date === dateStr);
        });

        _fetchSlots(dateStr, false);
    }

    function _fetchSlots(dateStr, dotOnly) {
        if (s.cache[dateStr] !== undefined && !dotOnly) {
            _renderSlots(s.cache[dateStr]);
            return;
        }
        if (s.cache[dateStr] !== undefined) return; // already fetching/fetched

        if (!dotOnly) {
            el('bk-slots').innerHTML = _skels();
        }

        s.cache[dateStr] = null; // mark fetching

        const params = new URLSearchParams({
            employee_id: s.employee.id,
            date:        dateStr,
            service_id:  s.service.id,
        });

        fetch(S_URL + '?' + params)
            .then(r => r.json())
            .then(data => {
                s.cache[dateStr] = data;

                // Add dot to day circle
                const dayEl = document.querySelector(`#bk-days [data-date="${dateStr}"] .bk-day-circle`);
                if (dayEl && !dayEl.querySelector('.bk-dot')) {
                    const dot = document.createElement('div');
                    dot.className = 'bk-dot ' + (data.available ? 'free' : 'full');
                    dayEl.appendChild(dot);
                }

                if (!dotOnly && s.date === dateStr) {
                    _renderSlots(data);
                }
            })
            .catch(() => {
                if (!dotOnly) el('bk-slots').innerHTML = `<p style="color:red;font-size:.8rem;padding:4px 0;">Error loading slots</p>`;
            });
    }

    function _renderSlots(data) {
        const wrap = document.querySelector('#bk-modal .bk-slots-section');

        if (!data || !data.available || !data.slots || !data.slots.length) {
            const empName = s.employee.name;
            const empImg  = s.employee.image;
            const reason  = data ? data.reason : 'error';
            const nextDate= data ? data.next_date : null;

            const msgs = {
                not_working:  IS_AR ? `${empName} لا يعمل هذا اليوم`   : `${empName} doesn't work this day`,
                on_leave:     IS_AR ? `${empName} في إجازة`             : `${empName} is on leave`,
                fully_booked: IS_AR ? 'لا توجد أوقات متاحة'            : 'No available times',
            };

            let nextBtn = '';
            if (nextDate) {
                const nd = new Date(nextDate + 'T00:00:00');
                const ndLabel = IS_AR
                    ? nd.toLocaleDateString('ar-SY',{weekday:'short',day:'numeric',month:'short'})
                    : nd.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'});
                nextBtn = `<button class="bk-next-avail-btn" onclick="BookingModal.goToDate('${nextDate}')">
                    ${IS_AR ? 'التالي: ' : 'Next: '}${ndLabel}
                </button>`;
            }

            const wh = data && data.working_hours
                ? `<div class="bk-unavail-hours">${IS_AR?'دوامه:':'Working hours:'} ${data.working_hours.start} – ${data.working_hours.end}</div>`
                : '';

            wrap.innerHTML = `
                <div class="bk-slots-lbl">${IS_AR ? 'الأوقات المتاحة' : 'Available Times'}</div>
                <div class="bk-unavail">
                    <div class="bk-unavail-av">
                        ${empImg ? `<img src="${empImg}" alt="">` : (empName[0] || '?')}
                    </div>
                    <div class="bk-unavail-msg">${msgs[reason] || (IS_AR ? 'غير متاح' : 'Not available')}</div>
                    ${wh}
                    ${nextBtn}
                </div>
            `;
            return;
        }

        wrap.innerHTML = `
            <div class="bk-slots-lbl">${IS_AR ? 'الأوقات المتاحة' : 'Available Times'}</div>
            <div class="bk-slots-grid" id="bk-slots"></div>
        `;

        const grid = el('bk-slots');
        data.slots.forEach(slot => {
            const btn = document.createElement('button');
            btn.className = 'bk-slot';
            btn.textContent = slot.time;
            btn.dataset.start = slot.start;
            btn.dataset.end   = slot.end;
            btn.addEventListener('click', () => _pickSlot(slot, btn));
            grid.appendChild(btn);
        });
    }

    function _pickSlot(slot, btn) {
        s.slot = slot;
        document.querySelectorAll('#bk-slots .bk-slot').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');

        // Update footer date label
        const d = new Date(s.date + 'T00:00:00');
        const label = IS_AR
            ? d.toLocaleDateString('ar-SY',{weekday:'long',day:'numeric',month:'long'}) + ' · ' + slot.time
            : d.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric'}) + ' · ' + slot.time;
        el('bk-date-lbl').textContent = label;
        el('bk-confirm').classList.add('on');
    }

    async function _confirm() {
        if (!s.slot) return;

        // Check auth first
        const me = await fetch(ME_URL).then(r => r.json()).catch(() => ({authenticated:false}));
        if (!me.authenticated) {
            CustomerAuthModal.open(() => _confirm());
            return;
        }

        const btn = el('bk-confirm');
        btn.innerHTML = `<div class="bk-spinner"></div>`;
        btn.classList.remove('on');

        const res = await fetch(B_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN},
            body: JSON.stringify({
                service_id:  s.service.id,
                employee_id: s.employee.id,
                start_time:  s.slot.start,
                notes: '',
            }),
        }).then(r => r.json()).catch(() => ({error:true}));

        if (res.booked) {
            _showSuccess(res.appointment);
        } else if (res.conflict) {
            btn.innerHTML = IS_AR ? 'تأكيد' : 'Confirm';
            btn.classList.add('on');
            s.slot = null;
            s.cache[s.date] = null;
            _fetchSlots(s.date, false);
            alert(IS_AR ? 'هذا الوقت محجوز للتو! اختر وقتاً آخر.' : 'Slot just taken! Pick another time.');
        } else {
            btn.innerHTML = IS_AR ? 'تأكيد' : 'Confirm';
            btn.classList.add('on');
            alert(IS_AR ? 'حدث خطأ. حاول مجدداً.' : 'An error occurred. Try again.');
        }
    }

    function _showSuccess(appt) {
        el('bk-body').innerHTML = `
            <div style="text-align:center;padding:40px 24px 20px;">
                <div style="font-size:3rem;margin-bottom:14px;display:inline-block;animation:bkBounce .5s ease;">✅</div>
                <div style="font-size:1.05rem;font-weight:800;color:var(--bk-text);margin-bottom:8px;">
                    ${IS_AR ? 'تم الحجز بنجاح!' : 'Booking Confirmed!'}
                </div>
                <div style="font-size:.85rem;color:var(--bk-text2);line-height:1.8;margin-bottom:18px;">
                    ${esc(appt.service)}<br>
                    <strong style="color:var(--bk-text);">${appt.start_time} – ${appt.end_time}</strong>
                </div>
                <div style="background:var(--bk-card);border-radius:12px;padding:14px;border:1px solid var(--bk-border);margin-bottom:20px;">
                    <div style="font-size:.7rem;color:var(--bk-text2);margin-bottom:3px;">${IS_AR?'المبلغ':'Amount'}</div>
                    <div style="font-size:1.2rem;font-weight:800;color:var(--bk-gold);">${appt.price} ${IS_AR?'ل.س':'SYP'}</div>
                </div>
                <button onclick="BookingModal.close()" style="width:100%;padding:13px;border-radius:14px;background:var(--bk-sel);color:var(--bk-sel-text);border:none;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;">
                    ${IS_AR ? 'تم ✓' : 'Done ✓'}
                </button>
            </div>
        `;
        el('bk-footer').style.display = 'none';

        if (s.onSuccess) {
            setTimeout(() => { s.onSuccess(appt); el('bk-footer').style.display = ''; }, 1800);
        }
    }

    function _renderEmp() {
        const emp = s.employee;
        el('bk-emp').innerHTML = `
            <div class="bk-emp-av">
                ${emp.image ? `<img src="${emp.image}" alt="">` : (emp.name[0]||'?')}
            </div>
            <div>
                <div class="bk-emp-lbl">${IS_AR ? 'مع' : 'With'}</div>
                <div class="bk-emp-nm">${esc(emp.name)}</div>
            </div>
        `;
    }

    function _renderSvc() {
        const svc = s.service;
        el('bk-svc').innerHTML = `
            <div>
                <div class="bk-svc-nm">${esc(svc.name)}</div>
                <div class="bk-svc-dur">${svc.duration} ${IS_AR ? 'دقيقة' : 'min'}</div>
            </div>
            <div class="bk-svc-price">${svc.price} ${IS_AR ? 'ل.س' : 'SYP'}</div>
        `;
        el('bk-price-lbl').textContent = svc.price + ' ' + (IS_AR ? 'ل.س' : 'SYP');
    }

    function _skels() {
        return ['80px','72px','88px','76px'].map(w => `<div class="bk-skel" style="width:${w};"></div>`).join('');
    }

    function esc(s){ const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }

    // expose _confirm as public
    return { open, close, shiftWeek, goToDate, resetForNext, _confirm };
})();
</script>
