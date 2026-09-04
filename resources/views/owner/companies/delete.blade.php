{{-- Delete company --}}
<div class="modal fade" id="modal-campania-delete" tabindex="-1" aria-labelledby="modal-campania-delete-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cm-modal">
            <form id="form-campania-delete" method="post" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <div class="cm-modal-titlewrap">
                        <span class="cm-modal-ic cm-modal-ic-danger" aria-hidden="true"><i data-feather="alert-triangle"></i></span>
                        <div>
                            <h5 class="modal-title" id="modal-campania-delete-label">{{ __('Delete company') }}</h5>
                            <div class="cm-modal-sub">{{ __('This action cannot be undone.') }}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" style="color:var(--bk-text-soft);">{{ __('Are you sure you want to delete this company?') }}</p>
                    <p class="fw-semibold mb-0 mt-2" style="color:var(--bk-danger);" id="delete-company-name-display"></p>
                    <p class="small mt-2 mb-0" style="color:var(--bk-text-muted);">{{ __('All branches, services, and appointments linked to this company will be removed.') }}</p>
                    <div class="cm-modal-foot">
                        <button type="button" class="cm-btn cm-btn-ghost" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="cm-btn cm-btn-danger"><i data-feather="trash-2"></i> {{ __('Delete') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
