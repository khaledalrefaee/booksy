{{--
  Compensation section — included in create.blade.php and edit.blade.php
  Variables expected:
    $services            — grouped collection (for per-service rates)
    $compensation        — EmployeeCompensation|null
    $serviceCommissions  — [service_id => rate] array (for edit)
--}}
@php
    $compensation       ??= null;
    $serviceCommissions ??= [];
    $currencies          = config('booksy.currencies', []);
    $defaultCurrency     = config('booksy.default_currency', 'SYP');
    $selectedCurrency    = old('comp_currency', $compensation?->currency ?? $defaultCurrency);

    $compType     = old('comp_type',            $compensation?->type            ?? '');
    $baseAmount   = old('comp_base_amount',     $compensation?->base_amount     ?? '');
    $payPeriod    = old('comp_pay_period',      $compensation?->pay_period      ?? 'monthly');
    $commType     = old('comp_commission_type', $compensation?->commission_type ?? '');
    $commRate     = old('comp_commission_rate', $compensation?->commission_rate ?? '');
@endphp

@once
@push('company-styles')
<style>
/* ── Compensation type cards ── */
.comp-type-cards { display:flex; gap:10px; flex-wrap:wrap; }
.comp-type-card {
    flex:1; min-width:110px;
    border: 2px solid rgba(255,255,255,.1);
    border-radius:14px; padding:14px 10px;
    text-align:center; cursor:pointer;
    transition:border-color .2s, background .2s, transform .15s;
    background:rgba(255,255,255,.03);
    user-select:none;
}
.bk-theme-light .comp-type-card { border-color:#e2e8f0; background:#f8fafc; }
.comp-type-card:hover { border-color:rgba(255,255,255,.25); transform:translateY(-2px); }
.bk-theme-light .comp-type-card:hover { border-color:#94a3b8; }
.comp-type-card.active {
    border-color:#667eea; background:rgba(102,126,234,.12);
}
.comp-type-card .ct-icon {
    font-size:24px; margin-bottom:6px; display:block; line-height:1;
}
.comp-type-card .ct-label { font-size:12px; font-weight:700; }
.comp-type-card .ct-sub   { font-size:10px; opacity:.5; margin-top:2px; }

/* ── Pay period pills ── */
.period-pills { display:flex; gap:6px; flex-wrap:wrap; }
.period-pill {
    flex:1; min-width:70px;
    border:1.5px solid rgba(255,255,255,.1); border-radius:10px;
    padding:8px 6px; text-align:center; cursor:pointer;
    font-size:12px; font-weight:600; transition:all .18s;
    background:rgba(255,255,255,.03);
}
.bk-theme-light .period-pill { border-color:#e2e8f0; background:#f8fafc; }
.period-pill.active { border-color:#fa709a; background:rgba(250,112,154,.1); color:#fa709a; }
.bk-theme-light .period-pill.active { border-color:#fa709a; color:#fa709a; }

/* ── Commission type toggles ── */
.comm-type-wrap { display:flex; gap:8px; }
.comm-type-btn {
    flex:1; padding:10px; text-align:center; border-radius:10px;
    border:1.5px solid rgba(255,255,255,.1); cursor:pointer;
    font-size:12px; font-weight:600; transition:all .18s;
    background:rgba(255,255,255,.03);
}
.bk-theme-light .comm-type-btn { border-color:#e2e8f0; background:#f8fafc; }
.comm-type-btn.active { border-color:#43e97b; background:rgba(67,233,123,.1); color:#43e97b; }
.bk-theme-light .comm-type-btn.active { border-color:#28a745; color:#1a7a36; }

/* ── Per-service rate rows ── */
.svc-rate-row {
    display:flex; align-items:center; gap:10px;
    padding:8px 0; border-bottom:1px solid rgba(255,255,255,.05);
}
.bk-theme-light .svc-rate-row { border-color:#f1f5f9; }
.svc-rate-row:last-child { border-bottom:none; }
.svc-rate-name { flex:1; font-size:12px; font-weight:500; }
.svc-rate-input {
    width:90px; padding:5px 8px; border-radius:8px;
    border:1.5px solid rgba(255,255,255,.1); background:rgba(255,255,255,.05);
    color:inherit; font-size:12px; text-align:right; outline:none;
    transition:border-color .18s;
}
.bk-theme-light .svc-rate-input { background:#fff; border-color:#e2e8f0; color:#1e293b; }
.svc-rate-input:focus { border-color:#667eea; }
.svc-rate-suffix { font-size:11px; opacity:.5; flex-shrink:0; }

/* animated panels */
.comp-panel { overflow:hidden; transition:max-height .3s ease, opacity .3s ease; }
.comp-panel.hidden { max-height:0 !important; opacity:0; pointer-events:none; }
.comp-panel.visible { max-height:600px; opacity:1; }
</style>
@endpush
@endonce

<div class="card border-0 sec-card bk-a2" style="margin-bottom:18px;">
    <div class="card-body p-0">
        <div class="sec-header">
            <div class="sec-icon" style="background:rgba(250,112,154,.12);">
                <i data-feather="dollar-sign" style="width:15px;height:15px;color:#fa709a;"></i>
            </div>
            <div>
                <div class="sec-title">{{ __('Compensation') }}</div>
                <div class="sec-sub">{{ __('Salary, commission & pay period') }}</div>
            </div>
        </div>
        <div class="sec-body">

            {{-- ── Step 1: type ── --}}
            <div class="f-label mb-2">
                {{ __('Compensation type') }}
                <span style="font-weight:500;text-transform:none;letter-spacing:0;opacity:.5;margin-inline-start:6px;">
                    ({{ __('optional') }})
                </span>
            </div>
            <div class="comp-type-cards mb-4">
                @foreach([
                    ''           => ['⏭️', __('Skip'),           __('Set later')],
                    'salary'     => ['💰', __('Fixed Salary'),   __('Monthly/weekly/daily rate')],
                    'commission' => ['📊', __('Commission'),      __('% of each service')],
                    'mixed'      => ['🔄', __('Mixed'),           __('Salary + commission')],
                ] as $val => [$icon, $lbl, $sub])
                @php $isActive = ($compType === $val); @endphp
                <div class="comp-type-card {{ $isActive ? 'active' : '' }}"
                     data-type="{{ $val }}" onclick="selectCompType('{{ $val }}')">
                    <span class="ct-icon">{{ $icon }}</span>
                    <div class="ct-label">{{ $lbl }}</div>
                    <div class="ct-sub">{{ $sub }}</div>
                </div>
                @endforeach
            </div>
            <input type="hidden" name="comp_type" id="comp_type" value="{{ $compType }}">

            {{-- ── Salary panel ── --}}
            <div id="panel-salary" class="comp-panel {{ in_array($compType, ['salary','mixed']) ? 'visible' : 'hidden' }}">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="f-label">{{ __('Base amount') }}</label>
                        <div class="d-flex gap-2">
                            <select name="comp_currency" class="f-input form-select" style="max-width:110px; flex-shrink:0;">
                                @foreach($currencies as $code => $cur)
                                <option value="{{ $code }}" {{ $selectedCurrency === $code ? 'selected' : '' }}>
                                    {{ $cur['symbol'] }} {{ $code }}
                                </option>
                                @endforeach
                            </select>
                            <input type="number" name="comp_base_amount" id="comp_base_amount"
                                   class="f-input form-control" min="0" step="0.01"
                                   value="{{ $baseAmount }}"
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="f-label">{{ __('Pay period') }}</label>
                        <div class="period-pills mt-1">
                            @foreach(['daily' => __('Daily'), 'weekly' => __('Weekly'), 'monthly' => __('Monthly')] as $p => $plbl)
                            <div class="period-pill {{ $payPeriod === $p ? 'active' : '' }}"
                                 data-period="{{ $p }}" onclick="selectPeriod('{{ $p }}')">
                                {{ $plbl }}
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="comp_pay_period" id="comp_pay_period" value="{{ $payPeriod }}">
                    </div>
                </div>
            </div>

            {{-- ── Commission panel ── --}}
            <div id="panel-commission" class="comp-panel {{ in_array($compType, ['commission','mixed']) ? 'visible' : 'hidden' }}">
                <div class="mb-3">
                    <label class="f-label mb-2">{{ __('Commission structure') }}</label>
                    <div class="comm-type-wrap">
                        <div class="comm-type-btn {{ $commType === 'flat' ? 'active' : '' }}"
                             data-ctype="flat" onclick="selectCommType('flat')">
                            <div>🎯</div>
                            <div>{{ __('Flat %') }}</div>
                            <div style="font-size:10px;opacity:.5;font-weight:400;">{{ __('Same for all services') }}</div>
                        </div>
                        <div class="comm-type-btn {{ $commType === 'per_service' ? 'active' : '' }}"
                             data-ctype="per_service" onclick="selectCommType('per_service')">
                            <div>✂️</div>
                            <div>{{ __('Per service') }}</div>
                            <div style="font-size:10px;opacity:.5;font-weight:400;">{{ __('Different % per service') }}</div>
                        </div>
                    </div>
                    <input type="hidden" name="comp_commission_type" id="comp_commission_type" value="{{ $commType }}">
                </div>

                {{-- Flat rate input --}}
                <div id="panel-flat" class="comp-panel {{ $commType === 'flat' ? 'visible' : 'hidden' }} mb-3">
                    <label class="f-label">{{ __('Commission rate') }} (%)</label>
                    <div class="d-flex align-items-center gap-2 mt-1" style="max-width:180px;">
                        <input type="number" name="comp_commission_rate" id="comp_commission_rate"
                               class="f-input form-control" min="0" max="100" step="0.5"
                               value="{{ $commRate }}" placeholder="0">
                        <span class="f-label mb-0" style="font-size:18px;opacity:.6;">%</span>
                    </div>
                </div>

                {{-- Per-service rates --}}
                <div id="panel-per-service" class="comp-panel {{ $commType === 'per_service' ? 'visible' : 'hidden' }} mb-3">
                    <label class="f-label mb-2">{{ __('Rate per service') }}</label>
                    @if($services->isNotEmpty())
                        @foreach($services as $catId => $group)
                        @php $cat = $group->first()->serviceCategory; @endphp
                        <div class="mb-3">
                            <div style="font-size:10px;font-weight:700;opacity:.4;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                                {{ $cat ? (app()->getLocale()==='ar' ? ($cat->name_ar ?: $cat->name_en) : ($cat->name_en ?: $cat->name_ar)) : __('Uncategorized') }}
                            </div>
                            @foreach($group as $svc)
                            @php $svcRate = old("comp_service_rates.{$svc->id}", $serviceCommissions[$svc->id] ?? ''); @endphp
                            <div class="svc-rate-row">
                                <span class="svc-rate-name">
                                    {{ app()->getLocale()==='ar' ? ($svc->name_ar ?: $svc->name_en) : ($svc->name_en ?: $svc->name_ar) }}
                                </span>
                                <input type="number" name="comp_service_rates[{{ $svc->id }}]"
                                       class="svc-rate-input"
                                       min="0" max="100" step="0.5"
                                       value="{{ $svcRate }}"
                                       placeholder="—">
                                <span class="svc-rate-suffix">%</span>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    @else
                        <p class="sec-sub" style="font-size:12px;">{{ __('Add services to this branch first to set rates.') }}</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function(){
    function selectCompType(val) {
        document.getElementById('comp_type').value = val;
        document.querySelectorAll('.comp-type-card').forEach(function(c){
            c.classList.toggle('active', c.dataset.type === val);
        });
        var showSalary = (val === 'salary' || val === 'mixed');
        var showComm   = (val === 'commission' || val === 'mixed');
        togglePanel('panel-salary', showSalary);
        togglePanel('panel-commission', showComm);
        // highlight skip card differently
        var skipCard = document.querySelector('.comp-type-card[data-type=""]');
        if (skipCard) skipCard.style.opacity = val === '' ? '1' : '.65';
    }

    function selectPeriod(p) {
        document.getElementById('comp_pay_period').value = p;
        document.querySelectorAll('.period-pill').forEach(function(el){
            el.classList.toggle('active', el.dataset.period === p);
        });
    }

    function selectCommType(t) {
        document.getElementById('comp_commission_type').value = t;
        document.querySelectorAll('.comm-type-btn').forEach(function(el){
            el.classList.toggle('active', el.dataset.ctype === t);
        });
        togglePanel('panel-flat',        t === 'flat');
        togglePanel('panel-per-service', t === 'per_service');
    }

    function togglePanel(id, show) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('visible', show);
        el.classList.toggle('hidden',  !show);
    }

    window.selectCompType  = selectCompType;
    window.selectPeriod    = selectPeriod;
    window.selectCommType  = selectCommType;

    // Apply initial skip opacity on load
    (function() {
        var currentType = document.getElementById('comp_type');
        if (currentType && currentType.value !== '') {
            var skipCard = document.querySelector('.comp-type-card[data-type=""]');
            if (skipCard) skipCard.style.opacity = '.65';
        }
    })();
})();
</script>
@endpush
@endonce
