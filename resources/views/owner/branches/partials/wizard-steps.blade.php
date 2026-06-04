@php
    $current = $currentStep ?? 1;
@endphp
<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <div class="d-flex align-items-center gap-2 {{ $current === 1 ? 'text-primary fw-semibold' : 'text-muted' }}">
        <span class="badge rounded-pill {{ $current === 1 ? 'bg-primary' : 'bg-light text-muted border' }}">1</span>
        <span>{{ __('Branch details') }}</span>
    </div>
    <i data-feather="chevron-right" class="text-muted" style="width:16px;height:16px;"></i>
    <div class="d-flex align-items-center gap-2 {{ $current === 2 ? 'text-primary fw-semibold' : 'text-muted' }}">
        <span class="badge rounded-pill {{ $current === 2 ? 'bg-primary' : 'bg-light text-muted border' }}">2</span>
        <span>{{ __('Working hours') }}</span>
    </div>
    <i data-feather="chevron-right" class="text-muted" style="width:16px;height:16px;"></i>
    <div class="d-flex align-items-center gap-2 {{ $current === 3 ? 'text-primary fw-semibold' : 'text-muted' }}">
        <span class="badge rounded-pill {{ $current === 3 ? 'bg-primary' : 'bg-light text-muted border' }}">3</span>
        <span>{{ __('Employees') }}</span>
    </div>
</div>
