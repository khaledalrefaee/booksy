@extends('owner.dashboard')
@section('content')

<div class="page-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-3 mb-md-0">{{ __('Subscription plans') }}</h4>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Subscription plans') }}</li>
                </ol>
            </nav>
        </div>
        <button type="button" class="btn btn-primary btn-icon-text" data-bs-toggle="modal" data-bs-target="#modal-plan-create">
            <i class="btn-icon-prepend" data-feather="plus"></i>
            {{ __('Add plan') }}
        </button>
    </div>

    @include('owner.partials.flash')

    <div class="row">
        <div class="col-md-12 stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="pt-0">#</th>
                                    <th class="pt-0">{{ __('Name') }}</th>
                                    <th class="pt-0">{{ __('Price') }}</th>
                                    <th class="pt-0">{{ __('Duration (days)') }}</th>
                                    <th class="pt-0">{{ __('Branches limit') }}</th>
                                    <th class="pt-0">{{ __('Employees limit') }}</th>
                                    <th class="pt-0">{{ __('Features') }}</th>
                                    <th class="pt-0">{{ __('Companies') }}</th>
                                    <th class="pt-0">{{ __('Status') }}</th>
                                    <th class="pt-0 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $plan)
                                    @php
                                        $enabled = collect($featureCatalog)->filter(fn ($f, $k) => $plan->hasFeature($k));
                                    @endphp
                                    <tr>
                                        <td>{{ $plan->id }}</td>
                                        <td class="fw-semibold">{{ $plan->localizedName() }}</td>
                                        <td>
                                            @if ((float) $plan->price === 0.0)
                                                <span class="badge rounded-pill bg-success">{{ __('Free') }}</span>
                                            @else
                                                {{ number_format((float) $plan->price, 2) }} {{ $plan->currency }}
                                            @endif
                                        </td>
                                        <td>{{ $plan->duration_days }}</td>
                                        <td>{{ $plan->max_branches ?? __('Unlimited') }}</td>
                                        <td>{{ $plan->max_employees ?? __('Unlimited') }}</td>
                                        <td style="max-width:280px;">
                                            @if ($enabled->isEmpty())
                                                <span class="text-muted">{{ __('Core only (appointments & customers)') }}</span>
                                            @else
                                                @foreach ($enabled as $key => $f)
                                                    <span class="badge rounded-pill bg-secondary mb-1">{{ $f['label'] }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $plan->companies_count }}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $plan->is_active ? 'success' : 'secondary' }}">
                                                {{ $plan->is_active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" data-bs-target="#modal-plan-edit-{{ $plan->id }}">
                                                {{ __('Edit') }}
                                            </button>
                                            <form action="{{ route('owner.plans.destroy', $plan) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('Delete this plan?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        {{ $plan->companies_count > 0 ? 'disabled' : '' }}>
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">{{ __('No plans yet. Create your first plan.') }}</td>
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

{{-- ══ Create modal ══ --}}
@include('owner.plans._form-modal', [
    'modalId' => 'modal-plan-create',
    'title'   => __('Add plan'),
    'action'  => route('owner.plans.store'),
    'method'  => 'POST',
    'plan'    => null,
    'featureCatalog' => $featureCatalog,
])

{{-- ══ Edit modals ══ --}}
@foreach ($plans as $plan)
    @include('owner.plans._form-modal', [
        'modalId' => 'modal-plan-edit-'.$plan->id,
        'title'   => __('Edit plan').' — '.$plan->localizedName(),
        'action'  => route('owner.plans.update', $plan),
        'method'  => 'PUT',
        'plan'    => $plan,
        'featureCatalog' => $featureCatalog,
    ])
@endforeach

@endsection
