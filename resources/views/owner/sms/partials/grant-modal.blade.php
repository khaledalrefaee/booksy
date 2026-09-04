{{-- Grant free GlowRez credits to a company pool or a specific branch. --}}
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
              <input type="number" name="credits" id="sxGrantCredits" class="sx-input" min="1" step="1" placeholder="200" required>
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
          <button type="submit" class="sx-btn sx-btn-primary"><i data-feather="plus"></i>{{ __('Grant credits') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
