@php
    /** Fresha-style visit booking: multiple guests, per-guest services + staff,
     *  ONE time, atomic booking. Expects $branch (+ its services & employees). */
    $isAr = $isAr ?? (app()->getLocale() === 'ar');
    $gbServices = $branch->services->where('is_active', true)->map(fn($s) => [
        'id'       => $s->id,
        'name'     => $isAr ? ($s->name_ar ?: $s->name_en) : ($s->name_en ?: $s->name_ar),
        'cat'      => $s->service_category_id,
        'cat_name' => $s->serviceCategory ? ($isAr ? ($s->serviceCategory->name_ar ?? '') : ($s->serviceCategory->name_en ?? '')) : '',
        'duration' => (int) $s->duration_minutes,
        'price'    => (float) ($s->price ?: 0),
    ])->values();
    $gbEmployees = $branch->employees->where('is_active', true)->map(fn($e) => [
        'id'    => $e->id,
        'name'  => $isAr ? ($e->name_ar ?: $e->name_en) : ($e->name_en ?: $e->name_ar),
        'image' => $e->image ? asset('storage/'.$e->image) : null,
    ])->values();
@endphp

<div class="gb-overlay" id="gb-overlay" onclick="GroupBookingModal.close()"></div>
<div class="gb-modal" id="gb-modal" role="dialog" aria-modal="true">
  <div class="gb-head">
    <button class="gb-back" id="gb-back" onclick="GroupBookingModal.back()" style="display:none" aria-label="{{ $isAr ? 'رجوع' : 'Back' }}"><x-icon name="chevron-{{ $isAr ? 'right' : 'left' }}" :size="18"/></button>
    <span class="gb-title" id="gb-title">{{ $isAr ? 'اختر خدماتك' : 'Choose services' }}</span>
    <button class="gb-close" onclick="GroupBookingModal.close()" aria-label="{{ $isAr ? 'إغلاق' : 'Close' }}"><x-icon name="x" :size="18"/></button>
  </div>

  <div class="gb-body" id="gb-body"></div>

  <div class="gb-foot" id="gb-foot">
    <div class="gb-foot-meta"><span class="gb-foot-l" id="gb-foot-l"></span><strong class="gb-foot-p bkf-tnum" id="gb-foot-p"></strong></div>
    <button class="bkf-btn bkf-btn-primary" id="gb-next" onclick="GroupBookingModal.next()">{{ $isAr ? 'متابعة' : 'Continue' }}</button>
  </div>
</div>

<style>
.gb-overlay{ position:fixed; inset:0; z-index:1200; display:none; background:color-mix(in srgb,#000 50%,transparent); backdrop-filter:blur(4px); }
.gb-overlay.open{ display:block; }
.gb-modal{ position:fixed; z-index:1201; inset-inline:0; inset-block-end:0; margin-inline:auto; max-width:520px; width:100%;
  background:var(--bk-surface); border-radius:var(--bk-r-xl) var(--bk-r-xl) 0 0; display:none; flex-direction:column; max-height:92vh; box-shadow:var(--bk-shadow-xl); }
.gb-modal.open{ display:flex; }
@media (min-width:600px){ .gb-modal{ inset-block:auto; top:50%; transform:translateY(-50%); border-radius:var(--bk-r-xl); max-height:88vh; } }
.gb-head{ display:flex; align-items:center; gap:10px; padding:16px 18px; border-bottom:1px solid var(--bk-border); }
.gb-title{ font-family:var(--bk-font-ui); font-weight:800; font-size:1.02rem; color:var(--bk-text); flex:1; }
.gb-back,.gb-close{ width:34px; height:34px; border-radius:50%; border:0; background:var(--bk-surface-2); color:var(--bk-text-soft); display:grid; place-items:center; cursor:pointer; flex-shrink:0; }
.gb-back:hover,.gb-close:hover{ background:var(--bk-surface-3); color:var(--bk-text); }
.gb-body{ padding:16px 18px; overflow-y:auto; flex:1; }
.gb-foot{ display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 18px calc(14px + env(safe-area-inset-bottom)); border-top:1px solid var(--bk-border); }
.gb-foot-meta{ display:flex; flex-direction:column; }
.gb-foot-l{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); }
.gb-foot-p{ font-family:var(--bk-font-display); font-weight:800; font-size:1.15rem; color:var(--bk-text); }
.gb-foot .bkf-btn{ min-width:130px; }

/* guests tabs */
.gb-guests{ display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.gb-guest-tab{ display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:var(--bk-r-pill); border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text-soft); font-family:var(--bk-font-ui); font-weight:600; font-size:var(--bk-fs-sm); cursor:pointer; }
.gb-guest-tab.is-active{ background:var(--bk-accent); color:var(--bk-accent-ink); border-color:var(--bk-accent); }
.gb-guest-tab .rm{ opacity:.7; }
.gb-guest-add{ border-style:dashed; color:var(--bk-accent); background:transparent; }
.gb-guest-cnt{ display:inline-flex; align-items:center; justify-content:center; min-width:18px; height:18px; padding:0 5px; border-radius:9px; background:var(--bk-gold-soft); color:var(--bk-gold-strong); font-size:.68rem; font-weight:800; }

/* service rows */
.gb-cat-h{ font-family:var(--bk-font-ui); font-weight:700; font-size:.82rem; color:var(--bk-text-muted); text-transform:uppercase; letter-spacing:.5px; margin:14px 0 8px; }
.gb-svc{ display:flex; align-items:center; gap:12px; padding:13px 14px; border:1px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface); margin-bottom:8px; cursor:pointer; transition:border-color var(--bk-t) ease,background var(--bk-t) ease; }
.gb-svc:hover{ border-color:color-mix(in srgb,var(--bk-accent) 35%,var(--bk-border)); }
.gb-svc.is-on{ border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.gb-svc-info{ flex:1; min-width:0; }
.gb-svc-nm{ font-family:var(--bk-font-ui); font-weight:600; color:var(--bk-text); font-size:.92rem; }
.gb-svc-meta{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); margin-top:3px; }
.gb-svc-pr{ font-family:var(--bk-font-ui); font-weight:700; color:var(--bk-gold-strong); font-size:.88rem; white-space:nowrap; }
.gb-check{ width:24px; height:24px; border-radius:50%; border:2px solid var(--bk-border); display:grid; place-items:center; flex-shrink:0; color:transparent; }
.gb-svc.is-on .gb-check{ background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }

/* staff */
.gb-section-h{ font-family:var(--bk-font-ui); font-weight:800; font-size:1rem; color:var(--bk-text); margin:20px 0 12px; }
.gb-opt{ display:flex; align-items:center; gap:12px; padding:14px; border:1px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface); margin-bottom:10px; cursor:pointer; }
.gb-opt.is-on{ border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.gb-opt .gb-radio{ width:22px; height:22px; border-radius:50%; border:2px solid var(--bk-border); flex-shrink:0; display:grid; place-items:center; }
.gb-opt.is-on .gb-radio{ border-color:var(--bk-accent); }
.gb-opt.is-on .gb-radio::after{ content:""; width:11px; height:11px; border-radius:50%; background:var(--bk-accent); }
.gb-opt-t{ font-family:var(--bk-font-ui); font-weight:600; color:var(--bk-text); font-size:.92rem; }
.gb-opt-s{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs); color:var(--bk-text-muted); margin-top:2px; }
.gb-emp-select{ width:100%; margin-top:8px; padding:11px 12px; border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); background:var(--bk-surface-2); color:var(--bk-text); font-family:var(--bk-font-ui); font-size:.9rem; }
.gb-per-guest{ margin-top:6px; }
.gb-per-row{ display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.gb-per-row label{ font-family:var(--bk-font-ui); font-size:.85rem; color:var(--bk-text-soft); min-width:70px; }

/* time (reused pattern) */
.gb-month{ display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.gb-month-l{ font-family:var(--bk-font-ui); font-weight:700; color:var(--bk-text); }
.gb-navb{ width:34px; height:34px; border-radius:50%; border:1px solid var(--bk-border); background:var(--bk-surface); color:var(--bk-text); cursor:pointer; }
.gb-navb:disabled{ opacity:.4; cursor:default; }
.gb-days{ display:flex; gap:8px; overflow-x:auto; scrollbar-width:none; padding-bottom:6px; }
.gb-days::-webkit-scrollbar{ display:none; }
.gb-day{ flex:0 0 auto; width:52px; text-align:center; padding:10px 0; border:1px solid var(--bk-border); border-radius:var(--bk-r); background:var(--bk-surface); cursor:pointer; }
.gb-day.is-active{ background:var(--bk-accent); border-color:var(--bk-accent); color:var(--bk-accent-ink); }
.gb-day .d{ font-family:var(--bk-font-display); font-weight:800; font-size:1.1rem; }
.gb-day .w{ font-family:var(--bk-font-ui); font-size:.68rem; opacity:.75; }
.gb-slots{ display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
.gb-slot{ padding:10px 14px; border:1px solid var(--bk-border); border-radius:var(--bk-r-sm); background:var(--bk-surface); color:var(--bk-text); font-family:var(--bk-font-ui); font-weight:600; font-size:.85rem; cursor:pointer; }
.gb-slot:hover{ border-color:var(--bk-accent); }
.gb-slot.is-on{ background:var(--bk-accent); color:var(--bk-accent-ink); border-color:var(--bk-accent); }
.gb-empty{ text-align:center; color:var(--bk-text-muted); font-family:var(--bk-font-ui); font-size:.88rem; padding:30px 10px; }
.gb-skel{ height:38px; width:78px; border-radius:var(--bk-r-sm); background:linear-gradient(90deg,var(--bk-surface-2),var(--bk-surface-3),var(--bk-surface-2)); background-size:200% 100%; animation:gbShimmer 1.2s infinite; }
@keyframes gbShimmer{ 0%{background-position:200% 0} 100%{background-position:-200% 0} }
@media (prefers-reduced-motion:reduce){ .gb-skel{ animation:none; } }

/* summary / success */
.gb-sum-row{ display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--bk-border); font-family:var(--bk-font-ui); font-size:.9rem; }
.gb-sum-row .g{ color:var(--bk-text-muted); }
.gb-success{ text-align:center; padding:32px 16px; }
.gb-success .ic{ font-size:3rem; }
.gb-success h3{ font-family:var(--bk-font-ui); font-weight:800; font-size:1.1rem; color:var(--bk-text); margin:12px 0 6px; }
.gb-success p{ font-family:var(--bk-font-ui); color:var(--bk-text-soft); font-size:.9rem; }
</style>

<script>
window.GroupBookingModal = (function () {
  'use strict';
  const AR = @json($isAr);
  const CUR = @json($isAr ? 'ل.س' : 'SYP');
  const BRANCH_ID = @json($branch->id);
  const SERVICES = @json($gbServices);
  const EMPLOYEES = @json($gbEmployees);
  const SLOTS_URL = @json(route('booking.group-slots'));
  const BOOK_URL  = @json(route('booking.group-book'));
  const ME_URL    = @json(route('customer.me'));
  const TOKEN     = @json(csrf_token());
  const SVC = {}; SERVICES.forEach(s => SVC[s.id] = s);

  const DAYS  = AR ? ['أح','إث','ثل','أر','خم','جم','سب'] : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  const MONTHS= AR ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
                   : ['January','February','March','April','May','June','July','August','September','October','November','December'];

  let st = { step:0, activeGuest:0, guests:[{services:[], employeeId:null}], staffMode:'any', oneEmployee:null,
             weekOffset:0, date:null, slot:null, cache:{} };

  const el = id => document.getElementById(id);
  const money = n => (parseFloat(n)||0).toFixed(0) + ' ' + CUR;
  const guestDur = g => g.services.reduce((s,id)=> s + (SVC[id]?.duration||0), 0);
  const guestPrice = g => g.services.reduce((s,id)=> s + (SVC[id]?.price||0), 0);
  const totalPrice = () => st.guests.reduce((s,g)=> s + guestPrice(g), 0);
  const totalServices = () => st.guests.reduce((s,g)=> s + g.services.length, 0);

  function open(initialServiceIds) {
    st = { step:0, activeGuest:0, guests:[{services:(initialServiceIds||[]).slice(), employeeId:null}],
           staffMode:'any', oneEmployee:null, weekOffset:0, date:null, slot:null, cache:{} };
    el('gb-overlay').classList.add('open');
    el('gb-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
    render();
  }
  function close() {
    el('gb-overlay').classList.remove('open'); el('gb-modal').classList.remove('open');
    document.body.style.overflow = '';
  }
  function back() { if (st.step > 0) { st.step--; render(); } }
  function next() {
    if (st.step === 0) { if (totalServices() === 0) return; st.step = 1; }
    else if (st.step === 1) { st.step = 2; st.date = today(); st.cache = {}; }
    else if (st.step === 2) { if (!st.slot) return; st.step = 3; }
    else if (st.step === 3) { confirmBooking(); return; }
    render();
  }

  /* ── render dispatch ── */
  function render() {
    el('gb-back').style.display = st.step > 0 && st.step < 4 ? 'grid' : 'none';
    el('gb-foot').style.display = st.step < 4 ? 'flex' : 'none';
    const titles = [ AR?'اختر خدماتك':'Choose services', AR?'مَن سيحضر؟':'Who’s coming?',
                     AR?'اختر الوقت':'Pick a time', AR?'مراجعة وتأكيد':'Review & confirm' ];
    el('gb-title').textContent = titles[st.step] || '';
    if (st.step === 0) renderServices();
    else if (st.step === 1) renderStaff();
    else if (st.step === 2) renderTime();
    else if (st.step === 3) renderReview();
    updateFoot();
  }
  function updateFoot() {
    const nextBtn = el('gb-next');
    el('gb-foot-p').textContent = money(totalPrice());
    if (st.step === 0) { el('gb-foot-l').textContent = totalServices()+' '+(AR?'خدمة':'services'); nextBtn.textContent = AR?'متابعة':'Continue'; nextBtn.disabled = totalServices()===0; }
    else if (st.step === 1) { el('gb-foot-l').textContent = st.guests.length+' '+(AR?'ضيف':'guests'); nextBtn.textContent = AR?'اختر الوقت':'Pick time'; nextBtn.disabled=false; }
    else if (st.step === 2) { el('gb-foot-l').textContent = st.slot ? st.slot.time : (AR?'اختر وقتاً':'Select a time'); nextBtn.textContent = AR?'متابعة':'Continue'; nextBtn.disabled = !st.slot; }
    else if (st.step === 3) { el('gb-foot-l').textContent = totalServices()+' '+(AR?'خدمة':'services'); nextBtn.textContent = AR?'تأكيد الحجز':'Confirm booking'; nextBtn.disabled=false; }
  }

  /* ── step 0: guests + services ── */
  function renderServices() {
    const b = el('gb-body');
    let h = '<div class="gb-guests">';
    st.guests.forEach((g,i)=>{
      h += `<button class="gb-guest-tab ${i===st.activeGuest?'is-active':''}" onclick="GroupBookingModal._pickGuest(${i})">`
         + (i===0 ? (AR?'أنا':'Me') : (AR?'ضيف ':'Guest ')+(i+1))
         + (g.services.length? `<span class="gb-guest-cnt">${g.services.length}</span>`:'')
         + (i>0? ` <span class="rm" onclick="event.stopPropagation();GroupBookingModal._removeGuest(${i})">✕</span>`:'')
         + '</button>';
    });
    if (st.guests.length < 8) h += `<button class="gb-guest-tab gb-guest-add" onclick="GroupBookingModal._addGuest()">+ ${AR?'ضيف':'Guest'}</button>`;
    h += '</div>';

    // services grouped by category, toggling for the active guest
    const active = st.guests[st.activeGuest];
    const byCat = {};
    SERVICES.forEach(s=>{ (byCat[s.cat_name||'—'] = byCat[s.cat_name||'—'] || []).push(s); });
    Object.keys(byCat).forEach(cat=>{
      if (cat && cat !== '—') h += `<div class="gb-cat-h">${cat}</div>`;
      byCat[cat].forEach(s=>{
        const on = active.services.indexOf(s.id) > -1;
        h += `<div class="gb-svc ${on?'is-on':''}" onclick="GroupBookingModal._toggleSvc(${s.id})">
                <div class="gb-svc-info"><div class="gb-svc-nm">${esc(s.name)}</div>
                <div class="gb-svc-meta">${s.duration?s.duration+(AR?' دقيقة':' min'):''}</div></div>
                <div class="gb-svc-pr">${s.price?money(s.price):(AR?'حسب الطلب':'On request')}</div>
                <div class="gb-check">✓</div></div>`;
      });
    });
    b.innerHTML = h;
  }
  function _pickGuest(i){ st.activeGuest=i; render(); }
  function _addGuest(){ st.guests.push({services:[],employeeId:null}); st.activeGuest=st.guests.length-1; render(); }
  function _removeGuest(i){ st.guests.splice(i,1); if(st.activeGuest>=st.guests.length) st.activeGuest=st.guests.length-1; render(); }
  function _toggleSvc(id){ const g=st.guests[st.activeGuest]; const k=g.services.indexOf(id); if(k>-1)g.services.splice(k,1); else g.services.push(id); render(); }

  /* ── step 1: staff ── */
  function renderStaff() {
    const b = el('gb-body');
    const multi = st.guests.length > 1;
    const empOpts = sel => '<option value="">'+(AR?'أي موظف متاح':'Any professional')+'</option>' +
      EMPLOYEES.map(e=>`<option value="${e.id}" ${sel==e.id?'selected':''}>${esc(e.name)}</option>`).join('');
    let h = `<div class="gb-section-h">${AR?'من يقدّم الخدمة؟':'Who will serve you?'}</div>`;
    const opt = (mode, t, s) => `<div class="gb-opt ${st.staffMode===mode?'is-on':''}" onclick="GroupBookingModal._setMode('${mode}')"><div class="gb-radio"></div><div><div class="gb-opt-t">${t}</div><div class="gb-opt-s">${s}</div></div></div>`;
    h += opt('any', AR?'أي موظف متاح':'Any professional', AR?'نختار لك الأنسب المتاح':'Maximum availability');
    if (multi) {
      h += opt('one', AR?'موظف واحد للجميع':'One professional for everyone', AR?'يخدم كل الضيوف بالتتابع':'Serves all guests in turn');
      h += opt('per', AR?'اختيار موظف لكل ضيف':'A professional per guest', AR?'كل ضيف يختار موظفه':'Each guest picks their own');
    } else {
      h += opt('one', AR?'اختيار موظف محدد':'Choose a professional', AR?'تحجز مع موظف بعينه':'Book with a specific one');
    }
    if (st.staffMode === 'one') {
      h += `<select class="gb-emp-select" onchange="GroupBookingModal._setOne(this.value)">${empOpts(st.oneEmployee)}</select>`;
    } else if (st.staffMode === 'per') {
      h += '<div class="gb-per-guest">';
      st.guests.forEach((g,i)=>{
        h += `<div class="gb-per-row"><label>${i===0?(AR?'أنا':'Me'):(AR?'ضيف ':'Guest ')+(i+1)}</label>
              <select class="gb-emp-select" style="margin-top:0" onchange="GroupBookingModal._setGuestEmp(${i},this.value)">${empOpts(g.employeeId)}</select></div>`;
      });
      h += '</div>';
    }
    b.innerHTML = h;
  }
  function _setMode(m){ st.staffMode=m; render(); }
  function _setOne(v){ st.oneEmployee = v?+v:null; }
  function _setGuestEmp(i,v){ st.guests[i].employeeId = v?+v:null; }

  /* ── spec builder ── */
  function buildSpec(withStart) {
    const mode = (st.staffMode === 'one') ? 'one' : 'split';
    const guests = st.guests.map(g => ({
      service_ids: g.services.slice(),
      employee_id: st.staffMode==='per' ? (g.employeeId||null) : (st.staffMode==='one' ? (st.oneEmployee||null) : null),
    }));
    const spec = { branch_id: BRANCH_ID, mode, guests };
    if (withStart) spec.start_time = st.date + ' ' + st.slot.time + ':00'; else spec.date = st.date;
    return spec;
  }

  /* ── step 2: time ── */
  function today(){ return new Date().toISOString().slice(0,10); }
  function addDays(s,n){ const d=new Date(s+'T00:00:00'); d.setDate(d.getDate()+n); return d.toISOString().slice(0,10); }

  function renderTime() {
    const b = el('gb-body');
    const first = new Date(addDays(today(), st.weekOffset)+'T00:00:00');
    let h = `<div class="gb-month"><span class="gb-month-l">${MONTHS[first.getMonth()]} ${first.getFullYear()}</span>
      <span><button class="gb-navb" ${st.weekOffset===0?'disabled':''} onclick="GroupBookingModal._week(-7)">‹</button>
      <button class="gb-navb" onclick="GroupBookingModal._week(7)">›</button></span></div><div class="gb-days">`;
    for (let i=0;i<14;i++){ const ds=addDays(today(), st.weekOffset+i); const d=new Date(ds+'T00:00:00');
      h += `<div class="gb-day ${ds===st.date?'is-active':''}" onclick="GroupBookingModal._pickDay('${ds}')"><div class="d">${d.getDate()}</div><div class="w">${DAYS[d.getDay()]}</div></div>`; }
    h += '</div><div class="gb-slots" id="gb-slots"></div>';
    b.innerHTML = h;
    fetchSlots();
  }
  function _week(n){ st.weekOffset=Math.max(0,st.weekOffset+n); if(st.date < addDays(today(),st.weekOffset)) st.date = addDays(today(),st.weekOffset); render(); }
  function _pickDay(ds){ st.date=ds; st.slot=null; render(); }

  function fetchSlots() {
    const grid = el('gb-slots'); if(!grid) return;
    if (st.cache[st.date]) { paintSlots(st.cache[st.date]); return; }
    grid.innerHTML = '<div class="gb-skel"></div><div class="gb-skel"></div><div class="gb-skel"></div>';
    const q = specToQuery(buildSpec(false));
    fetch(SLOTS_URL + '?' + q).then(r=>r.text()).then(t=>{ const d=JSON.parse(t.replace(/^﻿/,'')); st.cache[st.date]=d; if(el('gb-slots')) paintSlots(d); })
      .catch(()=>{ if(el('gb-slots')) el('gb-slots').innerHTML = `<div class="gb-empty">${AR?'تعذّر التحميل':'Failed to load'}</div>`; });
  }
  function paintSlots(d){
    const grid = el('gb-slots'); if(!grid) return;
    if (!d.available || !d.slots.length){ grid.innerHTML = `<div class="gb-empty">${AR?'لا أوقات متاحة في هذا اليوم':'No times available this day'}</div>`; updateFoot(); return; }
    grid.innerHTML = d.slots.map(s=>`<button class="gb-slot ${st.slot&&st.slot.time===s.time?'is-on':''}" onclick="GroupBookingModal._pickSlot('${s.time}')">${s.time}</button>`).join('');
  }
  function _pickSlot(t){ st.slot={time:t}; document.querySelectorAll('#gb-slots .gb-slot').forEach(b=>b.classList.toggle('is-on', b.textContent.trim()===t)); updateFoot(); }

  function specToQuery(spec){
    const p = new URLSearchParams(); p.set('branch_id',spec.branch_id); p.set('mode',spec.mode); p.set('date',spec.date);
    spec.guests.forEach((g,i)=>{ g.service_ids.forEach(sid=>p.append(`guests[${i}][service_ids][]`,sid)); if(g.employee_id) p.set(`guests[${i}][employee_id]`,g.employee_id); });
    return p.toString();
  }

  /* ── step 3: review ── */
  function renderReview() {
    const b = el('gb-body');
    const empName = id => { const e=EMPLOYEES.find(x=>x.id==id); return e?e.name:(AR?'أي موظف':'Any'); };
    let h = '';
    st.guests.forEach((g,i)=>{
      const who = i===0?(AR?'أنا':'Me'):(AR?'ضيف ':'Guest ')+(i+1);
      let staff = AR?'أي موظف متاح':'Any professional';
      if (st.staffMode==='one') staff = st.oneEmployee?empName(st.oneEmployee):(AR?'موظف واحد':'One professional');
      else if (st.staffMode==='per') staff = g.employeeId?empName(g.employeeId):(AR?'أي موظف':'Any');
      h += `<div class="gb-sum-row"><span class="g">${who} · ${staff}</span></div>`;
      g.services.forEach(id=>{ const s=SVC[id]; h += `<div class="gb-sum-row"><span>${esc(s.name)}</span><span>${money(s.price)}</span></div>`; });
    });
    const dLbl = new Date(st.date+'T00:00:00').toLocaleDateString(AR?'ar-SY':'en-US',{weekday:'long',day:'numeric',month:'long'});
    h += `<div class="gb-sum-row"><span class="g">${AR?'الموعد':'When'}</span><strong>${dLbl} · ${st.slot.time}</strong></div>`;
    h += `<div class="gb-sum-row" style="border:0"><strong>${AR?'الإجمالي':'Total'}</strong><strong class="gb-foot-p" style="font-size:1.1rem">${money(totalPrice())}</strong></div>`;
    b.innerHTML = h;
  }

  /* ── confirm → book ── */
  async function confirmBooking() {
    const me = await fetch(ME_URL).then(r=>r.json()).catch(()=>({authenticated:false}));
    if (!me.authenticated) { window.CustomerAuthModal && CustomerAuthModal.open(()=>confirmBooking()); return; }
    const btn = el('gb-next'); btn.disabled=true; btn.textContent = AR?'جارٍ الحجز…':'Booking…';
    const res = await fetch(BOOK_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':TOKEN,'X-Requested-With':'XMLHttpRequest'}, body:JSON.stringify(buildSpec(true)) })
      .then(r=>r.text()).then(t=>JSON.parse(t.replace(/^﻿/,''))).catch(()=>({error:true}));
    if (res.booked) showSuccess(res.summary);
    else if (res.conflict) { st.step=2; st.slot=null; st.cache={}; render(); alert(res.message || (AR?'تعذّر الحجز في هذا الوقت. اختر وقتاً آخر.':'Couldn’t book this time. Pick another.')); }
    else { btn.disabled=false; btn.textContent=AR?'تأكيد الحجز':'Confirm booking'; alert(AR?'حدث خطأ. حاول مجدداً.':'Something went wrong. Try again.'); }
  }
  function showSuccess(sum) {
    st.step = 4; render();
    el('gb-body').innerHTML = `<div class="gb-success"><div class="ic">✅</div>
      <h3>${AR?'تم تأكيد حجزك!':'Booking confirmed!'}</h3>
      <p>${sum?sum.start:''}<br><strong>${totalServices()} ${AR?'خدمة':'services'} · ${money(sum?sum.total:totalPrice())}</strong></p>
      <button class="bkf-btn bkf-btn-primary bkf-btn-block" style="margin-top:20px" onclick="GroupBookingModal.close()">${AR?'تم ✓':'Done ✓'}</button></div>`;
    el('gb-title').textContent = AR?'تم':'Done';
    el('gb-back').style.display='none'; el('gb-foot').style.display='none';
  }

  function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

  document.addEventListener('keydown', e=>{ if(e.key==='Escape' && el('gb-modal').classList.contains('open')) close(); });

  return { open, close, back, next, _pickGuest, _addGuest, _removeGuest, _toggleSvc, _setMode, _setOne, _setGuestEmp, _week, _pickDay, _pickSlot };
})();
</script>
