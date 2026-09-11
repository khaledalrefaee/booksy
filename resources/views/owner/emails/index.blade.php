@extends('owner.dashboard')
@section('content')

<div class="page-content">
    {{-- ── Header ── --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 grid-margin">
        <div>
            <h4 class="mb-1">{{ __('Emails') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Emails') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2"
                data-bs-toggle="modal" data-bs-target="#composeModal">
            <i data-feather="send" style="width:16px;height:16px;"></i>
            {{ __('Send new email') }}
        </button>
    </div>

    @include('owner.partials.flash')

    {{-- ── History card ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-semibold mb-0">{{ __('Sent emails') }}</h6>
                <span class="text-muted tx-13">{{ __(':count total', ['count' => $history->total()]) }}</span>
            </div>

            {{-- Filter bar (server-side: spans all records, then paginates) --}}
            <form method="get" action="{{ route('owner.emails.index') }}" id="em-filter-form" class="row g-2 mt-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i data-feather="search" style="width:15px;height:15px;"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0"
                               placeholder="{{ __('Search by subject or sender…') }}"
                               value="{{ request('q') }}" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select name="status" class="form-select form-select-sm em-auto">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach(['sent'=>__('Sent'),'partial'=>__('Partial'),'failed'=>__('Failed'),'queued'=>__('Queued'),'sending'=>__('Sending')] as $val=>$label)
                            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="audience" class="form-select form-select-sm em-auto">
                        <option value="">{{ __('All audiences') }}</option>
                        @foreach(['all'=>__('All companies'),'active'=>__('Active companies only'),'selected'=>__('Selected companies'),'manual'=>__('Manual addresses')] as $val=>$label)
                            <option value="{{ $val }}" @selected(request('audience')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <select name="per_page" class="form-select form-select-sm em-auto" aria-label="{{ __('Per page') }}">
                        @foreach(['10','20','50'] as $opt)
                            <option value="{{ $opt }}" @selected((string)$perPage===$opt)>{{ __(':n per page', ['n'=>$opt]) }}</option>
                        @endforeach
                        <option value="all" @selected($perPage==='all')>{{ __('Show all') }}</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="card-body p-0 pt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="em-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('Date') }}</th>
                            <th>{{ __('Subject') }}</th>
                            <th>{{ __('From') }}</th>
                            <th>{{ __('Sent to') }}</th>
                            <th>{{ __('Result') }}</th>
                            <th class="pe-4 text-end">{{ __('Details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $mail)
                            @php($badge = match($mail->status) {
                                'sent' => 'success', 'partial' => 'warning',
                                'failed' => 'danger', default => 'secondary',
                            })
                            <tr>
                                <td class="ps-4 text-nowrap text-muted tx-13">{{ $mail->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="fw-semibold tx-13 text-truncate" style="max-width:220px;">{{ $mail->subject }}</div>
                                    <div class="text-muted tx-12 text-truncate" style="max-width:220px;">{{ str($mail->body)->limit(60) }}</div>
                                </td>
                                <td class="tx-12 text-nowrap">{{ $mail->from_address }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-muted border tx-12">{{ $mail->audienceLabel() }}</span>
                                    <div class="text-muted tx-12 mt-1">{{ __(':count recipient(s)', ['count' => $mail->recipients_count]) }}</div>
                                </td>
                                <td class="tx-12 text-nowrap">
                                    <span class="badge rounded-pill bg-{{ $badge }}-subtle text-{{ $badge }} border border-{{ $badge }}-subtle">
                                        {{ $mail->sent_count }}/{{ $mail->recipients_count }}
                                    </span>
                                    @if($mail->failed_count > 0)
                                        <div class="text-danger tx-12 mt-1">{{ __(':count failed', ['count' => $mail->failed_count]) }}</div>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <button type="button" class="btn btn-sm btn-light rounded-pill em-view"
                                            data-bs-toggle="modal" data-bs-target="#detailModal"
                                            data-subject="{{ $mail->subject }}"
                                            data-from="{{ $mail->from_name }} · {{ $mail->from_address }}"
                                            data-audience="{{ $mail->audienceLabel() }}"
                                            data-status="{{ $mail->sent_count }}/{{ $mail->recipients_count }}"
                                            data-body="{{ $mail->body }}"
                                            data-error="{{ $mail->last_error }}"
                                            data-recipients="{{ json_encode(collect($mail->recipients ?? [])->pluck('email')->filter()->values()) }}">
                                        <i data-feather="eye" style="width:14px;height:14px;"></i>
                                        <span class="ms-1">{{ __('View') }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="em-empty">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <i data-feather="mail" style="width:40px;height:40px;" class="text-muted opacity-50"></i>
                                        <p class="mb-0">{{ __('No emails sent yet.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-4 py-3 border-top">
                    <span class="text-muted tx-12">
                        {{ __('Showing :from–:to of :total', ['from'=>$history->firstItem(), 'to'=>$history->lastItem(), 'total'=>$history->total()]) }}
                    </span>
                    {{ $history->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════ Compose modal ═══════════ --}}
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#4B5D34,#3a4a28);">
                <h5 class="modal-title d-flex align-items-center gap-2" id="composeModalLabel">
                    <i data-feather="mail" style="width:18px;height:18px;"></i>
                    {{ __('Compose email') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form method="post" action="{{ route('owner.emails.store') }}">
                @csrf
                <div class="modal-body p-4" style="max-height:calc(100vh - 210px);overflow-y:auto;">
                    <div class="row g-3">
                        {{-- From --}}
                        <div class="col-md-6">
                            <label for="em-from" class="form-label fw-semibold">
                                {{ __('From') }} <span class="text-danger">*</span>
                            </label>
                            <select name="from_address" id="em-from" class="form-select @error('from_address') is-invalid @enderror" required>
                                @foreach($senders as $sender)
                                    <option value="{{ $sender['address'] }}" @selected(old('from_address') === $sender['address'])>
                                        {{ $sender['name'] }} · {{ $sender['address'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Verified glowrez.com addresses only.') }}</div>
                        </div>

                        {{-- Audience --}}
                        <div class="col-md-6">
                            <label for="em-audience" class="form-label fw-semibold">
                                {{ __('Send to') }} <span class="text-danger">*</span>
                            </label>
                            <select name="audience" id="em-audience" class="form-select @error('audience') is-invalid @enderror" required>
                                <option value="all" @selected(old('audience') === 'all')>{{ __('All companies') }}</option>
                                <option value="active" @selected(old('audience') === 'active')>{{ __('Active companies only') }}</option>
                                <option value="selected" @selected(old('audience') === 'selected')>{{ __('Selected companies') }}</option>
                                <option value="manual" @selected(old('audience') === 'manual')>{{ __('Manual addresses') }}</option>
                            </select>
                        </div>

                        {{-- Selected companies --}}
                        <div class="col-12 d-none" id="em-companies-wrap">
                            <label for="em-companies" class="form-label fw-semibold">{{ __('Companies') }}</label>
                            <input type="text" id="em-company-filter" class="form-control form-control-sm mb-2"
                                   placeholder="{{ __('Filter companies…') }}" autocomplete="off">
                            <select name="company_ids[]" id="em-companies" class="form-select" multiple size="7">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                            data-label="{{ str($company->name_en.' '.$company->name_ar.' '.$company->email)->lower() }}"
                                            @selected(collect(old('company_ids'))->contains($company->id))>
                                        {{ $company->localizedName() }} · {{ $company->email }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                {{ __('Ctrl/Cmd-click to select multiple.') }}
                                <span id="em-company-count" class="fw-semibold text-primary"></span>
                            </div>
                        </div>

                        {{-- Manual addresses --}}
                        <div class="col-12 d-none" id="em-manual-wrap">
                            <label for="em-manual" class="form-label fw-semibold">{{ __('Email addresses') }}</label>
                            <textarea name="manual_emails" id="em-manual" class="form-control" rows="3"
                                      placeholder="name@example.com, other@example.com">{{ old('manual_emails') }}</textarea>
                            <div class="form-text">{{ __('Separate with commas, spaces or new lines.') }}</div>
                        </div>

                        {{-- Subject --}}
                        <div class="col-12">
                            <label for="em-subject" class="form-label fw-semibold">
                                {{ __('Subject') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="subject" id="em-subject" class="form-control @error('subject') is-invalid @enderror"
                                   maxlength="200" required value="{{ old('subject') }}">
                        </div>

                        {{-- Body --}}
                        <div class="col-12">
                            <label for="em-body" class="form-label fw-semibold">
                                {{ __('Message') }} <span class="text-danger">*</span>
                            </label>
                            <textarea name="body" id="em-body" class="form-control @error('body') is-invalid @enderror" rows="8"
                                      maxlength="5000" required>{{ old('body') }}</textarea>
                            <div class="form-text">{{ __('Sent inside the branded GlowRez template (logo + header). Line breaks are preserved.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i data-feather="send" style="width:15px;height:15px;" class="me-1"></i>
                        {{ __('Send email') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════ Detail modal ═══════════ --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">{{ __('Email details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body p-4" style="max-height:calc(100vh - 200px);overflow-y:auto;">
                <dl class="row mb-3 tx-13">
                    <dt class="col-sm-3 text-muted fw-normal">{{ __('Subject') }}</dt>
                    <dd class="col-sm-9 fw-semibold" id="d-subject">—</dd>
                    <dt class="col-sm-3 text-muted fw-normal">{{ __('From') }}</dt>
                    <dd class="col-sm-9" id="d-from">—</dd>
                    <dt class="col-sm-3 text-muted fw-normal">{{ __('Sent to') }}</dt>
                    <dd class="col-sm-9" id="d-audience">—</dd>
                    <dt class="col-sm-3 text-muted fw-normal">{{ __('Result') }}</dt>
                    <dd class="col-sm-9" id="d-status">—</dd>
                </dl>

                {{-- Failure reason (only shown when there was one) --}}
                <div id="d-error-wrap" class="alert alert-danger d-flex align-items-start gap-2 tx-13 d-none" role="alert">
                    <i data-feather="alert-triangle" style="width:16px;height:16px;flex:none;margin-top:2px;"></i>
                    <div>
                        <div class="fw-semibold">{{ __('Failure reason') }}</div>
                        <div id="d-error"></div>
                    </div>
                </div>

                <h6 class="fw-semibold tx-13 text-muted mb-2">{{ __('Message') }}</h6>
                <div id="d-body" class="p-3 bg-light rounded-3 tx-13" style="white-space:pre-wrap;line-height:1.7;"></div>

                <h6 class="fw-semibold tx-13 text-muted mt-3 mb-2">
                    {{ __('Recipients') }} <span id="d-recipients-count" class="badge bg-secondary rounded-pill"></span>
                </h6>
                <div id="d-recipients" class="d-flex flex-wrap gap-1" style="max-height:160px;overflow:auto;"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    /* ── Move modals to <body> so the template's transformed wrapper can't clip
         or mis-size them (keeps position:fixed relative to the viewport). ── */
    ['composeModal', 'detailModal'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el && el.parentNode !== document.body) { document.body.appendChild(el); }
    });

    /* ── Compose: audience-dependent fields ── */
    const audience      = document.getElementById('em-audience');
    const companiesWrap = document.getElementById('em-companies-wrap');
    const manualWrap    = document.getElementById('em-manual-wrap');
    const companies     = document.getElementById('em-companies');
    const companyFilter = document.getElementById('em-company-filter');
    const companyCount  = document.getElementById('em-company-count');

    function toggleAudience() {
        companiesWrap.classList.toggle('d-none', audience.value !== 'selected');
        manualWrap.classList.toggle('d-none', audience.value !== 'manual');
    }
    function updateCompanyCount() {
        const n = Array.from(companies.selectedOptions).length;
        companyCount.textContent = n ? '(' + n + ')' : '';
    }
    if (companyFilter) {
        companyFilter.addEventListener('input', function () {
            const q = companyFilter.value.trim().toLowerCase();
            Array.from(companies.options).forEach(function (opt) {
                opt.hidden = q !== '' && !(opt.dataset.label || '').includes(q);
            });
        });
    }
    audience.addEventListener('change', toggleAudience);
    companies.addEventListener('change', updateCompanyCount);
    toggleAudience();
    updateCompanyCount();

    /* ── Re-open compose modal if the server bounced validation errors ── */
    @if($errors->any())
        var composeEl = document.getElementById('composeModal');
        if (composeEl && window.bootstrap) { new bootstrap.Modal(composeEl).show(); }
    @endif

    /* ── Detail modal population ── */
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function (ev) {
            const btn = ev.relatedTarget;
            if (!btn) return;
            document.getElementById('d-subject').textContent  = btn.dataset.subject || '—';
            document.getElementById('d-from').textContent      = btn.dataset.from || '—';
            document.getElementById('d-audience').textContent  = btn.dataset.audience || '—';
            document.getElementById('d-status').textContent    = btn.dataset.status || '—';
            document.getElementById('d-body').textContent      = btn.dataset.body || '';

            const err = (btn.dataset.error || '').trim();
            document.getElementById('d-error').textContent = err;
            document.getElementById('d-error-wrap').classList.toggle('d-none', !err);

            let list = [];
            try { list = JSON.parse(btn.dataset.recipients || '[]'); } catch (e) { list = []; }
            document.getElementById('d-recipients-count').textContent = list.length;
            const box = document.getElementById('d-recipients');
            box.innerHTML = '';
            if (!list.length) {
                box.innerHTML = '<span class="text-muted tx-12">—</span>';
            } else {
                list.forEach(function (email) {
                    const chip = document.createElement('span');
                    chip.className = 'badge bg-light text-dark border rounded-pill tx-12';
                    chip.textContent = email;
                    box.appendChild(chip);
                });
            }
        });
    }

    /* ── History filter: server-side. Selects submit instantly; the search box
         submits on Enter. Changing a filter resets to page 1 (the form has no
         "page" field, so it drops off the query string). ── */
    const filterForm = document.getElementById('em-filter-form');
    if (filterForm) {
        filterForm.querySelectorAll('.em-auto').forEach(function (el) {
            el.addEventListener('change', function () { filterForm.submit(); });
        });
    }

    /* Re-render feather icons injected above */
    if (window.feather) { window.feather.replace(); }
})();
</script>
@endpush

@endsection
