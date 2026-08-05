@php
    $steps = $bkOnboarding['steps'] ?? [];
    $helpSteps = [
        ['key' => 'service',       'icon' => 'scissors', 'title' => __('Add your first service'),        'text' => __('Set the price and duration for what you offer.'),      'route' => 'company.branches.index'],
        ['key' => 'employee',      'icon' => 'users',    'title' => __('Add your team'),                  'text' => __('Link staff to services so clients can pick them.'),     'route' => 'company.branches.index'],
        ['key' => 'working_hours', 'icon' => 'clock',    'title' => __('Set working hours'),              'text' => __('Tell us when each branch is open for bookings.'),       'route' => 'company.branches.index'],
        ['key' => 'appointment',   'icon' => 'calendar', 'title' => __('Create your first appointment'),  'text' => __('Everything ready — book a client from anywhere.'),      'route' => 'company.appointments.create'],
    ];
    $doneCount = count(array_filter($steps));
    $totalCount = count($helpSteps);
    $pct = $bkOnboarding['percent'] ?? 0;
@endphp

<div class="modal fade" id="bkHelpModal" tabindex="-1" aria-labelledby="bkHelpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;overflow:hidden;">
      <div class="modal-header" style="background:var(--bk-accent-wash);border-bottom:1px solid var(--bk-border);">
        <div>
          <h5 class="modal-title fw-bold" id="bkHelpModalLabel">
            <i data-feather="help-circle" style="width:18px;height:18px;vertical-align:-3px;color:var(--bk-accent);"></i>
            {{ __('Getting started') }}
          </h5>
          <div class="tx-12 text-muted mt-1">{{ __('Set up your business in a few steps. Come back anytime.') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>

      <div class="modal-body">
        {{-- Progress --}}
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="tx-12 fw-semibold text-muted">{{ __('Setup progress') }}</span>
          <span class="tx-12 fw-bold" style="color:var(--bk-accent);">{{ $doneCount }}/{{ $totalCount }} · {{ $pct }}%</span>
        </div>
        <div class="progress mb-4" style="height:7px;background:var(--bk-border);border-radius:6px;">
          <div class="progress-bar" role="progressbar" style="width:{{ $pct }}%;background:var(--bk-accent);"
               aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        {{-- Steps checklist --}}
        <div class="d-flex flex-column gap-2">
          @foreach($helpSteps as $s)
            @php $done = $steps[$s['key']] ?? false; @endphp
            <a href="{{ route($s['route']) }}"
               class="d-flex align-items-center gap-3 p-2 rounded-3 text-decoration-none bk-help-step"
               style="border:1px solid var(--bk-border);{{ $done ? 'opacity:.7;' : '' }}">
              <span class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-circle"
                    style="width:34px;height:34px;background:{{ $done ? 'var(--bk-success-bg)' : 'var(--bk-accent-wash)' }};color:{{ $done ? 'var(--bk-success)' : 'var(--bk-accent)' }};">
                <i data-feather="{{ $done ? 'check' : $s['icon'] }}" style="width:16px;height:16px;"></i>
              </span>
              <span class="flex-grow-1" style="min-width:0;">
                <span class="d-block tx-13 fw-semibold" style="color:var(--bk-text);{{ $done ? 'text-decoration:line-through;' : '' }}">{{ $s['title'] }}</span>
                <span class="d-block tx-11 text-muted">{{ $s['text'] }}</span>
              </span>
              <i data-feather="chevron-{{ $isAr ?? false ? 'left' : 'right' }}" style="width:15px;height:15px;color:var(--bk-text-muted);"></i>
            </a>
          @endforeach
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between" style="border-top:1px solid var(--bk-border);">
        <a href="{{ route('front.help') }}" target="_blank" class="btn btn-link btn-sm text-decoration-none px-0" style="color:var(--bk-text-muted);">
          <i data-feather="book-open" style="width:14px;height:14px;vertical-align:-2px;"></i>
          {{ __('Help center & FAQ') }}
        </a>
        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="bkReplayTour" data-bs-dismiss="modal">
          <i data-feather="play" style="width:14px;height:14px;vertical-align:-2px;"></i>
          {{ __('Replay guided tour') }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>
    document.getElementById('bkReplayTour')?.addEventListener('click', function () {
        if (typeof window.bkStartTour === 'function') {
            setTimeout(window.bkStartTour, 300); // let the modal close first
        }
    });
    if (window.feather) feather.replace();
</script>
