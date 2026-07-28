@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Reviews') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Reviews') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('owner.partials.flash')

    {{-- ── Stat cards ── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="message-square" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Total reviews') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="star" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Average rating') }}</div>
                        <div class="fw-bold tx-20">{{ $stats['average'] ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-feather="eye-off" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div class="text-muted tx-12 fw-semibold text-uppercase">{{ __('Hidden reviews') }}</div>
                        <div class="fw-bold tx-20">{{ number_format($stats['hidden']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <form method="get" action="{{ route('owner.reviews.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-4">
                    <label for="rev-q" class="form-label small fw-semibold mb-1">{{ __('Search') }}</label>
                    <input type="search" name="q" id="rev-q" class="form-control form-control-sm"
                           value="{{ $q }}" placeholder="{{ __('Search in comments…') }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="rev-rating" class="form-label small fw-semibold mb-1">{{ __('Rating') }}</label>
                    <select name="rating" id="rev-rating" class="form-select form-select-sm">
                        <option value="">{{ __('All ratings') }}</option>
                        @foreach([5, 4, 3, 2, 1] as $r)
                            <option value="{{ $r }}" @selected($filterRating === (string) $r)>{{ str_repeat('★', $r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="rev-state" class="form-label small fw-semibold mb-1">{{ __('State') }}</label>
                    <select name="state" id="rev-state" class="form-select form-select-sm">
                        <option value="">{{ __('All states') }}</option>
                        <option value="visible" @selected($filterState === 'visible')>{{ __('Visible') }}</option>
                        <option value="hidden" @selected($filterState === 'hidden')>{{ __('Hidden') }}</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-grow-1">{{ __('Filter') }}</button>
                    @if($q !== '' || $filterRating !== '' || $filterState !== '')
                        <a href="{{ route('owner.reviews.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Date') }}</th>
                            <th>{{ __('Branch') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Rating') }}</th>
                            <th style="min-width:240px;">{{ __('Comment') }}</th>
                            <th>{{ __('State') }}</th>
                            <th class="text-end pe-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                            <tr @class(['opacity-50' => $review->is_hidden])>
                                <td class="ps-4 text-nowrap text-muted tx-13">{{ $review->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    <div class="fw-semibold tx-13">{{ $review->branch?->localizedName() ?? '—' }}</div>
                                    <div class="text-muted tx-12">{{ $review->branch?->company?->localizedName() }}</div>
                                </td>
                                <td class="tx-13">{{ $review->customer?->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <span class="text-warning" aria-label="{{ $review->rating }}/5">{{ str_repeat('★', $review->rating) }}</span><span class="text-muted">{{ str_repeat('★', max(0, 5 - $review->rating)) }}</span>
                                </td>
                                <td class="tx-13" style="max-width:320px;">
                                    <span title="{{ $review->comment }}">{{ str($review->comment)->limit(120) ?: '—' }}</span>
                                </td>
                                <td>
                                    @if($review->is_hidden)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger" title="{{ $review->hidden_reason }}">{{ __('Hidden') }}</span>
                                    @else
                                        <span class="badge rounded-pill bg-success-subtle text-success">{{ __('Visible') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @can('owner-can', 'reviews.moderate')
                                        @if($review->is_hidden)
                                            <form method="post" action="{{ route('owner.reviews.toggle-hidden', $review) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill">
                                                    <i data-feather="eye" style="width:13px;height:13px;" class="me-1"></i>{{ __('Show') }}
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#modal-hide-review"
                                                    data-hide-url="{{ route('owner.reviews.toggle-hidden', $review) }}"
                                                    data-summary="{{ $review->branch?->localizedName() }} — {{ str_repeat('★', $review->rating) }}">
                                                <i data-feather="eye-off" style="width:13px;height:13px;" class="me-1"></i>{{ __('Hide') }}
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="star" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No reviews yet.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reviews->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">{{ $reviews->links() }}</div>
        @endif
    </div>

    {{-- ── Hide review modal ── --}}
    @can('owner-can', 'reviews.moderate')
    <div class="modal fade" id="modal-hide-review" tabindex="-1" aria-labelledby="modal-hide-review-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content border-0 shadow rounded-4" id="bk-hide-review-form">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modal-hide-review-title">{{ __('Hide review') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="bk-hide-review-summary"></p>
                    <label for="bk-hide-review-reason" class="form-label fw-semibold">
                        {{ __('Reason') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <textarea name="reason" id="bk-hide-review-reason" class="form-control" rows="3" maxlength="500" required></textarea>
                    <div class="form-text">{{ __('The review disappears from the public site; you can restore it any time.') }}</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger rounded-pill">{{ __('Hide') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@push('scripts')
<script>
(function () {
    'use strict';
    const modal = document.getElementById('modal-hide-review');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', (event) => {
        const btn = event.relatedTarget;
        if (!btn) return;
        document.getElementById('bk-hide-review-form').action = btn.dataset.hideUrl;
        document.getElementById('bk-hide-review-summary').textContent = btn.dataset.summary;
        document.getElementById('bk-hide-review-reason').value = '';
    });
    modal.addEventListener('shown.bs.modal', () => document.getElementById('bk-hide-review-reason').focus());
})();
</script>
@endpush

@endsection
