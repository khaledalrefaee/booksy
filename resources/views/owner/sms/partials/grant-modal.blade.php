{{-- Grant free GlowRez credits to a company pool or a specific branch.
     A capacity banner reflects what Rasel can actually deliver this cycle so the
     owner never distributes more credits than the provider can send. --}}
@php
    $__snap = app(\App\Services\Sms\RasselAccountClient::class)->snapshot();
    $__cap  = app(\App\Services\Sms\SmsCreditService::class)->distributionCapacity($__snap);
@endphp
<div class="modal fade" id="sxGrantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content sx" style="background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:20px;">
      <form method="POST" action="{{ route('owner.sms.grant') }}">
        @csrf
        <div class="modal-header" style="border-bottom:1px solid var(--bk-border); padding:18px 22px;">
          <div>
            <h5 class="modal-title sx-card-title" style="margin:0;">{{ __('Add free SMS') }}</h5>
            <p class="sx-card-note">{{ __('Grant GlowRez credits — no charge to the company.') }}</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="modal-body" style="padding:22px;">
          {{-- Real Rasel sending capacity vs credits already distributed. --}}
          @if($__cap['enforce'] ?? false)
            <div class="sx-cap-banner {{ ($__cap['available'] ?? 0) <= 0 ? 'is-empty' : '' }}"
                 style="border:1px solid var(--bk-border); border-radius:14px; padding:14px 16px; margin-bottom:18px; background:var(--bk-surface-2, rgba(0,0,0,.02));">
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <i data-feather="send" style="width:15px;height:15px; color:var(--bk-gold-strong);"></i>
                <strong class="sx-name">{{ __('Rasel sending capacity') }}</strong>
                <span class="sx-ref-tag" style="margin-inline-start:auto;"><i data-feather="eye"></i>{{ __('Provider') }}</span>
              </div>
              <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:8px 16px;">
                <div class="sx-sub">{{ __('Rasel wallet balance') }}: <strong class="sx-mono">{{ number_format((float) ($__cap['wallet_balance'] ?? 0), 2) }} {{ $__cap['wallet_currency'] ?? 'USD' }}</strong></div>
                <div class="sx-sub">{{ __('Free grant left') }}: <strong class="sx-mono">{{ number_format((int) ($__cap['grant_remaining'] ?? 0)) }} / {{ number_format((int) ($__cap['grant_total'] ?? 0)) }}</strong></div>
                <div class="sx-sub">{{ __('Plan segments left') }}: <strong class="sx-mono">{{ number_format((int) ($__cap['plan_remaining'] ?? 0)) }}</strong></div>
                <div class="sx-sub">{{ __('Already distributed') }}: <strong class="sx-mono">{{ number_format((int) ($__cap['outstanding'] ?? 0)) }}</strong></div>
              </div>
              <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--bk-border); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <span class="sx-sub">{{ __('Available to grant') }}</span>
                <strong class="sx-mono" style="font-size:1.15rem; color:{{ ($__cap['available'] ?? 0) <= 0 ? 'var(--bk-danger, #b3261e)' : 'var(--bk-gold-strong)' }};">{{ number_format((int) ($__cap['available'] ?? 0)) }} {{ __('SMS') }}</strong>
              </div>
              @if(($__cap['available'] ?? 0) <= 0)
                <div class="sx-sub" style="margin-top:8px; color:var(--bk-danger, #b3261e);">
                  {{ __('Rasel has no deliverable capacity left. Top up the Rasel plan or balance before granting more.') }}
                </div>
              @endif
            </div>
          @elseif(!($__snap['configured'] ?? false))
            <div class="sx-note sx-note-warn" style="margin-bottom:18px;">
              <i data-feather="alert-circle"></i>
              <span>{{ __('Rasel is not configured, so sending capacity is unknown and grants are not limited.') }}</span>
            </div>
          @else
            <div class="sx-note sx-note-info" style="margin-bottom:18px;">
              <i data-feather="info"></i>
              <span>{{ __('Could not reach Rasel, so sending capacity is unknown and grants are not limited right now.') }}</span>
            </div>
          @endif

          <div class="sx-field">
            <label for="sxGrantCompany">{{ __('Company') }}</label>
            <select name="company_id" id="sxGrantCompany" class="sx-input" required
                    data-branches-url="{{ route('owner.sms.company-branches', ['company' => 'CID']) }}">
              <option value="">{{ __('Select a company…') }}</option>
              @foreach(\App\Models\Company::orderBy('name_en')->get() as $co)
                <option value="{{ $co->id }}">{{ $co->localizedName() }}</option>
              @endforeach
            </select>
          </div>
          <div class="sx-field">
            <label for="sxGrantBranch">{{ __('Branch') }}</label>
            <select name="branch_id" id="sxGrantBranch" class="sx-input">
              <option value="">{{ __('Company pool (all branches)') }}</option>
            </select>
            <p class="sx-hint">{{ __('Leave as pool to share across the company, or pick one branch.') }}</p>
          </div>
          <div class="sx-row">
            <div class="sx-field">
              <label for="sxGrantCredits">{{ __('Credits (SMS)') }}</label>
              <input type="number" name="credits" id="sxGrantCredits" class="sx-input" min="1" step="1" placeholder="200" required
                     @if($__cap['enforce'] ?? false)
                        data-available="{{ (int) ($__cap['available'] ?? 0) }}"
                        data-over-msg="{{ __('You can grant at most :n SMS right now, based on what Rasel can deliver.', ['n' => number_format((int) ($__cap['available'] ?? 0))]) }}"
                     @endif>
              <p class="sx-hint" id="sxGrantCreditsHint" hidden style="color:var(--bk-danger, #b3261e);"></p>
            </div>
            <div class="sx-field">
              <label for="sxGrantValidity">{{ __('Valid for (days)') }}</label>
              <input type="number" name="validity_days" id="sxGrantValidity" class="sx-input" min="1" step="1" placeholder="{{ __('Never expires') }}">
            </div>
          </div>
          <div class="sx-field" style="margin-bottom:0;">
            <label for="sxGrantNote">{{ __('Note') }}</label>
            <input type="text" name="note" id="sxGrantNote" class="sx-input" maxlength="255" placeholder="{{ __('e.g. Launch promotion') }}">
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--bk-border); padding:16px 22px; gap:10px;">
          <button type="button" class="sx-btn sx-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" id="sxGrantSubmit" class="sx-btn sx-btn-primary"><i data-feather="plus"></i>{{ __('Grant credits') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
