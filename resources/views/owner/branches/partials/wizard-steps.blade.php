@php
    $current = $currentStep ?? 1;
    $steps = [
        1 => ['label' => __('Branch details'),  'icon' => 'map-pin'],
        2 => ['label' => __('Working hours'),   'icon' => 'clock'],
        3 => ['label' => __('Employees'),       'icon' => 'users'],
    ];
@endphp
<div class="bm-wizard" aria-label="{{ __('Setup progress') }}">
    @foreach($steps as $n => $step)
        @php $state = $n === $current ? 'is-active' : ($n < $current ? 'is-done' : ''); @endphp
        <div class="bm-step {{ $state }}">
            <span class="bm-step-num">
                @if($n < $current)<i data-feather="check" style="width:14px;height:14px;"></i>@else{{ $n }}@endif
            </span>
            <span>{{ $step['label'] }}</span>
        </div>
        @if(!$loop->last)
            <i data-feather="chevron-right" class="bm-wizard-sep"></i>
        @endif
    @endforeach
</div>
