@php
    // One ledger row, shared by the Overview and the full Transactions page.
    $typeMeta = [
        'grant'      => ['label' => __('Grant'),      'icon' => 'gift',        'cls' => 'sx-type-confirmation'],
        'purchase'   => ['label' => __('Purchase'),   'icon' => 'shopping-bag','cls' => 'sx-type-reminder'],
        'consume'    => ['label' => __('Consume'),    'icon' => 'send',        'cls' => 'sx-type-followup'],
        'refund'     => ['label' => __('Refund'),     'icon' => 'corner-up-left','cls' => 'sx-type-confirmation'],
        'expire'     => ['label' => __('Expire'),     'icon' => 'clock',       'cls' => ''],
        'adjustment' => ['label' => __('Adjustment'), 'icon' => 'sliders',     'cls' => ''],
    ];
    $meta   = $typeMeta[$t->type] ?? ['label' => $t->type, 'icon' => 'circle', 'cls' => ''];
    $isPlus = $t->credits >= 0;
    $scope  = $t->wallet?->branch?->localizedName() ?? __('Company pool');
@endphp
<tr>
    <td>
        <span class="sx-type {{ $meta['cls'] }}"><i data-feather="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
        <div class="sx-sub">{{ $t->wallet?->company?->localizedName() ?? '—' }} · {{ $scope }}</div>
    </td>
    @unless($compact ?? false)
        <td class="sx-sub">{{ $t->created_at?->translatedFormat('d M Y · g:i A') }}</td>
    @endunless
    <td class="num">
        <strong class="sx-mono" style="color:{{ $isPlus ? 'var(--bk-success)' : 'var(--bk-text)' }}">
            {{ $isPlus ? '+' : '' }}{{ number_format($t->credits) }}
        </strong>
        <div class="sx-sub sx-mono">{{ __('bal') }} {{ number_format($t->balance_after) }}</div>
    </td>
</tr>
