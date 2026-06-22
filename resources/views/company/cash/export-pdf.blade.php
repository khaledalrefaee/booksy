@php
    $isAr = app()->getLocale() === 'ar';
    $ar = $isAr ? new \ArPHP\I18N\Arabic() : null;
    $pdfText = function($text) use ($ar) {
        if (!$ar || !$text) return $text;
        return $ar->utf8Glyphs($text);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Cash Report') }} — {{ $branch->localizedName() }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size:11px; color:#1a1a2e; padding:20px; direction:{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}; }

        /* Header */
        .header-table { width:100%; border-bottom:3px solid #C9A227; padding-bottom:12px; margin-bottom:16px; }
        .header-table td { vertical-align:top; }
        .header-title { font-size:18px; font-weight:800; }
        .header-sub { font-size:12px; font-weight:600; margin-top:4px; }
        .header-meta { text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; font-size:10px; color:#64748b; line-height:1.6; }

        /* Summary */
        .summary-table { width:100%; margin-bottom:16px; border-spacing:8px; border-collapse:separate; }
        .summary-cell { padding:12px; border:1px solid #e2e8f0; border-radius:6px; text-align:center; width:33.33%; }
        .summary-label { font-size:9px; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:.5px; }
        .summary-value { font-size:18px; font-weight:800; margin-top:4px; }
        .income { color:#22c55e; }
        .expense { color:#ef4444; }
        .net-pos { color:#667eea; }

        /* Data table */
        .data-table { width:100%; border-collapse:collapse; margin-top:10px; }
        .data-table th {
            background:#f8fafc; padding:6px 8px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
            color:#64748b; border-bottom:2px solid #e2e8f0;
        }
        .data-table td { padding:6px 8px; border-bottom:1px solid #f1f5f9; font-size:10px; }
        .data-table tr:nth-child(even) td { background:#fafbfc; }

        /* Footer */
        .footer { margin-top:20px; text-align:center; font-size:9px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:10px; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:60%;">
                <div class="header-title">{{ $pdfText(__('Cash Report')) }}</div>
                <div class="header-sub">{{ $pdfText($branch->localizedName() . ' — ' . $company->localizedName()) }}</div>
            </td>
            <td class="header-meta">
                {{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}<br>
                {{ $pdfText(__('Generated')) }}: {{ now()->format('Y-m-d H:i') }}<br>
                {{ $pdfText(__('Total transactions')) }}: {{ $transactions->count() }}
            </td>
        </tr>
    </table>

    {{-- Summary --}}
    @foreach($summary as $currency => $s)
    <table class="summary-table" cellpadding="0">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">{{ $pdfText(__('Income')) }} ({{ $currency }})</div>
                <div class="summary-value income">+{{ number_format($s['income'], 2) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">{{ $pdfText(__('Expenses')) }} ({{ $currency }})</div>
                <div class="summary-value expense">-{{ number_format($s['expense'], 2) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">{{ $pdfText(__('Net')) }} ({{ $currency }})</div>
                <div class="summary-value net-pos">{{ $s['net'] >= 0 ? '+' : '' }}{{ number_format($s['net'], 2) }}</div>
            </td>
        </tr>
    </table>
    @endforeach

    {{-- Transactions table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $pdfText(__('Date')) }}</th>
                <th>{{ $pdfText(__('Category')) }}</th>
                <th>{{ $pdfText(__('Type')) }}</th>
                <th>{{ $pdfText(__('Payment method')) }}</th>
                <th>{{ $pdfText(__('Amount')) }}</th>
                <th>{{ $pdfText(__('Notes')) }}</th>
                <th>{{ $pdfText(__('Customer')) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $i => $tx)
            @php
                $catMeta = $cats[$tx->category] ?? ['label_key' => $tx->category, 'type' => 'income', 'color' => '#667eea'];
                $pmMeta  = \App\Models\BranchPayment::PAYMENT_METHODS[$tx->payment_method] ?? null;
                $isIncome = $catMeta['type'] === 'income';
            @endphp
            <tr>
                <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                <td>{{ $tx->paid_at->format('Y-m-d H:i') }}</td>
                <td style="color:{{ $catMeta['color'] }};font-weight:700;">{{ $pdfText(__($catMeta['label_key'])) }}</td>
                <td style="color:{{ $isIncome ? '#22c55e' : '#ef4444' }};font-weight:700;">
                    {{ $pdfText($isIncome ? __('Income') : __('Expense')) }}
                </td>
                <td>{{ $pdfText($pmMeta ? __($pmMeta['label_key']) : ($tx->payment_method ?? '-')) }}</td>
                <td style="font-weight:800;color:{{ $isIncome ? '#22c55e' : '#ef4444' }};">
                    {{ $isIncome ? '+' : '-' }}{{ number_format($tx->amount, 2) }} {{ $tx->currency }}
                </td>
                <td>{{ $pdfText(Str::limit($tx->notes ?? '-', 40)) }}</td>
                <td>{{ $pdfText($tx->appointment?->customer?->name ?? '-') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Booksy. — {{ $pdfText(__('Cash Report')) }} · {{ now()->format('Y') }}
    </div>
</body>
</html>
