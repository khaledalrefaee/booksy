@extends('company.dashboard')
@section('content')

@php
    $statusMeta = [
        'pending'  => ['label' => __('Pending'),  'cls' => 'sx-pill-skipped'],
        'approved' => ['label' => __('Approved'), 'cls' => 'sx-pill-sent'],
        'rejected' => ['label' => __('Rejected'), 'cls' => 'sx-pill-failed'],
    ];
@endphp

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('company.sms.overview') }}">{{ __('SMS') }}</a>
                <span aria-hidden="true">·</span> {{ __('Credits') }}
            </div>
            <h1 class="sx-title">{{ __('Purchase SMS') }}</h1>
            <p class="sx-subtitle">{{ __('Request an SMS package. The GlowRez team tops up your credits — you are not charged automatically here.') }}</p>
        </div>
    </header>

    @include('company.partials.flash')

    @if($packages->isEmpty())
        <div class="sx-card sx-reveal">
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="box"></i></span>
                <h3 class="sx-empty-title">{{ __('No packages available') }}</h3>
                <p class="sx-empty-text">{{ __('There are no SMS packages to request right now. Please check back later.') }}</p>
            </div>
        </div>
    @else
        <div class="sx-reveal" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-bottom:26px;">
            @foreach($packages as $p)
                <div class="sx-card" style="display:flex; flex-direction:column;">
                    <div class="sx-card-pad" style="flex:1;">
                        <span class="sx-chip">{{ $p->name }}</span>
                        <div style="margin-top:16px; font-family:var(--sx-display); font-size:2.6rem; font-weight:600; color:var(--bk-text); line-height:1; font-variant-numeric:tabular-nums;">
                            {{ number_format($p->credits) }}
                            <span style="font-family:inherit; font-size:.85rem; color:var(--bk-text-muted); font-weight:500;">{{ __('SMS') }}</span>
                        </div>
                        <div style="margin-top:14px; display:flex; align-items:baseline; gap:8px;">
                            <span style="font-size:1.4rem; font-weight:700; color:var(--bk-gold-strong);">{{ number_format($p->price, 2) }}</span>
                            <span class="sx-sub">{{ $p->currency }}</span>
                        </div>
                        @if($p->validity_days)
                            <div class="sx-legend" style="margin-top:10px;"><i data-feather="clock" style="width:13px;height:13px;"></i>{{ __(':n days validity', ['n' => $p->validity_days]) }}</div>
                        @endif
                    </div>
                    <div style="padding:0 22px 22px;">
                        <button type="button" class="sx-btn sx-btn-primary sx-btn-sm sx-req-btn" style="width:100%; justify-content:center;"
                                data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-credits="{{ $p->credits }}"
                                data-price="{{ number_format($p->price, 2) }}" data-currency="{{ $p->currency }}">
                            <i data-feather="shopping-bag"></i>{{ __('Request') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Request history --}}
    <div class="sx-card sx-reveal">
        <div class="sx-card-head"><h2 class="sx-card-title">{{ __('Your requests') }}</h2></div>
        @if($requests->isEmpty())
            <div class="sx-card-pad">
                <div class="sx-note sx-note-info"><i data-feather="info"></i><span>{{ __('You have not requested any SMS credits yet.') }}</span></div>
            </div>
        @else
            <div class="sx-table-scroll">
                <table class="sx-table" style="min-width:0;">
                    <thead><tr>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th class="num">{{ __('Credits') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Requested') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($requests as $r)
                        @php $sm = $statusMeta[$r->status] ?? ['label' => $r->status, 'cls' => 'sx-pill-queued']; @endphp
                        <tr>
                            <td><span class="sx-name">{{ $r->package?->name ?? '—' }}</span></td>
                            <td class="sx-sub">{{ $r->branch?->localizedName() ?? __('Company pool') }}</td>
                            <td class="num sx-mono">{{ number_format($r->credits) }}</td>
                            <td><span class="sx-pill {{ $sm['cls'] }}">{{ $sm['label'] }}</span></td>
                            <td class="sx-sub">{{ $r->created_at?->translatedFormat('d M Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Request modal --}}
<div class="modal fade" id="sxRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content sx" style="background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:20px;">
      <form method="POST" action="{{ route('company.sms.purchase.request') }}">
        @csrf
        <input type="hidden" name="package_id" id="sxReqPackage">
        <div class="modal-header" style="border-bottom:1px solid var(--bk-border); padding:18px 22px;">
          <div>
            <h5 class="modal-title sx-card-title" style="margin:0;">{{ __('Request SMS credits') }}</h5>
            <p class="sx-card-note" id="sxReqSummary"></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="modal-body" style="padding:22px;">
          <div class="sx-field">
            <label>{{ __('Assign to') }}</label>
            <select name="branch_id" class="sx-input">
              <option value="">{{ __('Company pool (all branches)') }}</option>
              @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->localizedName() }}</option>
              @endforeach
            </select>
          </div>
          <div class="sx-field" style="margin-bottom:0;">
            <label>{{ __('Note (optional)') }}</label>
            <input type="text" name="note" class="sx-input" maxlength="255" placeholder="{{ __('Anything the GlowRez team should know') }}">
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--bk-border); padding:16px 22px; gap:10px;">
          <button type="button" class="sx-btn sx-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="sx-btn sx-btn-primary"><i data-feather="send"></i>{{ __('Send request') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('company-styles')
    @include('company.sms.partials.styles')
@endpush

@push('scripts')
<script>
(function () {
    var smsLabel = @json(__('SMS'));
    document.querySelectorAll('.sx-req-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('sxReqPackage').value = btn.dataset.id;
            document.getElementById('sxReqSummary').textContent =
                btn.dataset.name + ' — ' + Number(btn.dataset.credits).toLocaleString() + ' ' + smsLabel +
                ' · ' + btn.dataset.price + ' ' + btn.dataset.currency;
            new bootstrap.Modal(document.getElementById('sxRequestModal')).show();
        });
    });
})();
</script>
@endpush

@endsection
