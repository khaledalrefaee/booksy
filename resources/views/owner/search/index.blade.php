@extends('owner.dashboard')

@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">🔎 {{ __('Search results') }}</h4>
            @if($q !== '')
                <p class="text-muted mb-0">
                    {{ __('Search results for') }} "<strong>{{ $q }}</strong>" —
                    {{ $totalCount }} {{ __('results') }}
                </p>
            @endif
        </div>
        <form method="GET" action="{{ route('owner.search.index') }}" class="d-flex" style="min-width:280px;">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i data-feather="search" style="width:14px;height:14px;color:var(--bk-accent);"></i>
                </span>
                <input type="text" name="q" value="{{ $q }}" autofocus
                    class="form-control border-start-0" placeholder="{{ __('Search') }}…">
            </div>
        </form>
    </div>

    @if($q === '')
        <div class="text-center py-5" style="opacity:.5;">
            <i data-feather="search" style="width:40px;height:40px;"></i>
            <p class="mt-3">{{ __('Type something to search across companies, branches, employees, services and appointments.') }}</p>
        </div>
    @elseif($totalCount === 0)
        <div class="text-center py-5" style="opacity:.5;">
            <i data-feather="frown" style="width:40px;height:40px;"></i>
            <p class="mt-3">{{ __('No results found') }}</p>
        </div>
    @else
        @php
            $sections = [
                'companies'    => ['icon' => 'briefcase',  'label' => __('Companies')],
                'branches'     => ['icon' => 'map-pin',    'label' => __('Branches')],
                'employees'    => ['icon' => 'user-check', 'label' => __('Employees')],
                'services'     => ['icon' => 'scissors',   'label' => __('Services')],
                'appointments' => ['icon' => 'calendar',   'label' => __('Appointments')],
            ];
        @endphp

        <div class="row g-4">
            @foreach($sections as $key => $meta)
                @continue($results[$key]->isEmpty())
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-transparent d-flex align-items-center gap-2 border-0 pt-3">
                            <i data-feather="{{ $meta['icon'] }}" style="width:16px;height:16px;color:var(--bk-accent);"></i>
                            <span class="fw-bold">{{ $meta['label'] }}</span>
                            <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $results[$key]->count() }}</span>
                        </div>
                        <div class="list-group list-group-flush">
                            @if($key === 'companies')
                                @foreach($results[$key] as $item)
                                    <a href="{{ route('owner.companies.show', $item) }}" class="list-group-item list-group-item-action">
                                        <div class="fw-semibold">{{ $item->localizedName() }}</div>
                                        <div class="small text-muted">{{ $item->email }}</div>
                                    </a>
                                @endforeach
                            @elseif($key === 'branches')
                                @foreach($results[$key] as $item)
                                    <a href="{{ route('owner.branches.edit', $item) }}" class="list-group-item list-group-item-action">
                                        <div class="fw-semibold">{{ $item->localizedName() }}</div>
                                        <div class="small text-muted">{{ $item->company?->localizedName() }}</div>
                                    </a>
                                @endforeach
                            @elseif($key === 'employees')
                                @foreach($results[$key] as $item)
                                    <a href="{{ route('owner.employees.edit', $item) }}" class="list-group-item list-group-item-action">
                                        <div class="fw-semibold">{{ $item->localizedName() }}</div>
                                        <div class="small text-muted">{{ $item->branch?->localizedName() }}</div>
                                    </a>
                                @endforeach
                            @elseif($key === 'services')
                                @foreach($results[$key] as $item)
                                    <a href="{{ route('owner.services.edit', $item) }}" class="list-group-item list-group-item-action">
                                        <div class="fw-semibold">{{ $item->localizedName() }}</div>
                                        <div class="small text-muted">{{ $item->branch?->localizedName() }}</div>
                                    </a>
                                @endforeach
                            @elseif($key === 'appointments')
                                @foreach($results[$key] as $item)
                                    <a href="{{ route('owner.appointments.show', $item) }}" class="list-group-item list-group-item-action">
                                        <div class="fw-semibold">{{ $item->customer_name }}</div>
                                        <div class="small text-muted">
                                            {{ $item->company?->localizedName() }} · {{ $item->branch?->localizedName() }} ·
                                            {{ optional($item->start_time)->format('Y-m-d H:i') }}
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
