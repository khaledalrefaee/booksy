@extends('owner.dashboard')
@section('content')

<div class="cm-page">

    {{-- Breadcrumb --}}
    <nav class="cm-breadcrumb cm-reveal" aria-label="breadcrumb">
        <a href="{{ route('owner.companies.index') }}">{{ __('Companies') }}</a>
        <i data-feather="chevron-left"></i>
        <span>{{ __('Import from data file') }}</span>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="cm-card cm-reveal" style="padding:1.75rem;">

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div style="width:48px;height:48px;border-radius:14px;background:var(--bk-gold-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-feather="upload-cloud" style="color:var(--bk-gold-strong);"></i>
                    </div>
                    <div>
                        <h5 class="mb-1" style="font-weight:700;">{{ __('Import a company from a data file') }}</h5>
                        <p class="mb-0" style="color:var(--bk-muted);font-size:.9rem;">
                            {{ __('Upload the company-data.json file the customer sent you. A new company account will be created and filled with its data.') }}
                        </p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-3 small">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="alert alert-light border rounded-3 small d-flex gap-2 mb-4">
                    <i data-feather="info" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
                    <div>
                        <div class="mb-1">{{ __('Good to know:') }}</div>
                        <ul class="mb-0 ps-3">
                            <li>{{ __('Use the company-data.json file (not the Excel file) from the downloaded ZIP.') }}</li>
                            <li>{{ __('A temporary password is set for the new company and its staff — reset it afterwards.') }}</li>
                            <li>{{ __('Branch, service, employee and appointment links are rebuilt automatically.') }}</li>
                        </ul>
                    </div>
                </div>

                <form action="{{ route('owner.companies.import-data') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label fw-semibold" for="import-json-file">
                        {{ __('JSON data file') }} <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="file" id="import-json-file" required accept=".json,application/json,.txt"
                           class="form-control form-control-lg">

                    <div class="cm-modal-foot" style="margin-top:1.25rem;display:flex;gap:.5rem;justify-content:flex-end;">
                        <a href="{{ route('owner.companies.index') }}" class="cm-btn cm-btn-ghost">{{ __('Cancel') }}</a>
                        <button type="submit" class="cm-btn cm-btn-primary">
                            <i data-feather="upload"></i> {{ __('Import company') }}
                        </button>
                    </div>
                </form>

            </div>

        </div>
    </div>

</div>
@endsection
