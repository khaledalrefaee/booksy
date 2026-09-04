@extends('owner.dashboard')
@section('content')

<div class="page-content sx">

    <header class="sx-head sx-reveal">
        <div>
            <div class="sx-eyebrow">
                <a href="{{ route('owner.sms.overview') }}">{{ __('SMS credits') }}</a>
                <span aria-hidden="true">·</span> {{ __('Catalog') }}
            </div>
            <h1 class="sx-title">{{ __('SMS Packages') }}</h1>
            <p class="sx-subtitle">{{ __('Bundles companies can buy to top up credits. Set the size, price and optional validity.') }}</p>
        </div>
        <div class="sx-head-actions">
            <a href="{{ route('owner.sms.pricing') }}" class="sx-btn sx-btn-ghost"><i data-feather="tag"></i>{{ __('Pricing') }}</a>
            <button type="button" class="sx-btn sx-btn-primary" onclick="sxPackageModal()"><i data-feather="plus"></i>{{ __('New package') }}</button>
        </div>
    </header>

    @include('owner.partials.flash')

    @if($packages->isEmpty())
        <div class="sx-card sx-reveal">
            <div class="sx-empty">
                <span class="sx-empty-ic"><i data-feather="box"></i></span>
                <h3 class="sx-empty-title">{{ __('No packages yet') }}</h3>
                <p class="sx-empty-text">{{ __('Create your first bundle — for example 200, 500, 1000 or 5000 SMS.') }}</p>
                <button type="button" class="sx-btn sx-btn-primary" onclick="sxPackageModal()"><i data-feather="plus"></i>{{ __('New package') }}</button>
            </div>
        </div>
    @else
        <div class="sx-reveal" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px;">
            @foreach($packages as $p)
                <div class="sx-card" style="{{ $p->is_active ? '' : 'opacity:.6;' }}">
                    <div class="sx-card-pad">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                            <div>
                                <span class="sx-chip">{{ $p->name }}</span>
                                @unless($p->is_active)<span class="sx-pill sx-pill-queued" style="margin-inline-start:6px;">{{ __('Inactive') }}</span>@endunless
                            </div>
                            <div style="display:flex; gap:6px;">
                                <button type="button" class="sx-btn sx-btn-ghost sx-btn-sm"
                                        onclick='sxPackageModal(@json($p))'><i data-feather="edit-2"></i></button>
                                <form method="POST" action="{{ route('owner.sms.packages.destroy', $p) }}"
                                      onsubmit="return confirm('{{ __('Delete this package?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="sx-btn sx-btn-danger sx-btn-sm"><i data-feather="trash-2"></i></button>
                                </form>
                            </div>
                        </div>
                        <div style="margin-top:16px; font-family:var(--sx-display); font-size:2.4rem; font-weight:600; color:var(--bk-text); line-height:1; font-variant-numeric:tabular-nums;">
                            {{ number_format($p->credits) }}
                            <span style="font-family:inherit; font-size:.9rem; color:var(--bk-text-muted); font-weight:500;">{{ __('SMS') }}</span>
                        </div>
                        <div style="margin-top:14px; display:flex; align-items:baseline; gap:8px;">
                            <span style="font-size:1.35rem; font-weight:700; color:var(--bk-gold-strong);">{{ number_format($p->price, 2) }}</span>
                            <span class="sx-sub">{{ $p->currency }}</span>
                        </div>
                        <div class="sx-meter-legend" style="margin-top:12px; gap:12px;">
                            <span class="sx-legend"><i data-feather="hash" style="width:13px;height:13px;"></i>{{ number_format($p->pricePerSms(), 2) }} / {{ __('SMS') }}</span>
                            <span class="sx-legend"><i data-feather="clock" style="width:13px;height:13px;"></i>{{ $p->validity_days ? __(':n days', ['n' => $p->validity_days]) : __('No expiry') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Create / edit modal --}}
<div class="modal fade" id="sxPackageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content sx" style="background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:20px;">
      <form method="POST" id="sxPackageForm" action="{{ route('owner.sms.packages.store') }}">
        @csrf
        <input type="hidden" name="_method" id="sxPkgMethod" value="POST">
        <div class="modal-header" style="border-bottom:1px solid var(--bk-border); padding:18px 22px;">
          <h5 class="modal-title sx-card-title" id="sxPkgTitle" style="margin:0;">{{ __('New package') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="modal-body" style="padding:22px;">
          <div class="sx-field">
            <label>{{ __('Name') }}</label>
            <input type="text" name="name" id="sxPkgName" class="sx-input" maxlength="120" placeholder="{{ __('Starter 200') }}" required>
          </div>
          <div class="sx-row">
            <div class="sx-field"><label>{{ __('Credits (SMS)') }}</label><input type="number" name="credits" id="sxPkgCredits" class="sx-input" min="1" step="1" required></div>
            <div class="sx-field"><label>{{ __('Validity (days)') }}</label><input type="number" name="validity_days" id="sxPkgValidity" class="sx-input" min="1" step="1" placeholder="{{ __('Never') }}"></div>
          </div>
          <div class="sx-row">
            <div class="sx-field"><label>{{ __('Price') }}</label><input type="number" name="price" id="sxPkgPrice" class="sx-input" min="0" step="0.01" required></div>
            <div class="sx-field"><label>{{ __('Currency') }}</label><input type="text" name="currency" id="sxPkgCurrency" class="sx-input" maxlength="8" value="{{ config('booksy.sms.credits.currency', 'SYP') }}" required></div>
          </div>
          <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:.9rem; color:var(--bk-text-soft);">
            <input type="checkbox" name="is_active" id="sxPkgActive" value="1" checked>{{ __('Active (available to buy)') }}
          </label>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--bk-border); padding:16px 22px; gap:10px;">
          <button type="button" class="sx-btn sx-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="sx-btn sx-btn-primary"><i data-feather="check"></i>{{ __('Save package') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('owner-styles')
    @include('owner.sms.partials.styles')
@endpush

@push('scripts')
<script>
window.sxPackageModal = function (pkg) {
    var form   = document.getElementById('sxPackageForm');
    var title  = document.getElementById('sxPkgTitle');
    var method = document.getElementById('sxPkgMethod');
    var storeUrl = '{{ route('owner.sms.packages.store') }}';
    if (pkg && pkg.id) {
        form.action = storeUrl + '/' + pkg.id;
        method.value = 'PUT';
        title.textContent = '{{ __('Edit package') }}';
        document.getElementById('sxPkgName').value = pkg.name || '';
        document.getElementById('sxPkgCredits').value = pkg.credits || '';
        document.getElementById('sxPkgValidity').value = pkg.validity_days || '';
        document.getElementById('sxPkgPrice').value = pkg.price || '';
        document.getElementById('sxPkgCurrency').value = pkg.currency || 'SYP';
        document.getElementById('sxPkgActive').checked = !!pkg.is_active;
    } else {
        form.action = storeUrl;
        method.value = 'POST';
        title.textContent = '{{ __('New package') }}';
        form.reset();
        document.getElementById('sxPkgActive').checked = true;
    }
    new bootstrap.Modal(document.getElementById('sxPackageModal')).show();
};
</script>
@endpush

@endsection
