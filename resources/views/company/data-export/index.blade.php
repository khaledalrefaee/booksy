@extends('company.dashboard')

@section('content')
<div class="page-content">

    {{-- Breadcrumb --}}
    <div class="mb-4">
        <h4 class="mb-2">{{ __('My data') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('company.dashboard') }}">{{ __('Dashboard') }}</a>
                </li>
                <li class="breadcrumb-item active">{{ __('My data') }}</li>
            </ol>
        </nav>
    </div>

    @include('company.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:48px;height:48px;">
                            <i data-feather="download-cloud" style="width:22px;height:22px;" class="text-success"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">{{ __('Download a copy of your data') }}</h6>
                            <p class="text-muted small mb-0">
                                {{ __('Get all your account data in one file — you can keep it, review it, or use it to restore your data later.') }}
                            </p>
                        </div>
                    </div>

                    <hr class="my-3">

                    <p class="fw-semibold text-muted text-uppercase small mb-2">
                        {{ __("What's inside the file") }}
                    </p>
                    <ul class="list-unstyled small mb-4">
                        <li class="mb-2 d-flex gap-2">
                            <i data-feather="file-text" style="width:16px;height:16px;" class="text-success mt-1 flex-shrink-0"></i>
                            <span><strong>company-data.xlsx</strong> — {{ __('Your data in a readable Excel file, one sheet per section (branches, employees, appointments, invoices…).') }}</span>
                        </li>
                        <li class="mb-2 d-flex gap-2">
                            <i data-feather="code" style="width:16px;height:16px;" class="text-success mt-1 flex-shrink-0"></i>
                            <span><strong>company-data.json</strong> — {{ __('A complete copy used to re-import your data if you come back.') }}</span>
                        </li>
                    </ul>

                    <div class="alert alert-light border small d-flex gap-2 mb-4" role="alert">
                        <i data-feather="info" style="width:16px;height:16px;" class="text-muted mt-1 flex-shrink-0"></i>
                        <span class="text-muted">
                            {{ __('Only sections you use are included. Empty, unused sections are skipped to keep the file clean.') }}
                        </span>
                    </div>

                    <a href="{{ route('company.data-export.download') }}"
                       class="btn btn-success rounded-3 px-4 d-inline-flex align-items-center gap-2">
                        <i data-feather="download" style="width:18px;height:18px;"></i>
                        {{ __('Download my data') }}
                    </a>

                </div>
            </div>

            {{-- ══ Danger zone: close account ══ --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-inline-start:4px solid var(--bs-danger,#dc3545)!important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-2">
                        <div class="rounded-circle bg-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:48px;height:48px;">
                            <i data-feather="power" style="width:22px;height:22px;" class="text-danger"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 text-danger">{{ __('Close my account') }}</h6>
                            <p class="text-muted small mb-0">
                                {{ __('Your account will be deactivated and you will be signed out. Your data is not deleted — it is kept safe and can be restored later by contacting support.') }}
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-warning border small d-flex gap-2 my-3" role="alert">
                        <i data-feather="alert-triangle" style="width:16px;height:16px;" class="mt-1 flex-shrink-0"></i>
                        <span>{{ __('Recommended: download a copy of your data first, before closing.') }}</span>
                    </div>

                    <button type="button" class="btn btn-outline-danger rounded-3 px-4 d-inline-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#closeAccountModal">
                        <i data-feather="power" style="width:18px;height:18px;"></i>
                        {{ __('Close my account') }}
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- Close-account confirmation modal --}}
    <div class="modal fade" id="closeAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="POST" action="{{ route('company.account.close') }}">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger">{{ __('Close my account') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            {{ __('To confirm, enter your account password. Your data will be preserved and can be restored later.') }}
                        </p>

                        @error('password')
                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
                        @enderror

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Password') }}</label>
                            <input type="password" name="password" class="form-control rounded-3" required autocomplete="current-password">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-semibold">{{ __('Reason (optional)') }}</label>
                            <textarea name="reason" rows="2" maxlength="500" class="form-control rounded-3"
                                      placeholder="{{ __('Tell us why you are leaving (optional)') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger rounded-3">{{ __('Close my account') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @error('password')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('closeAccountModal');
                if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
            });
        </script>
    @enderror

</div>
@endsection
