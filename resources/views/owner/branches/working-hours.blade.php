@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Working hours') }} — {{ $branch->localizedName() }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                <li class="breadcrumb-item active">{{ $branch->localizedName() }}</li>
            </ol>
        </nav>
        <p class="text-muted small mt-2 mb-0">
            {{ __('Company') }}: <strong>{{ $branch->company?->localizedName() }}</strong>
        </p>
    </div>

    @include('owner.partials.flash')

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    @include('owner.branches.partials.wizard-steps', ['currentStep' => 2])

                    <form method="post" action="{{ route('owner.branches.working-hours.store', $branch) }}" id="branch-hours-form">
                        @csrf
                        <p class="text-muted mb-3">{{ __('Set opening hours for each day. Uncheck a day if the branch is closed.') }}</p>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Day') }}</th>
                                        <th class="text-center">{{ __('Open') }}</th>
                                        <th>{{ __('Opens at') }}</th>
                                        <th>{{ __('Closes at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($weekDays as $dayNum => $dayLabel)
                                        @php
                                            $saved = $existingHours[$dayNum] ?? null;
                                            $oldHour = old('hours.'.$dayNum, []);
                                            $isOpen = isset($oldHour['is_open'])
                                                ? (bool) $oldHour['is_open']
                                                : ($saved ? $saved->is_open : ($dayNum >= 1 && $dayNum <= 5));
                                            $openTime = $oldHour['open_time'] ?? ($saved && $saved->open_time ? \Illuminate\Support\Str::of($saved->open_time)->substr(0, 5) : '09:00');
                                            $closeTime = $oldHour['close_time'] ?? ($saved && $saved->close_time ? \Illuminate\Support\Str::of($saved->close_time)->substr(0, 5) : '18:00');
                                        @endphp
                                        <tr>
                                            <td class="fw-medium">
                                                {{ $dayLabel }}
                                                <input type="hidden" name="hours[{{ $dayNum }}][day_of_week]" value="{{ $dayNum }}">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input js-day-open"
                                                    name="hours[{{ $dayNum }}][is_open]" value="1"
                                                    data-day="{{ $dayNum }}"
                                                    @checked($isOpen)>
                                            </td>
                                            <td>
                                                <input type="time" name="hours[{{ $dayNum }}][open_time]"
                                                    class="form-control form-control-sm rounded-3 js-day-open-time"
                                                    data-day="{{ $dayNum }}"
                                                    value="{{ $openTime }}"
                                                    @disabled(! $isOpen)>
                                            </td>
                                            <td>
                                                <input type="time" name="hours[{{ $dayNum }}][close_time]"
                                                    class="form-control form-control-sm rounded-3 js-day-close-time"
                                                    data-day="{{ $dayNum }}"
                                                    value="{{ $closeTime }}"
                                                    @disabled(! $isOpen)>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('hours')
                            <div class="text-danger small mb-3">{{ $message }}</div>
                        @enderror

                    </form>

                    <div class="d-flex justify-content-between gap-2 mt-4 pt-3 border-top flex-wrap">
                        <form method="post" action="{{ route('owner.branches.working-hours.skip', $branch) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-light rounded-pill px-4">{{ __('Skip for now') }}</button>
                        </form>
                        <button type="submit" form="branch-hours-form" class="btn btn-primary rounded-pill px-4">
                            <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                            {{ __('Save & finish') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }

    document.querySelectorAll('.js-day-open').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var day = checkbox.getAttribute('data-day');
            var openInput = document.querySelector('.js-day-open-time[data-day="' + day + '"]');
            var closeInput = document.querySelector('.js-day-close-time[data-day="' + day + '"]');
            var enabled = checkbox.checked;
            if (openInput) {
                openInput.disabled = !enabled;
            }
            if (closeInput) {
                closeInput.disabled = !enabled;
            }
        });
    });

    var hoursForm = document.getElementById('branch-hours-form');
    if (hoursForm) {
        hoursForm.addEventListener('submit', function () {
            hoursForm.querySelectorAll('.js-day-open-time, .js-day-close-time').forEach(function (el) {
                el.disabled = false;
            });
        });
    }
});
</script>
@endpush
@endsection
