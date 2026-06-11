@extends('owner.dashboard')

@push('owner-styles')
<style>
.emp-form-hero {
    background: linear-gradient(135deg, #c9a227 0%, #a07d10 100%);
    border-radius: 20px; padding: 26px 30px;
    margin-bottom: 24px; color: #000;
    position: relative; overflow: hidden;
}
.emp-form-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,.1); pointer-events: none;
}
[dir="rtl"] .emp-form-hero::before { right: auto; left: -50px; }

.sec-card  { border-radius: 16px !important; margin-bottom: 18px; overflow: hidden; }
.sec-header {
    padding: 14px 20px 13px;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; align-items: center; gap: 10px;
}
.bk-theme-light .sec-header { border-bottom-color: rgba(0,0,0,.07); }
.sec-icon  { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.sec-body  { padding: 18px 20px; }
.sec-title { font-weight: 700; font-size: 13px; }
.sec-sub   { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 1px; }
.bk-theme-light .sec-sub { color: rgba(0,0,0,.45); }

.f-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: rgba(255,255,255,.5); margin-bottom: 5px; display: block;
}
.bk-theme-light .f-label { color: rgba(0,0,0,.5); }
.f-input {
    width: 100%; background: rgba(255,255,255,.05); border: 1.5px solid rgba(255,255,255,.1);
    border-radius: 11px; padding: 9px 13px; font-size: 13px; color: inherit;
    transition: border-color .2s, background .2s, box-shadow .2s; outline: none;
}
.f-input::placeholder { color: rgba(255,255,255,.25); }
.f-input:focus { border-color: #c9a227; background: rgba(201,162,39,.07); box-shadow: 0 0 0 3px rgba(201,162,39,.15); }
.bk-theme-light .f-input { background: #f8f9fa; border-color: #dee2e6; color: #212529; }
.bk-theme-light .f-input::placeholder { color: rgba(0,0,0,.3); }
.bk-theme-light .f-input:focus { background: #fff; border-color: #c9a227; box-shadow: 0 0 0 3px rgba(201,162,39,.12); }
.f-input.is-invalid { border-color: #f5576c !important; }

/* Employee block separator */
.emp-block { border-bottom: 1px solid rgba(255,255,255,.07); padding-bottom: 22px; margin-bottom: 22px; }
.bk-theme-light .emp-block { border-bottom-color: rgba(0,0,0,.07); }
.emp-block:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
.emp-block-num {
    width: 26px; height: 26px; border-radius: 8px;
    background: rgba(201,162,39,.2); color: #c9a227;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px; flex-shrink: 0;
}
.bk-theme-light .emp-block-num { background: rgba(201,162,39,.12); }

/* Day pills */
.day-pill { border-radius: 11px; border: 1.5px solid rgba(255,255,255,.09); padding: 11px 13px; transition: all .2s; background: rgba(255,255,255,.03); }
.bk-theme-light .day-pill { border-color: #e8ecf1; background: #fafbfc; }
.day-pill.active { border-color: rgba(201,162,39,.5); background: rgba(201,162,39,.07); }
.bk-theme-light .day-pill.active { border-color: #c9a227; background: rgba(201,162,39,.06); }
.day-name { font-weight: 700; font-size: 12px; }
.day-times { display: flex; align-items: center; gap: 5px; margin-top: 9px; }
.day-times input {
    flex: 1; border: 1.5px solid rgba(255,255,255,.1); border-radius: 9px; padding: 5px 7px;
    font-size: 12px; text-align: center; background: rgba(255,255,255,.05); color: inherit; outline: none;
}
.day-times input:focus { border-color: #c9a227; }
.day-times input:disabled { opacity: .3; cursor: not-allowed; }
.bk-theme-light .day-times input { background: #fff; border-color: #dee2e6; color: #212529; }
.day-times .sep { color: rgba(255,255,255,.3); font-size: 11px; flex-shrink: 0; }
.bk-theme-light .day-times .sep { color: rgba(0,0,0,.3); }

.toggle-row {
    display: flex; align-items: center; gap: 12px;
    background: rgba(255,255,255,.03); border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,.08); padding: 12px 14px; cursor: pointer;
}
.bk-theme-light .toggle-row { background: #f8f9fa; border-color: #dee2e6; }

.btn-add-more {
    width: 100%; border: 1.5px dashed rgba(255,255,255,.15); background: rgba(255,255,255,.02);
    border-radius: 12px; padding: 10px; color: rgba(255,255,255,.5);
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-add-more:hover { border-color: #c9a227; color: #c9a227; background: rgba(201,162,39,.05); }
.bk-theme-light .btn-add-more { border-color: #dee2e6; color: rgba(0,0,0,.4); background: transparent; }
.bk-theme-light .btn-add-more:hover { border-color: #c9a227; color: #c9a227; }

.btn-remove-emp {
    border: none; background: transparent; color: rgba(255,255,255,.3);
    cursor: pointer; border-radius: 7px; padding: 4px 8px;
    transition: all .2s; font-size: 12px;
}
.btn-remove-emp:hover { background: rgba(245,87,108,.12); color: #f5576c; }
.bk-theme-light .btn-remove-emp { color: rgba(0,0,0,.3); }

.btn-submit-main {
    background: linear-gradient(135deg, #c9a227, #f4a642);
    color: #000; border: none; border-radius: 13px;
    padding: 12px 36px; font-weight: 700; font-size: 14px;
    cursor: pointer; box-shadow: 0 4px 18px rgba(201,162,39,.3);
    transition: opacity .2s, transform .15s;
}
.btn-submit-main:hover { opacity: .9; transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="emp-form-hero bk-a1">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index:1;">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0" style="--bs-breadcrumb-divider-color:rgba(0,0,0,.4);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('owner.branches.index') }}" class="text-decoration-none" style="color:rgba(0,0,0,.6);font-size:13px;">{{ __('Branches') }}</a>
                        </li>
                        @unless($wizard)
                        <li class="breadcrumb-item">
                            <a href="{{ route('owner.branches.employees.index', $branch) }}" class="text-decoration-none" style="color:rgba(0,0,0,.6);font-size:13px;">{{ $branch->localizedName() }}</a>
                        </li>
                        @endunless
                        <li class="breadcrumb-item active" style="color:rgba(0,0,0,.5);font-size:13px;">{{ __('New Employee') }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0" style="font-family:'Poppins',sans-serif;">{{ __('Add employees') }} — {{ $branch->localizedName() }}</h3>
            </div>
            @unless($wizard)
            <a href="{{ route('owner.branches.employees.index', $branch) }}"
               style="background:rgba(0,0,0,.15); color:#000; border:1.5px solid rgba(0,0,0,.25); font-weight:600; font-size:13px;"
               class="btn btn-sm rounded-pill px-3">
                <i data-feather="arrow-left" style="width:13px;height:13px;"></i>
                <span class="{{ app()->getLocale()==='ar' ? 'me-1' : 'ms-1' }}">{{ __('Back') }}</span>
            </a>
            @endunless
        </div>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-xl-10">

            @if($wizard)
                @include('owner.branches.partials.wizard-steps', ['currentStep' => 3])
            @endif

            <form method="post" action="{{ route('owner.branches.employees.store', $branch) }}" id="employees-form">
                @csrf
                @if($wizard)<input type="hidden" name="wizard" value="1">@endif

                @error('employees')
                    <div class="alert alert-danger rounded-3 mb-3">{{ $message }}</div>
                @enderror

                <div class="card border-0 sec-card bk-a2">
                    <div class="card-body p-0">
                        <div class="sec-header">
                            <div class="sec-icon" style="background:rgba(201,162,39,.15);">
                                <i data-feather="users" style="width:15px;height:15px;color:#c9a227;"></i>
                            </div>
                            <div>
                                <div class="sec-title">{{ __('Basic Information') }}</div>
                                <div class="sec-sub">{{ __('Add one or more employees. Use the button below to add another form.') }}</div>
                            </div>
                        </div>
                        <div class="sec-body">
                            <div id="employee-forms">
                                @php $rows = old('employees', [[]]); @endphp
                                @foreach($rows as $index => $row)
                                    @include('owner.employees.partials.form-block', [
                                        'index' => $index,
                                        'row'   => is_array($row) ? $row : [],
                                    ])
                                @endforeach
                            </div>
                            <button type="button" class="btn-add-more mt-2" id="add-employee-btn">
                                <i data-feather="plus" style="width:14px;height:14px;"></i>
                                {{ __('Add another employee') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    @if($wizard)
                        <form method="post" action="{{ route('owner.branches.employees.skip', $branch) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.12); font-size:13px;">{{ __('Skip for now') }}</button>
                        </form>
                        <button type="submit" form="employees-form" class="btn-submit-main">
                            <i data-feather="check" style="width:14px;height:14px;" class="{{ app()->getLocale()==='ar' ? 'ms-2' : 'me-2' }}"></i>
                            {{ __('Save & finish') }}
                        </button>
                    @else
                        <a href="{{ route('owner.branches.employees.index', $branch) }}"
                           class="btn btn-sm rounded-pill px-4" style="background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.12); font-size:13px;">{{ __('Cancel') }}</a>
                        <button type="submit" form="employees-form" class="btn-submit-main">
                            <i data-feather="save" style="width:14px;height:14px;" class="{{ app()->getLocale()==='ar' ? 'ms-2' : 'me-2' }}"></i>
                            {{ __('Save all') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<template id="employee-block-template">
    @include('owner.employees.partials.form-block', ['index' => '__INDEX__', 'row' => []])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    feather.replace();
    const container = document.getElementById('employee-forms');
    const template  = document.getElementById('employee-block-template');
    const addBtn    = document.getElementById('add-employee-btn');

    function nextIndex() {
        let max = -1;
        container.querySelectorAll('.employee-block').forEach(b => {
            const i = parseInt(b.dataset.index, 10);
            if (!isNaN(i) && i > max) max = i;
        });
        return max + 1;
    }

    function reindex() {
        container.querySelectorAll('.employee-block').forEach((block, i) => {
            block.dataset.index = i;
            const badge = block.querySelector('.js-employee-number');
            if (badge) badge.textContent = String(i + 1);
            block.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/employees\[\d+\]/, `employees[${i}]`);
            });
            const rem = block.querySelector('.js-remove-employee');
            if (rem) rem.hidden = container.querySelectorAll('.employee-block').length <= 1;
        });
        feather.replace();
    }

    addBtn.addEventListener('click', () => {
        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
        const div  = document.createElement('div');
        div.innerHTML = html.trim();
        container.appendChild(div.firstElementChild);
        reindex();
        // Init new toggles
        initToggles(container.lastElementChild);
    });

    container.addEventListener('click', e => {
        const btn = e.target.closest('.js-remove-employee');
        if (btn && container.querySelectorAll('.employee-block').length > 1) {
            btn.closest('.employee-block').remove();
            reindex();
        }
    });

    function initToggles(scope) {
        scope = scope || document;
        scope.querySelectorAll('.wh-toggle').forEach(t => {
            t.addEventListener('change', function () {
                const n = this.id.split('_').slice(-1)[0];
                const pill  = document.getElementById('pill-' + n);
                const times = document.getElementById('times-' + n);
                if (!pill || !times) return;
                pill.classList.toggle('active', this.checked);
                times.style.display = this.checked ? 'flex' : 'none';
                times.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
            });
        });
    }

    initToggles();
    document.getElementById('employees-form').addEventListener('submit', reindex);
    reindex();
});
</script>
@endpush
@endsection
