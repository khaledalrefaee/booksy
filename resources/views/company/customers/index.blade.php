@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Customers') }}</h4>
            <p class="text-muted mb-0 tx-13">{{ __('All customers who booked at your business') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('company.customers.export.csv') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i data-feather="download" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Export CSV') }}
            </a>
            <button class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                <i data-feather="upload" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Import Excel') }}
            </button>
            <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i data-feather="user-plus" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                {{ __('Add Customer') }}
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4 bk-a1">
            <div class="bk-stat" data-accent="gold">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-gold">
                        <i data-feather="users"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Total Customers') }}</div>
                        <div class="bk-stat-sub">{{ __('all time') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($totalCustomers) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 bk-a2">
            <div class="bk-stat" data-accent="green">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-green">
                        <i data-feather="user-plus"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('New This Month') }}</div>
                        <div class="bk-stat-sub">{{ now()->translatedFormat('F Y') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($newThisMonth) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4 bk-a3">
            <div class="bk-stat" data-accent="blue">
                <div class="bk-stat-left">
                    <div class="bk-stat-icon bk-icon-blue">
                        <i data-feather="filter"></i>
                    </div>
                    <div class="bk-stat-info">
                        <div class="bk-stat-label">{{ __('Showing') }}</div>
                        <div class="bk-stat-sub">{{ __('filtered results') }}</div>
                    </div>
                </div>
                <div class="bk-stat-num">{{ number_format($customers->total()) }}</div>
            </div>
        </div>
    </div>

    @include('company.partials.flash')

    {{-- Filter bar --}}
    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i data-feather="search" style="width:14px;height:14px;color:#C9A227;"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="{{ __('Search by name or phone…') }}"
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-6 col-sm-2">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('All branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    <select name="tag" class="form-select">
                        <option value="">{{ __('All tags') }}</option>
                        @foreach(\App\Models\Customer::TAGS as $tKey => $tMeta)
                        <option value="{{ $tKey }}" {{ request('tag') === $tKey ? 'selected' : '' }}>
                            {{ $tMeta['icon'] }} {{ __($tMeta['label_key']) }}
                        </option>
                        @endforeach
                        <option value="" disabled>──</option>
                        <option value="" {{ request('banned') === '1' ? '' : '' }}>—</option>
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    <select name="source" class="form-select">
                        <option value="">{{ __('All sources') }}</option>
                        @foreach(\App\Models\Customer::SOURCES as $sKey => $sMeta)
                        <option value="{{ $sKey }}" {{ request('source') === $sKey ? 'selected' : '' }}>
                            {{ $sMeta['icon'] }} {{ __($sMeta['label_key']) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-1 d-flex gap-1 flex-wrap">
                    <button class="btn btn-primary rounded-pill flex-fill">{{ __('Filter') }}</button>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2">
                <a href="{{ route('company.customers.index', array_merge(request()->only('search','branch_id','tag','source'), ['birthday_month'=>'1'])) }}"
                   class="btn btn-sm rounded-pill px-3 {{ request('birthday_month') === '1' ? 'btn-warning' : 'btn-outline-secondary' }}">
                    🎂 {{ __('Birthdays this month') }}
                </a>
                <a href="{{ route('company.customers.index', array_merge(request()->only('search','branch_id','tag','source'), ['inactive'=>'1'])) }}"
                   class="btn btn-sm rounded-pill px-3 {{ request('inactive') === '1' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    💤 {{ __('Inactive customers') }}
                </a>
                <a href="{{ route('company.customers.index', array_merge(request()->only('search','branch_id','tag','source'), ['has_debt'=>'1'])) }}"
                   class="btn btn-sm rounded-pill px-3 {{ request('has_debt') === '1' ? 'btn-danger' : 'btn-outline-secondary' }}">
                    💳 {{ __('Has debt') }}
                </a>
                <a href="{{ route('company.customers.index', ['banned' => '1']) }}"
                   class="btn btn-sm rounded-pill px-3 {{ request('banned') === '1' ? 'btn-danger' : 'btn-outline-danger' }}">
                    🚫 {{ __('Banned') }}
                </a>
                @if(request()->hasAny(['search','branch_id','tag','banned','source','birthday_month','inactive','has_debt']))
                    <a href="{{ route('company.customers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="{{ __('Clear') }}">
                        <i data-feather="x" style="width:14px;height:14px;"></i> {{ __('Clear filters') }}
                    </a>
                    @endif
                </div>
        </div>
    </form>

    {{-- Customer list --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            {{-- Column headers (hidden on mobile) --}}
            <div class="px-4 py-2 border-bottom d-none d-md-block" style="border-color:rgba(255,255,255,.06)!important;">
                <div class="row gx-3 align-items-center">
                    <div class="col-md-4"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Customer') }}</span></div>
                    <div class="col-md-2"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Phone') }}</span></div>
                    <div class="col-md-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Visits') }}</span></div>
                    <div class="col-md-2 text-end"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Total Spent') }}</span></div>
                    <div class="col-md-2 text-end"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Last Visit') }}</span></div>
                    <div class="col-md-1 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;"></span></div>
                </div>
            </div>

            @forelse($customers as $c)
            @php $tagMeta = \App\Models\Customer::TAGS[$c->tag] ?? null; @endphp
            <div class="px-4 py-3 bk-table-row {{ $c->is_banned ? 'opacity-50' : '' }}" style="border-bottom:1px solid rgba(255,255,255,.04);{{ $c->is_banned ? 'border-inline-start:3px solid #ef4444;' : '' }}">
                <div class="row gx-3 align-items-center">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <a href="{{ route('company.customers.show', $c) }}" class="d-flex align-items-center gap-3 text-decoration-none" style="color:inherit;">
                            @if($c->avatar)
                                <img src="{{ asset('storage/' . $c->avatar) }}" alt=""
                                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                            @else
                                <div style="width:36px;height:36px;border-radius:50%;background:{{ $c->is_banned ? 'rgba(239,68,68,.15)' : 'rgba(201,162,39,.15)' }};color:{{ $c->is_banned ? '#ef4444' : '#C9A227' }};display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;">
                                    {{ $c->is_banned ? '🚫' : mb_substr($c->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <div class="fw-semibold tx-13">
                                    {{ $c->name }}
                                    @if($tagMeta)
                                    <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:8px;background:{{ $tagMeta['color'] }}18;color:{{ $tagMeta['color'] }};margin-inline-start:4px;">
                                        {{ $tagMeta['icon'] }} {{ __($tagMeta['label_key']) }}
                                    </span>
                                    @endif
                                    @if($c->is_banned)
                                    <span style="font-size:9px;font-weight:700;padding:1px 6px;border-radius:8px;background:rgba(239,68,68,.15);color:#ef4444;margin-inline-start:4px;">
                                        🚫 {{ __('Banned') }}
                                    </span>
                                    @endif
                                </div>
                                @if($c->age)
                                    <div class="text-muted tx-11">{{ $c->age }} {{ __('years') }}</div>
                                @endif
                                @if($c->notes)
                                    <div class="text-muted tx-11">📝 {{ Str::limit($c->notes, 30) }}</div>
                                @endif
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-2 tx-13 text-muted" dir="ltr">{{ $c->phone }}</div>
                    <div class="col-6 col-md-1 text-center text-md-center">
                        <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                            {{ $c->total_visits ?? 0 }}
                        </span>
                    </div>
                    <div class="col-6 col-md-2 text-end fw-bold tx-13">
                        @if($c->total_spent)
                            {{ number_format((float)$c->total_spent, 0) }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="col-6 col-md-2 text-end tx-12 text-muted">
                        @if($c->last_visit)
                            {{ \Carbon\Carbon::parse($c->last_visit)->translatedFormat('d M Y') }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="col-12 col-md-1 text-center mt-2 mt-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm p-1" style="opacity:.5;" data-bs-toggle="dropdown" data-bs-auto-close="true">
                                <i data-feather="more-vertical" style="width:16px;height:16px;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                                @if($c->phone)
                                <li><button class="dropdown-item tx-13" onclick="openWaModal('{{ $c->phone }}', '{{ e($c->name) }}')">💬 {{ __('WhatsApp') }}</button></li>
                                @endif
                                <li><button class="dropdown-item tx-13" onclick="openTagModal({{ $c->id }}, '{{ $c->tag }}')">🏷️ {{ __('Tag') }}</button></li>
                                <li><button class="dropdown-item tx-13" onclick="openEditCustomer({{ json_encode(['id'=>$c->id,'name'=>$c->name,'phone'=>$c->phone,'age'=>$c->age,'notes'=>$c->notes,'date_of_birth'=>$c->date_of_birth?->format('Y-m-d'),'source'=>$c->source,'branch_ids'=>$c->linkedBranches->pluck('id')]) }})">✏️ {{ __('Edit') }}</button></li>
                                <li><hr class="dropdown-divider"></li>
                                @if($c->is_banned)
                                <li>
                                    <form method="POST" action="{{ route('company.customers.toggle-ban', $c) }}">
                                        @csrf @method('PUT')
                                        <button type="submit" class="dropdown-item tx-13">✅ {{ __('Unban') }}</button>
                                    </form>
                                </li>
                                @else
                                <li><button class="dropdown-item tx-13" onclick="openBanModal({{ $c->id }}, '{{ e($c->name) }}')">🚫 {{ __('Ban') }}</button></li>
                                @endif
                                <li><button class="dropdown-item tx-13 text-danger" onclick="openDeleteCustomer({{ $c->id }}, '{{ e($c->name) }}')">🗑️ {{ __('Delete') }}</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="bk-empty py-5">
                <div class="bk-empty-ic mb-3">
                    <i data-feather="users" style="width:24px;height:24px;"></i>
                </div>
                <p>{{ __('No customers found.') }}</p>
                <p class="tx-12" style="color:rgba(255,255,255,.2);">{{ __('Customers appear here when they book an appointment.') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    @if($customers->hasPages())
    <div class="mt-3">{{ $customers->links() }}</div>
    @endif

    {{-- ─── ADD CUSTOMER MODAL ─────────────────────────────────────────── --}}
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('company.customers.store') }}" onsubmit="disableSubmit(this)">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i data-feather="user-plus" style="width:18px;height:18px;margin-inline-end:6px;color:#C9A227;"></i>
                            {{ __('Add Customer') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Rania Ahmad') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Phone') }} <span class="text-danger">*</span></label>
                            @php $dialCodes = config('booksy.dial_codes', []); $defaultDial = config('booksy.default_dial_code', '+963'); @endphp
                            <div class="d-flex gap-2" dir="ltr">
                                <select name="dial_code" class="form-select dial-code-select" style="max-width:130px;flex-shrink:0;" onchange="updatePhoneValidation(this)">
                                    @foreach($dialCodes as $code => $info)
                                    <option value="{{ $code }}" data-min="{{ $info['digits_min'] }}" data-max="{{ $info['digits_max'] }}"
                                            {{ $code === $defaultDial ? 'selected' : '' }}>
                                        {{ $info['flag'] }} {{ $code }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="tel" name="phone_number" class="form-control phone-input" required
                                       placeholder="{{ __('e.g.') }} 962812838"
                                       pattern="[0-9]*" inputmode="numeric">
                            </div>
                            <div class="phone-hint tx-11 text-muted mt-1"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Date of birth') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Age') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                                <input type="number" name="age" class="form-control" min="1" max="120" placeholder="25">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Source') }}</label>
                            <select name="source" class="form-select">
                                @foreach(\App\Models\Customer::SOURCES as $sKey => $sMeta)
                                <option value="{{ $sKey }}" {{ $sKey === 'walk_in' ? 'selected' : '' }}>{{ $sMeta['icon'] }} {{ __($sMeta['label_key']) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Linked branches') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($branches as $b)
                                <label style="cursor:pointer;">
                                    <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" class="d-none branch-chk">
                                    <span class="pm-card" style="--pm-color:#C9A227;padding:4px 10px;font-size:11px;">
                                        {{ $b->localizedName() }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('e.g. allergic to certain products, prefers morning slots...') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                            {{ __('Add Customer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── EDIT CUSTOMER MODAL ────────────────────────────────────────── --}}
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content rounded-4">
                <form method="POST" id="editCustomerForm" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i data-feather="edit" style="width:18px;height:18px;margin-inline-end:6px;color:#667eea;"></i>
                            {{ __('Edit Customer') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Phone') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2" dir="ltr">
                                <select name="dial_code" id="editDialCode" class="form-select dial-code-select" style="max-width:130px;flex-shrink:0;" onchange="updatePhoneValidation(this)">
                                    @foreach($dialCodes as $code => $info)
                                    <option value="{{ $code }}" data-min="{{ $info['digits_min'] }}" data-max="{{ $info['digits_max'] }}">
                                        {{ $info['flag'] }} {{ $code }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="tel" name="phone_number" id="editPhoneNumber" class="form-control phone-input" required
                                       pattern="[0-9]*" inputmode="numeric">
                            </div>
                            <div class="phone-hint tx-11 text-muted mt-1" id="editPhoneHint"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Date of birth') }}</label>
                                <input type="date" name="date_of_birth" id="editDOB" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold tx-13">{{ __('Age') }}</label>
                                <input type="number" name="age" id="editAge" class="form-control" min="1" max="120">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Source') }}</label>
                            <select name="source" id="editSource" class="form-select">
                                <option value="">—</option>
                                @foreach(\App\Models\Customer::SOURCES as $sKey => $sMeta)
                                <option value="{{ $sKey }}">{{ $sMeta['icon'] }} {{ __($sMeta['label_key']) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold tx-13">{{ __('Linked branches') }}</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($branches as $b)
                                <label style="cursor:pointer;">
                                    <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}" class="d-none edit-branch-chk">
                                    <span class="pm-card" style="--pm-color:#C9A227;padding:4px 10px;font-size:11px;">
                                        {{ $b->localizedName() }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Notes') }}</label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── DELETE CUSTOMER MODAL ──────────────────────────────────────── --}}
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <div class="modal-body text-center p-4">
                    <div style="font-size:40px;margin-bottom:12px;">🗑️</div>
                    <h6 class="fw-bold mb-2">{{ __('Delete customer?') }}</h6>
                    <p class="text-muted small mb-1" id="deleteCustomerName"></p>
                    <p class="text-muted small mb-3">{{ __('This will permanently delete this customer and all their data.') }}</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form method="POST" id="deleteCustomerForm" onsubmit="disableSubmit(this)">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── TAG MODAL ───────────────────────────────────────────────── --}}
    <div class="modal fade" id="tagModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4">
                <form method="POST" id="tagForm" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">🏷️ {{ __('Set Tag') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="d-flex flex-column gap-2">
                            <label style="cursor:pointer;">
                                <input type="radio" name="tag" value="" class="d-none tag-radio">
                                <div class="pm-card" style="--pm-color:#64748b;padding:8px 14px;">
                                    👤 {{ __('No tag') }}
                                </div>
                            </label>
                            @foreach(\App\Models\Customer::TAGS as $tKey => $tMeta)
                            <label style="cursor:pointer;">
                                <input type="radio" name="tag" value="{{ $tKey }}" class="d-none tag-radio">
                                <div class="pm-card" style="--pm-color:{{ $tMeta['color'] }};padding:8px 14px;">
                                    {{ $tMeta['icon'] }} {{ __($tMeta['label_key']) }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── BAN MODAL ──────────────────────────────────────────────── --}}
    <div class="modal fade" id="banModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content rounded-4">
                <form method="POST" id="banForm" onsubmit="disableSubmit(this)">
                    @csrf @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">🚫 {{ __('Ban Customer') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <p class="text-muted tx-13 mb-3">
                            {{ __('Banning') }} <strong id="banCustomerName"></strong>.
                            {{ __('This customer will not be able to book appointments.') }}
                        </p>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Reason') }} <span class="text-danger">*</span></label>
                            <input type="text" name="ban_reason" class="form-control" required
                                   placeholder="{{ __('e.g. No-show multiple times, abusive behavior...') }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                            🚫 {{ __('Ban') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── WHATSAPP MESSAGE MODAL ─────────────────────────────────── --}}
    <div class="modal fade" id="waModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
            <div class="modal-content rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">💬 {{ __('Send WhatsApp') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="d-flex flex-column gap-2 mb-3">
                        <button type="button" class="btn btn-sm text-start rounded-3 px-3 py-2" style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.15);"
                                onclick="sendWA('welcome')">
                            👋 {{ __('Welcome message') }}
                        </button>
                        <button type="button" class="btn btn-sm text-start rounded-3 px-3 py-2" style="background:rgba(102,126,234,.08);border:1px solid rgba(102,126,234,.15);"
                                onclick="sendWA('reminder')">
                            ⏰ {{ __('Appointment reminder') }}
                        </button>
                        <button type="button" class="btn btn-sm text-start rounded-3 px-3 py-2" style="background:rgba(201,162,39,.08);border:1px solid rgba(201,162,39,.15);"
                                onclick="sendWA('offer')">
                            🎁 {{ __('Special offer') }}
                        </button>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold tx-13">{{ __('Or write custom message') }}</label>
                        <textarea id="waCustomMsg" class="form-control" rows="3" placeholder="{{ __('Type your message...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn rounded-pill px-4 fw-bold" style="background:#25D366;color:#fff;border:none;"
                            onclick="sendWA('custom')">
                        💬 {{ __('Send') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── IMPORT MODAL ──────────────────────────────────────────────── --}}
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('company.customers.import') }}" enctype="multipart/form-data" onsubmit="disableSubmit(this)">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">
                            <i data-feather="upload" style="width:18px;height:18px;margin-inline-end:6px;color:#C9A227;"></i>
                            {{ __('Import Customers') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="p-3 rounded-3 mb-3" style="background:rgba(201,162,39,.06);border:1px solid rgba(201,162,39,.15);">
                            <div class="fw-bold tx-12 mb-2" style="color:#C9A227;">{{ __('File format') }}</div>
                            <p class="tx-12 text-muted mb-2">
                                {{ __('Upload an Excel (.xlsx) or CSV file with columns:') }}
                            </p>
                            <div class="d-flex gap-3 mb-1">
                                <code class="tx-11" style="color:#C9A227;">name</code>
                                <code class="tx-11" style="color:#C9A227;">phone</code>
                                <code class="tx-11" style="color:#C9A227;">age</code>
                                <span class="text-muted tx-11">({{ __('optional') }})</span>
                            </div>
                            <p class="tx-11 text-muted mb-0">
                                {{ __('Arabic column names are also supported:') }}
                                <code class="tx-11">الاسم</code> <code class="tx-11">الهاتف</code> <code class="tx-11">العمر</code>
                            </p>
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Choose file') }} <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" required accept=".xlsx,.xls,.csv">
                            <div class="tx-11 text-muted mt-1">{{ __('Max 20MB. Formats: .xlsx, .xls, .csv') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i data-feather="upload" style="width:14px;height:14px;margin-inline-end:4px;"></i>
                            {{ __('Import') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var EDIT_BASE = '{{ route("company.customers.update", "__ID__") }}';
var DELETE_BASE = '{{ route("company.customers.destroy", "__ID__") }}';
var TAG_BASE = '{{ route("company.customers.update-tag", "__ID__") }}';
var BAN_BASE = '{{ route("company.customers.toggle-ban", "__ID__") }}';
var currentWaPhone = '';
var companyName = '{{ e($branches->first()?->localizedName() ?? "") }}';

window.disableSubmit = function(form) {
    form.querySelectorAll('button[type="submit"]').forEach(function(b) { b.disabled = true; b.style.opacity = '.5'; });
};

var dialCodesData = @json(config('booksy.dial_codes'));

window.updatePhoneValidation = function(selectEl) {
    var opt = selectEl.options[selectEl.selectedIndex];
    var min = opt.dataset.min || 7;
    var max = opt.dataset.max || 15;
    var input = selectEl.closest('.d-flex').querySelector('.phone-input');
    var hint = selectEl.closest('.mb-3').querySelector('.phone-hint');
    input.minLength = min;
    input.maxLength = max;
    if (hint) {
        hint.textContent = min === max
            ? '{{ __("Must be exactly") }} ' + min + ' {{ __("digits") }}'
            : min + ' — ' + max + ' {{ __("digits") }}';
    }
    validatePhoneInput(input, min, max);
};

function validatePhoneInput(input, min, max) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.startsWith('0')) this.value = this.value.substring(1);
        var len = this.value.length;
        if (len > 0 && (len < min || len > max)) {
            this.style.borderColor = '#ef4444';
        } else {
            this.style.borderColor = '';
        }
    });
}

// Init validation on page load
document.querySelectorAll('.dial-code-select').forEach(function(s) { updatePhoneValidation(s); });

// DOB → Age auto-calc
document.querySelectorAll('input[name="date_of_birth"]').forEach(function(dobInput) {
    dobInput.addEventListener('change', function() {
        var ageInput = this.closest('.row').querySelector('input[name="age"]');
        if (!ageInput || !this.value) return;
        var dob = new Date(this.value);
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear();
        var m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        if (age > 0 && age < 120) ageInput.value = age;
    });
});

// Branch checkbox toggle
document.querySelectorAll('.branch-chk, .edit-branch-chk').forEach(function(chk) {
    chk.addEventListener('change', function() {
        this.closest('label').querySelector('.pm-card').classList.toggle('active', this.checked);
    });
});

window.openEditCustomer = function(c) {
    document.getElementById('editCustomerForm').action = EDIT_BASE.replace('__ID__', c.id);
    document.getElementById('editName').value = c.name || '';
    document.getElementById('editAge').value = c.age || '';
    document.getElementById('editNotes').value = c.notes || '';
    document.getElementById('editDOB').value = c.date_of_birth || '';
    document.getElementById('editSource').value = c.source || '';

    // Set branch checkboxes
    var branchIds = c.branch_ids || [];
    document.querySelectorAll('.edit-branch-chk').forEach(function(chk) {
        chk.checked = branchIds.indexOf(parseInt(chk.value)) !== -1;
        chk.closest('label').querySelector('.pm-card').classList.toggle('active', chk.checked);
    });

    // Split phone into dial code + number
    var phone = (c.phone || '').replace(/\s/g, '');
    var matched = false;
    var codes = Object.keys(dialCodesData).sort(function(a, b) { return b.length - a.length; });
    for (var i = 0; i < codes.length; i++) {
        var code = codes[i];
        var digits = code.replace('+', '');
        if (phone.startsWith('+' + digits) || phone.startsWith(digits)) {
            document.getElementById('editDialCode').value = code;
            var num = phone.startsWith('+') ? phone.substring(code.length) : phone.substring(digits.length);
            document.getElementById('editPhoneNumber').value = num;
            matched = true;
            break;
        }
    }
    if (!matched) {
        document.getElementById('editPhoneNumber').value = phone;
    }
    updatePhoneValidation(document.getElementById('editDialCode'));

    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
};

window.openDeleteCustomer = function(id, name) {
    document.getElementById('deleteCustomerForm').action = DELETE_BASE.replace('__ID__', id);
    document.getElementById('deleteCustomerName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteCustomerModal')).show();
};

window.openTagModal = function(id, currentTag) {
    document.getElementById('tagForm').action = TAG_BASE.replace('__ID__', id);
    document.querySelectorAll('.tag-radio').forEach(function(r) {
        r.checked = (r.value === (currentTag || ''));
        var card = r.closest('label').querySelector('.pm-card');
        card.classList.toggle('active', r.checked);
    });
    new bootstrap.Modal(document.getElementById('tagModal')).show();
};

document.querySelectorAll('.tag-radio').forEach(function(r) {
    r.addEventListener('change', function() {
        document.querySelectorAll('#tagModal .pm-card').forEach(function(c) { c.classList.remove('active'); });
        this.closest('label').querySelector('.pm-card').classList.add('active');
    });
});

window.openBanModal = function(id, name) {
    document.getElementById('banForm').action = BAN_BASE.replace('__ID__', id);
    document.getElementById('banCustomerName').textContent = name;
    new bootstrap.Modal(document.getElementById('banModal')).show();
};

var currentWaName = '';

window.openWaModal = function(phone, name) {
    var clean = phone.replace(/[^0-9+]/g, '');
    clean = clean.replace(/^\+/, '');
    currentWaPhone = clean;
    currentWaName = name;
    document.getElementById('waCustomMsg').value = '';
    new bootstrap.Modal(document.getElementById('waModal')).show();
};

var waTemplates = {
    welcome: @json(__("Hi :name! Welcome to :company. We look forward to serving you! 😊")),
    reminder: @json(__("Hi :name! This is a reminder about your upcoming appointment at :company. See you soon! 📅")),
    offer: @json(__("Hi :name! We have a special offer just for you at :company! Contact us for details. 🎁")),
};

window.sendWA = function(type) {
    var msg = '';
    if (type === 'custom') {
        msg = document.getElementById('waCustomMsg').value.trim();
        if (!msg) return;
    } else {
        msg = (waTemplates[type] || '')
            .replace(':name', currentWaName)
            .replace(':company', companyName);
    }
    var url = 'https://wa.me/' + currentWaPhone + '?text=' + encodeURIComponent(msg);
    window.open(url, '_blank');
    bootstrap.Modal.getInstance(document.getElementById('waModal'))?.hide();
};
</script>
@endpush
@endsection
