<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $invoice->invoice_number }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 30px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #111; padding-bottom: 16px; }
  .company-name { font-size: 22px; font-weight: 700; }
  .invoice-meta { text-align: end; }
  .invoice-meta h2 { font-size: 18px; font-weight: 700; color: #333; }
  .section { margin-bottom: 20px; }
  .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: .8px; color: #666; margin-bottom: 6px; font-weight: 700; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #f4f4f4; padding: 8px 10px; text-align: start; font-size: 11px; text-transform: uppercase; color: #555; }
  td { padding: 8px 10px; border-bottom: 1px solid #eee; }
  .text-end { text-align: end; }
  tfoot td { font-weight: 600; border-top: 2px solid #111; }
  .total-row { font-size: 15px; font-weight: 700; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .badge-paid { background: #d1fae5; color: #065f46; }
  .badge-issued { background: #dbeafe; color: #1e40af; }
  .badge-draft { background: #f3f4f6; color: #374151; }
  .badge-void { background: #fee2e2; color: #991b1b; }
  .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; }
  @media print { body { padding: 0; } }
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="company-name">{{ $invoice->company->localizedName() }}</div>
        <div style="color:#666;font-size:12px;margin-top:4px;">{{ $invoice->branch?->localizedName() }}</div>
    </div>
    <div class="invoice-meta">
        <h2>{{ __('Invoice') }}</h2>
        <div style="margin-top:6px;color:#333;">{{ $invoice->invoice_number }}</div>
        <div style="font-size:11px;color:#666;margin-top:2px;">{{ $invoice->issued_at?->format('d M Y') ?? $invoice->created_at->format('d M Y') }}</div>
        <div style="margin-top:6px;">
            <span class="badge badge-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
        </div>
    </div>
</div>

<div class="section">
    <div class="section-title">{{ __('Bill To') }}</div>
    <strong>{{ $invoice->customer_name ?? '—' }}</strong>
    @if($invoice->customer_phone)
        <div style="color:#555;font-size:12px;">{{ $invoice->customer_phone }}</div>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>{{ __('Description') }}</th>
            <th>{{ __('Client') }}</th>
            <th>{{ __('Staff') }}</th>
            <th class="text-end">{{ __('Amount') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td style="color:#666;font-size:12px;">{{ $item->customer_name ?? '—' }}</td>
            <td style="color:#666;font-size:12px;">{{ $item->employee_name ?? '—' }}</td>
            <td class="text-end">{{ number_format((float)$item->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        @if($invoice->discount_amount > 0)
        <tr>
            <td colspan="3" class="text-end" style="color:#666;">{{ __('Discount') }}</td>
            <td class="text-end" style="color:#dc2626;">- {{ number_format((float)$invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        @if($invoice->vat_rate > 0)
        <tr>
            <td colspan="3" class="text-end" style="color:#666;">{{ __('VAT') }} ({{ $invoice->vat_rate }}%)</td>
            <td class="text-end">+ {{ number_format((float)$invoice->vat_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td colspan="3" class="text-end">{{ __('Total') }}</td>
            <td class="text-end">{{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </tfoot>
</table>

@if($invoice->notes)
<div class="section" style="margin-top:20px;">
    <div class="section-title">{{ __('Notes') }}</div>
    <p>{{ $invoice->notes }}</p>
</div>
@endif

<div class="footer">{{ __('Thank you for your visit!') }} — {{ config('app.name') }}</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
