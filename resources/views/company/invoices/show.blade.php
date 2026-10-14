@extends('company.dashboard')

@push('company-styles')
<link rel="stylesheet" href="{{ asset('backend/assets/css/invoice-show.css') }}?v={{ @filemtime(public_path('backend/assets/css/invoice-show.css')) ?: '1' }}">
@endpush

@section('content')
@php
    $money     = fn ($n) => number_format((float) $n, 2);
    $total     = (float) $invoice->total;
    $paid      = (float) $invoice->amount_paid;
    $remaining = (float) $invoice->remaining;
    $status    = $invoice->status;
    $closed    = in_array($status, ['void', 'refunded'], true);
    $canPay    = $remaining > 0 && ! in_array($status, ['paid', 'void', 'refunded'], true);
    $pct       = $total > 0 ? (int) min(100, round($paid / $total * 100)) : 0;
    $cur       = $invoice->currency;
    $issued    = $invoice->issued_at ?? $invoice->created_at;

    $methodLabel = fn ($m) => match ($m) {
        'cash' => __('Cash'), 'card' => __('Card'), 'transfer' => __('Bank transfer'), 'mixed' => __('Mixed'),
        default => $m ? __(ucfirst($m)) : '—',
    };

    // What the person looking at this page needs to know first, and what to do about it.
    $banner = match (true) {
        $status === 'void'     => ['tone' => 'danger',  'icon' => 'slash',        'title' => __('This invoice is void'),       'sub' => __('It no longer counts toward your revenue.')],
        $status === 'refunded' => ['tone' => 'info',    'icon' => 'rotate-ccw',   'title' => __('This invoice was refunded'),  'sub' => null],
        $status === 'paid'     => ['tone' => 'success', 'icon' => 'check-circle', 'title' => __('Paid in full'),
                                   'sub' => collect([$invoice->paid_at?->translatedFormat('j F Y'), $invoice->payment_method ? $methodLabel($invoice->payment_method) : null])->filter()->implode(' · ') ?: null],
        $status === 'partial'  => ['tone' => 'warning', 'icon' => 'clock',        'title' => __('Partially paid'),
                                   'sub' => __(':amount :cur still to collect', ['amount' => $money($remaining), 'cur' => $cur])],
        $status === 'draft'    => ['tone' => 'muted',   'icon' => 'edit-3',       'title' => __('Draft — not issued yet'),     'sub' => null],
        default                => ['tone' => 'warning', 'icon' => 'alert-circle', 'title' => __('Waiting for payment'),
                                   'sub' => __(':amount :cur still to collect', ['amount' => $money($remaining), 'cur' => $cur])],
    };
@endphp

<div class="page-content inv-page">

    {{-- ── Header: where am I, what is this, what can I do ── --}}
    <header class="inv-head">
        <div class="inv-head-main">
            <nav aria-label="breadcrumb" class="inv-crumb">
                <a href="{{ route('company.invoices.index') }}">
                    <i data-feather="arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>{{ __('Invoices') }}
                </a>
            </nav>
            <div class="inv-title-row">
                <h1 class="inv-title">{{ $invoice->invoice_number }}</h1>
                <span class="bk-badge bk-inv-status-{{ $status }}">{{ __(ucfirst($status)) }}</span>
            </div>
            <div class="inv-sub">{{ $invoice->customer_name ?? '—' }} · {{ $issued->translatedFormat('j F Y') }}</div>
        </div>
        <div class="inv-head-actions">
            @if($invoice->appointment_id)
            <a href="{{ route('company.appointments.show', $invoice->appointment_id) }}" class="inv-btn">
                <i data-feather="calendar"></i>{{ __('Appointment') }} #{{ $invoice->appointment_id }}
            </a>
            @endif
            <a href="{{ route('company.invoices.print', $invoice) }}" target="_blank" rel="noopener" class="inv-btn inv-btn-solid">
                <i data-feather="printer"></i>{{ __('Print') }}
            </a>
        </div>
    </header>

    @include('company.partials.flash')

    {{-- ── State banner: the one-line answer, with the next step when there is one ── --}}
    <section class="inv-banner tone-{{ $banner['tone'] }}" role="status">
        <span class="inv-banner-ic"><i data-feather="{{ $banner['icon'] }}"></i></span>
        <div class="inv-banner-txt">
            <strong>{{ $banner['title'] }}</strong>
            @if($banner['sub'])<span>{{ $banner['sub'] }}</span>@endif
        </div>
        @if($canPay)
            <a href="#inv-amount" class="inv-btn inv-btn-solid inv-banner-cta" data-inv-focus>{{ __('Record payment') }}</a>
        @endif
    </section>

    <div class="inv-layout">

        {{-- ═══ Payment: the thing you do on this page ═══ --}}
        <section class="inv-card inv-pay" aria-labelledby="inv-pay-h">
            <h2 class="inv-card-h" id="inv-pay-h"><span>{{ __('Payment') }}</span><span class="inv-cur">{{ $cur }}</span></h2>

            <div class="inv-figures">
                <div><small>{{ __('Total') }}</small><b>{{ $money($total) }}</b></div>
                <div class="is-paid"><small>{{ __('Paid Amount') }}</small><b>{{ $money($paid) }}</b></div>
                <div class="is-due {{ $remaining > 0 && ! $closed ? 'has-due' : '' }}"><small>{{ __('Remaining') }}</small><b>{{ $money(max(0, $remaining)) }}</b></div>
            </div>
            <div class="inv-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="{{ __('Paid Amount') }}">
                <span style="width: {{ $pct }}%"></span>
            </div>

            @if($canPay)
            <form method="POST" action="{{ route('company.invoices.pay', $invoice) }}" class="inv-payform" id="inv-payform">
                @csrf
                <label class="inv-lbl" for="inv-amount">{{ __('Amount') }}</label>
                <div class="inv-amount">
                    <input type="number" step="0.01" min="0.01" max="{{ $remaining }}" inputmode="decimal"
                           name="amount" id="inv-amount" value="{{ number_format($remaining, 2, '.', '') }}" required>
                    <span>{{ $cur }}</span>
                </div>
                <p class="inv-hint">{{ __('The full remaining amount is filled in. Lower it to record a part payment.') }}</p>

                <span class="inv-lbl" id="inv-method-l">{{ __('How was it paid?') }}</span>
                <div class="inv-seg" role="radiogroup" aria-labelledby="inv-method-l">
                    @foreach(['cash' => ['dollar-sign', __('Cash')], 'card' => ['credit-card', __('Card')], 'transfer' => ['repeat', __('Bank transfer')]] as $val => [$ic, $lbl])
                    <label>
                        <input type="radio" name="payment_method" value="{{ $val }}" {{ $loop->first ? 'checked' : '' }} required>
                        <span><i data-feather="{{ $ic }}"></i>{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>

                <label class="inv-lbl" for="inv-note">{{ __('Notes') }} <em>{{ __('optional') }}</em></label>
                <input type="text" name="notes" id="inv-note" class="inv-input" maxlength="500">

                <button type="submit" class="inv-btn inv-btn-solid inv-submit" id="inv-submit">
                    <span class="inv-submit-t">{{ __('Record payment') }}</span>
                    <span class="inv-spin" aria-hidden="true"></span>
                </button>
            </form>
            @endif

            {{-- History --}}
            <div class="inv-hist">
                <h3 class="inv-hist-h">{{ __('Invoice Payments') }}</h3>
                @forelse($invoice->payments->sortByDesc('created_at') as $pmt)
                    <div class="inv-pm">
                        <span class="inv-pm-ic"><i data-feather="arrow-down-left"></i></span>
                        <div class="inv-pm-main">
                            <b>{{ $methodLabel($pmt->payment_method) }}</b>
                            <small>{{ $pmt->created_at->translatedFormat('j M · H:i') }}@if($pmt->recorded_by_name) · {{ $pmt->recorded_by_name }}@endif</small>
                            @if($pmt->notes)<small class="inv-pm-note">{{ $pmt->notes }}</small>@endif
                        </div>
                        <span class="inv-pm-amt">+{{ $money($pmt->amount) }}</span>
                    </div>
                @empty
                    <p class="inv-empty">{{ __('No payments yet.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- ═══ The invoice itself ═══ --}}
        <article class="inv-card inv-sheet" aria-label="{{ __('Invoice') }} {{ $invoice->invoice_number }}">
            <div class="inv-sheet-top">
                <div>
                    <small>{{ __('Billed to') }}</small>
                    <b>{{ $invoice->customer_name ?? '—' }}</b>
                    @if($invoice->customer_phone)<span class="inv-ltr">{{ $invoice->customer_phone }}</span>@endif
                </div>
                <div class="is-end">
                    <small>{{ __('Issued') }}</small>
                    <b>{{ $issued->translatedFormat('j F Y') }}</b>
                    @if($invoice->branch)<span>{{ $invoice->branch->localizedName() }}</span>@endif
                </div>
            </div>

            <div class="inv-items">
                @foreach($invoice->items as $item)
                <div class="inv-item">
                    <div class="inv-item-d">
                        <b>{{ $item->description }}</b>
                        <small>
                            {{ $item->employee_name ?? '' }}
                            @if($item->customer_name && $item->customer_name !== $invoice->customer_name)
                                {{ $item->employee_name ? ' · ' : '' }}{{ $item->customer_name }}
                            @endif
                        </small>
                    </div>
                    <span class="inv-item-a">{{ $money($item->total) }}</span>
                </div>
                @endforeach
            </div>

            <div class="inv-sum">
                <div class="inv-line"><span>{{ __('Subtotal') }}</span><span>{{ $money($invoice->subtotal) }}</span></div>
                @if($invoice->discount_amount > 0)
                <div class="inv-line is-disc"><span>{{ __('Discount') }}</span><span>− {{ $money($invoice->discount_amount) }}</span></div>
                @endif
                @if($invoice->vat_rate > 0)
                <div class="inv-line"><span>{{ __('VAT') }} ({{ rtrim(rtrim((string) $invoice->vat_rate, '0'), '.') }}%)</span><span>+ {{ $money($invoice->vat_amount) }}</span></div>
                @endif
                <div class="inv-grand"><span>{{ __('Total') }}</span><span>{{ $money($total) }} <small>{{ $cur }}</small></span></div>
            </div>

            @if($invoice->notes)
            <div class="inv-notes"><small>{{ __('Notes') }}</small><p>{{ $invoice->notes }}</p></div>
            @endif
        </article>

        {{-- ═══ Everything else, out of the way ═══ --}}
        <aside class="inv-side">
            <section class="inv-card">
                <h2 class="inv-card-h">{{ __('Details') }}</h2>
                <dl class="inv-dl">
                    <dt>{{ __('Branch') }}</dt><dd>{{ $invoice->branch?->localizedName() ?? '—' }}</dd>
                    <dt>{{ __('Currency') }}</dt><dd>{{ $cur }}</dd>
                    @if($invoice->payment_method)
                    <dt>{{ __('Payment') }}</dt><dd>{{ $methodLabel($invoice->payment_method) }}</dd>
                    @endif
                    <dt>{{ __('Created by') }}</dt><dd>{{ $invoice->created_by_name ?? '—' }}</dd>
                    <dt>{{ __('Created') }}</dt><dd>{{ $invoice->created_at->translatedFormat('j M Y · H:i') }}</dd>
                </dl>
            </section>

            @if($status !== 'void')
            <details class="inv-card inv-adv">
                <summary>
                    <span>{{ __('More actions') }}</span>
                    <i data-feather="chevron-down"></i>
                </summary>

                <form method="POST" action="{{ route('company.invoices.update-status', $invoice) }}" class="inv-adv-form">
                    @csrf @method('PATCH')
                    <p class="inv-warn">
                        <i data-feather="info"></i>
                        {{ __('Changing the status here only changes the label. To add money to this invoice, use "Record payment" above.') }}
                    </p>
                    <label class="inv-lbl" for="inv-st">{{ __('Status') }}</label>
                    <select name="status" id="inv-st" class="inv-input">
                        @foreach(['draft' => __('Draft'), 'issued' => __('Issued'), 'paid' => __('Paid'), 'partial' => __('Partial'), 'refunded' => __('Refunded')] as $val => $lbl)
                            <option value="{{ $val }}" {{ $status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <label class="inv-lbl" for="inv-pm">{{ __('Payment method') }}</label>
                    <select name="payment_method" id="inv-pm" class="inv-input">
                        <option value="">{{ __('Select…') }}</option>
                        @foreach(['cash' => __('Cash'), 'card' => __('Card'), 'transfer' => __('Transfer'), 'mixed' => __('Mixed')] as $val => $lbl)
                            <option value="{{ $val }}" {{ $invoice->payment_method === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <button class="inv-btn inv-btn-block">{{ __('Update') }}</button>
                </form>

                <div class="inv-danger">
                    <button type="button" class="inv-btn inv-btn-danger inv-btn-block" data-bs-toggle="modal" data-bs-target="#voidModal">
                        <i data-feather="slash"></i>{{ __('Void invoice') }}
                    </button>
                </div>
            </details>
            @endif
        </aside>
    </div>
</div>

{{-- Void Modal --}}
<div class="modal fade" id="voidModal" tabindex="-1" aria-labelledby="voidModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="voidModalTitle">{{ __('Void invoice') }}?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body text-muted tx-13 pt-2">
                {{ __('This will permanently mark the invoice as void. This action cannot be undone.') }}
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <form method="POST" action="{{ route('company.invoices.void', $invoice) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-danger rounded-pill px-4">{{ __('Void') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('company-after-template')
<script>
(function () {
    /* "Record payment" in the banner: jump to the form and put the cursor in the amount */
    document.querySelectorAll('[data-inv-focus]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var t = document.getElementById('inv-amount');
            if (!t) return;
            e.preventDefault();
            t.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
            setTimeout(function () { t.focus({ preventScroll: true }); t.select(); }, 250);
        });
    });
    /* one tap = one payment: lock the button while the request is in flight */
    var f = document.getElementById('inv-payform'), b = document.getElementById('inv-submit');
    if (f && b) f.addEventListener('submit', function () {
        if (!f.checkValidity()) return;
        b.disabled = true; b.classList.add('is-busy');
    });
    if (window.feather) window.feather.replace();
})();
</script>
@endpush
