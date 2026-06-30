@extends('company.dashboard')

@section('content')
<div class="page-content">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('company.debts.index') }}" class="btn btn-sm btn-outline-secondary">←</a>
        <h4 class="fw-bold mb-0">💳 {{ __('Debt') }} #{{ $debt->id }}</h4>
        <span class="badge bg-{{ ['unpaid'=>'danger','partial'=>'warning','paid'=>'success','waived'=>'secondary'][$debt->status] ?? 'secondary' }} ms-2">
            {{ __($debt->status) }}
        </span>
    </div>

    <div class="row g-4">
        {{-- Debt Info --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">{{ __('Details') }}</h6>
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Customer') }}</td>
                            <td class="fw-bold">
                                {{ $debt->customer?->name ?? '—' }}
                                <br><small class="text-muted">{{ $debt->customer?->phone }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Branch') }}</td>
                            <td>{{ $debt->branch?->localizedName() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Original Amount') }}</td>
                            <td class="fw-bold">{{ number_format($debt->original_amount, 0) }} {{ $debt->currency }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Paid Amount') }}</td>
                            <td class="text-success">{{ number_format($debt->paid_amount, 0) }} {{ $debt->currency }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Remaining') }}</td>
                            <td class="fw-bold text-danger fs-5">{{ number_format($debt->remaining, 0) }} {{ $debt->currency }}</td>
                        </tr>
                        @if($debt->due_date)
                            <tr>
                                <td class="text-muted">{{ __('Due Date') }}</td>
                                <td>{{ $debt->due_date->format('d/m/Y') }}</td>
                            </tr>
                        @endif
                        @if($debt->appointment)
                            <tr>
                                <td class="text-muted">{{ __('Appointment') }}</td>
                                <td><a href="{{ route('company.appointments.show', $debt->appointment) }}">#{{ $debt->appointment_id }}</a></td>
                            </tr>
                        @endif
                        @if($debt->notes)
                            <tr>
                                <td class="text-muted">{{ __('Notes') }}</td>
                                <td>{{ $debt->notes }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">{{ __('Created') }}</td>
                            <td class="small">{{ $debt->created_at->format('d/m/Y H:i') }} — {{ $debt->created_by_name }}</td>
                        </tr>
                    </table>

                    @if(!$debt->isPaid() && $debt->status !== 'waived')
                        <div class="mt-3">
                            <form method="POST" action="{{ route('company.debts.waive', $debt) }}"
                                onsubmit="return confirm('{{ __('Waive this debt?') }}')" class="d-inline">
                                @csrf @method('PUT')
                                <button class="btn btn-sm btn-outline-secondary">{{ __('Waive Debt') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Form + History --}}
        <div class="col-md-6">
            @if(!$debt->isPaid() && $debt->status !== 'waived')
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">💵 {{ __('Record Payment') }}</h6>
                        <form method="POST" action="{{ route('company.debts.pay', $debt) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">{{ __('Payment Amount') }} *</label>
                                <input type="number" step="0.01" name="amount" class="form-control"
                                    max="{{ $debt->remaining }}" value="{{ $debt->remaining }}" required min="0.01">
                                <small class="text-muted">{{ __('Remaining balance') }}: {{ number_format($debt->remaining, 0) }} {{ $debt->currency }}</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">{{ __('Payment Method') }} *</label>
                                <select name="payment_method" class="form-select form-select-sm" required>
                                    <option value="cash">💵 {{ __('Cash') }}</option>
                                    <option value="card">💳 {{ __('Card') }}</option>
                                    <option value="bank_transfer">🏦 {{ __('Bank transfer') }}</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">{{ __('Notes') }}</label>
                                <input type="text" name="notes" class="form-control form-control-sm">
                            </div>
                            <button class="btn btn-success btn-sm w-100">💰 {{ __('Record Payment') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📋 {{ __('Payment History') }}</h6>
                    @if($debt->payments->isEmpty())
                        <p class="text-muted text-center py-3">{{ __('No payments yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Payment Method') }}</th>
                                        <th>{{ __('By') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debt->payments->sortByDesc('created_at') as $payment)
                                        <tr>
                                            <td class="small">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="fw-bold text-success">+{{ number_format($payment->amount, 0) }}</td>
                                            <td class="small">{{ __($payment->payment_method) }}</td>
                                            <td class="small">{{ $payment->recorded_by_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
