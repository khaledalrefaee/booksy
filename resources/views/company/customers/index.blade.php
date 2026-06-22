@extends('company.dashboard')
@section('content')
<div class="page-content">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">{{ __('Customers') }}</h4>
            <p class="text-muted mb-0 tx-13">{{ __('All customers who booked at your business') }}</p>
        </div>
        <div class="d-flex gap-2">
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
                <div class="col-sm-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i data-feather="search" style="width:14px;height:14px;color:#C9A227;"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="{{ __('Search by name or phone…') }}"
                            value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-sm-3">
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('All branches') }}</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 d-flex gap-2">
                    <button class="btn btn-primary rounded-pill flex-fill">{{ __('Filter') }}</button>
                    @if(request()->hasAny(['search','branch_id']))
                        <a href="{{ route('company.customers.index') }}" class="btn btn-outline-secondary rounded-pill px-3" title="{{ __('Clear') }}">
                            <i data-feather="x" style="width:14px;height:14px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Customer list --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            {{-- Column headers --}}
            <div class="px-4 py-2 border-bottom" style="border-color:rgba(255,255,255,.06)!important;">
                <div class="row gx-3 align-items-center">
                    <div class="col-4"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Customer') }}</span></div>
                    <div class="col-2"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Phone') }}</span></div>
                    <div class="col-2 text-center"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Visits') }}</span></div>
                    <div class="col-2 text-end"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Total Spent') }}</span></div>
                    <div class="col-2 text-end"><span class="tx-11 fw-bold text-muted text-uppercase" style="letter-spacing:.8px;">{{ __('Last Visit') }}</span></div>
                </div>
            </div>

            @forelse($customers as $c)
            <a href="{{ route('company.customers.show', $c) }}"
               class="d-block px-4 py-3 bk-table-row text-decoration-none"
               style="border-bottom:1px solid rgba(255,255,255,.04);">
                <div class="row gx-3 align-items-center">
                    <div class="col-4">
                        <div class="d-flex align-items-center gap-3">
                            @if($c->avatar)
                                <img src="{{ asset('storage/' . $c->avatar) }}" alt=""
                                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                            @else
                                <div style="width:36px;height:36px;border-radius:50%;background:rgba(201,162,39,.15);color:#C9A227;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;">
                                    {{ mb_substr($c->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <div class="fw-semibold tx-13">{{ $c->name }}</div>
                                @if($c->age)
                                    <div class="text-muted tx-11">{{ $c->age }} {{ __('years') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-2 tx-13 text-muted" dir="ltr">{{ $c->phone }}</div>
                    <div class="col-2 text-center">
                        <span style="font-size:12px;font-weight:700;background:rgba(201,162,39,.12);color:#C9A227;padding:3px 10px;border-radius:20px;">
                            {{ $c->total_visits ?? 0 }}
                        </span>
                    </div>
                    <div class="col-2 text-end fw-bold tx-13">
                        @if($c->total_spent)
                            {{ number_format((float)$c->total_spent, 0) }}
                        @else
                            —
                        @endif
                    </div>
                    <div class="col-2 text-end tx-12 text-muted">
                        @if($c->last_visit)
                            {{ \Carbon\Carbon::parse($c->last_visit)->translatedFormat('d M Y') }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </a>
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
                <form method="POST" action="{{ route('company.customers.store') }}">
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
                            <input type="text" name="phone" class="form-control" required placeholder="{{ __('e.g. 0912345678') }}" dir="ltr">
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold tx-13">{{ __('Age') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <input type="number" name="age" class="form-control" min="1" max="120" placeholder="25">
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

    {{-- ─── IMPORT MODAL ──────────────────────────────────────────────── --}}
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content rounded-4">
                <form method="POST" action="{{ route('company.customers.import') }}" enctype="multipart/form-data">
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
@endsection
