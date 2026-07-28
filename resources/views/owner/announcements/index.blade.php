@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Announcements') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Announcements') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('owner.partials.flash')

    <div class="row g-4">
        {{-- ── Compose ── --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3">
                        <i data-feather="send" style="width:16px;height:16px;" class="me-1"></i>
                        {{ __('New announcement') }}
                    </h6>
                    <form method="post" action="{{ route('owner.announcements.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="ann-title" class="form-label fw-semibold">
                                {{ __('Title') }} <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="text" name="title" id="ann-title" class="form-control"
                                   maxlength="120" required value="{{ old('title') }}">
                        </div>
                        <div class="mb-3">
                            <label for="ann-body" class="form-label fw-semibold">
                                {{ __('Message') }} <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <textarea name="body" id="ann-body" class="form-control" rows="4"
                                      maxlength="500" required>{{ old('body') }}</textarea>
                            <div class="form-text">{{ __('Shown in the notification bell of each targeted company panel.') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="ann-link" class="form-label fw-semibold">{{ __('Link (optional)') }}</label>
                            <input type="url" name="link" id="ann-link" class="form-control"
                                   placeholder="https://…" value="{{ old('link') }}">
                        </div>
                        <div class="mb-3">
                            <label for="ann-target" class="form-label fw-semibold">
                                {{ __('Target') }} <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <select name="target" id="ann-target" class="form-select" required>
                                <option value="all" @selected(old('target') === 'all')>{{ __('All companies') }}</option>
                                <option value="active" @selected(old('target') === 'active')>{{ __('Active companies only') }}</option>
                                <option value="plan" @selected(old('target') === 'plan')>{{ __('Companies on a specific plan') }}</option>
                                <option value="expiring" @selected(old('target') === 'expiring')>{{ __('Subscriptions expiring within 7 days') }}</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="ann-plan-wrap">
                            <label for="ann-plan" class="form-label fw-semibold">{{ __('Plan') }}</label>
                            <select name="plan_id" id="ann-plan" class="form-select">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                {{ __('Send announcement') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── History ── --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h6 class="fw-semibold mb-0">{{ __('Recent announcements') }}</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">{{ __('Date') }}</th>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Target') }}</th>
                                    <th>{{ __('Companies') }}</th>
                                    <th class="pe-4">{{ __('Admin') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($history as $log)
                                    <tr>
                                        <td class="ps-4 text-nowrap text-muted tx-13">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="fw-semibold tx-13">{{ $log->new_values['title'] ?? $log->auditable_label }}</div>
                                            <div class="text-muted tx-12 text-truncate" style="max-width:260px;">{{ $log->new_values['body'] ?? '' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-light text-muted border tx-12">
                                                {{ [
                                                    'all'      => __('All companies'),
                                                    'active'   => __('Active companies only'),
                                                    'plan'     => __('Specific plan'),
                                                    'expiring' => __('Expiring soon'),
                                                ][$log->new_values['target'] ?? ''] ?? ($log->new_values['target'] ?? '—') }}
                                            </span>
                                        </td>
                                        <td class="fw-semibold">{{ $log->new_values['companies'] ?? '—' }}</td>
                                        <td class="pe-4 tx-13">{{ $log->owner?->name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <i data-feather="bell" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                                <p class="mb-0">{{ __('No announcements sent yet.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';
    const target   = document.getElementById('ann-target');
    const planWrap = document.getElementById('ann-plan-wrap');

    function togglePlan() {
        planWrap.classList.toggle('d-none', target.value !== 'plan');
    }

    target.addEventListener('change', togglePlan);
    togglePlan();
})();
</script>
@endpush

@endsection
