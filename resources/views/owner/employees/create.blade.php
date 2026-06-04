@extends('owner.dashboard')
@section('content')
<div class="page-content">
    <div class="mb-4">
        <h4 class="mb-2">{{ __('Add employees') }} — {{ $branch->localizedName() }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('owner.branches.index') }}">{{ __('Branches') }}</a></li>
                @unless ($wizard)
                    <li class="breadcrumb-item"><a href="{{ route('owner.branches.employees.index', $branch) }}">{{ $branch->localizedName() }}</a></li>
                @endunless
                <li class="breadcrumb-item active">{{ __('Create') }}</li>
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
                    @if ($wizard)
                        @include('owner.branches.partials.wizard-steps', ['currentStep' => 3])
                    @endif

                    <p class="text-muted mb-4">{{ __('Add one or more employees. Use the button below to add another form.') }}</p>

                    @error('employees')
                        <div class="alert alert-danger rounded-3">{{ $message }}</div>
                    @enderror

                    <form method="post" action="{{ route('owner.branches.employees.store', $branch) }}" id="employees-form">
                        @csrf
                        @if ($wizard)
                            <input type="hidden" name="wizard" value="1">
                        @endif

                        <div id="employee-forms">
                            @php
                                $rows = old('employees', [[]]);
                            @endphp
                            @foreach ($rows as $index => $row)
                                @include('owner.employees.partials.form-block', [
                                    'index' => $index,
                                    'row' => is_array($row) ? $row : [],
                                    'roles' => $roles,
                                ])
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-outline-primary rounded-pill w-100 mb-4" id="add-employee-btn">
                            <i data-feather="plus" style="width:16px;height:16px;"></i>
                            {{ __('Add another employee') }}
                        </button>
                    </form>

                    <div class="d-flex justify-content-between gap-2 pt-3 border-top flex-wrap">
                        @if ($wizard)
                            <form method="post" action="{{ route('owner.branches.employees.skip', $branch) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-light rounded-pill px-4">{{ __('Skip for now') }}</button>
                            </form>
                            <button type="submit" form="employees-form" class="btn btn-primary rounded-pill px-4">
                                <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                                {{ __('Save & finish') }}
                            </button>
                        @else
                            <a href="{{ route('owner.branches.employees.index', $branch) }}" class="btn btn-light rounded-pill px-4">{{ __('Cancel') }}</a>
                            <button type="submit" form="employees-form" class="btn btn-primary rounded-pill px-4">{{ __('Save all') }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="employee-block-template">
    @include('owner.employees.partials.form-block', [
        'index' => '__INDEX__',
        'row' => [],
        'roles' => $roles,
    ])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.feather !== 'undefined') {
        window.feather.replace();
    }

    var container = document.getElementById('employee-forms');
    var template = document.getElementById('employee-block-template');
    var addBtn = document.getElementById('add-employee-btn');
    var form = document.getElementById('employees-form');

    if (!container || !template || !addBtn) {
        return;
    }

    function nextIndex() {
        var blocks = container.querySelectorAll('.employee-block');
        var max = -1;
        blocks.forEach(function (block) {
            var idx = parseInt(block.getAttribute('data-index'), 10);
            if (!isNaN(idx) && idx > max) {
                max = idx;
            }
        });
        return max + 1;
    }

    function reindexBlocks() {
        var blocks = container.querySelectorAll('.employee-block');
        blocks.forEach(function (block, i) {
            block.setAttribute('data-index', i);
            var badge = block.querySelector('.js-employee-number');
            if (badge) {
                badge.textContent = String(i + 1);
            }
            block.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/employees\[\d+\]/, 'employees[' + i + ']');
            });
            block.querySelectorAll('[id]').forEach(function (el) {
                if (el.id && el.id.indexOf('employee-active-') === 0) {
                    el.id = 'employee-active-' + i;
                }
            });
            block.querySelectorAll('label[for]').forEach(function (label) {
                if (label.getAttribute('for') && label.getAttribute('for').indexOf('employee-active-') === 0) {
                    label.setAttribute('for', 'employee-active-' + i);
                }
            });
            var removeBtn = block.querySelector('.js-remove-employee');
            if (removeBtn) {
                removeBtn.hidden = blocks.length <= 1;
            }
        });
        if (typeof window.feather !== 'undefined') {
            window.feather.replace();
        }
    }

    addBtn.addEventListener('click', function () {
        var index = nextIndex();
        var html = template.innerHTML.replace(/__INDEX__/g, String(index));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var block = wrapper.firstElementChild;
        container.appendChild(block);
        reindexBlocks();
    });

    container.addEventListener('click', function (event) {
        var btn = event.target.closest('.js-remove-employee');
        if (!btn) {
            return;
        }
        var block = btn.closest('.employee-block');
        if (block && container.querySelectorAll('.employee-block').length > 1) {
            block.remove();
            reindexBlocks();
        }
    });

    if (form) {
        form.addEventListener('submit', function () {
            reindexBlocks();
        });
    }

    reindexBlocks();
});
</script>
@endpush
@endsection
