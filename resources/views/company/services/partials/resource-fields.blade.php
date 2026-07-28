{{-- Required resources (rooms / equipment) — expects $branchResources, optional $linkedResourceIds, $inheritedResources --}}
@php($linkedResourceIds = $linkedResourceIds ?? [])
@php($inheritedResources = $inheritedResources ?? collect())
<h6 class="fw-semibold text-muted text-uppercase small mb-1">{{ __('Required resources') }}</h6>
<p class="form-text mt-0 mb-3">{{ __('The service can only be booked while one of the selected rooms/devices is free. Leave empty if the service needs none.') }}</p>

@if ($inheritedResources->isNotEmpty())
    <div class="alert alert-info py-2 px-3 tx-13 mb-3">
        <i data-feather="info" style="width:14px;height:14px;" class="me-1"></i>
        {{ __('Required via this service\'s category:') }}
        @foreach ($inheritedResources as $inherited)
            <span class="badge bg-info-subtle text-info">{{ $inherited->localizedName() }}</span>
        @endforeach
    </div>
@endif

@if ($branchResources->isEmpty())
    <p class="text-muted tx-12 mb-4">
        {{ __('No resources defined for this branch.') }}
        <a href="{{ route('company.resources.index') }}">{{ __('Add resources') }}</a>
    </p>
@else
    <div class="row g-2 mb-4">
        @foreach ($branchResources as $resource)
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="resource_ids[]"
                           id="resource{{ $resource->id }}" value="{{ $resource->id }}"
                           {{ in_array($resource->id, old('resource_ids', $linkedResourceIds)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="resource{{ $resource->id }}">
                        {{ $resource->localizedName() }}
                        <span class="text-muted tx-12">· {{ $resource->typeLabel() }}</span>
                        @unless ($resource->is_active)
                            <span class="badge bg-danger-subtle text-danger">{{ __('Inactive') }}</span>
                        @endunless
                    </label>
                </div>
            </div>
        @endforeach
    </div>
@endif
