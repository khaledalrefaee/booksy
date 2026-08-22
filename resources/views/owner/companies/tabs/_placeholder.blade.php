{{-- Workspace tab placeholder — replaced by each module agent. --}}
<div class="bk-card">
    <div class="bk-empty">
        <div class="bk-empty-ic"><i data-feather="tool"></i></div>
        <p><strong>{{ $tabLabel ?? __('This module') }}</strong></p>
        <p>{{ __('This workspace tab is being built.') }}</p>
        <form method="post" action="{{ $ws->fullEditorAction() }}" class="mt-2"
              onsubmit="return confirm('{{ __('Log in as this company? Every action will be recorded in the audit log.') }}')">
            @csrf
            <button type="submit" class="bk-btn bk-btn--gold">
                <i data-feather="external-link"></i> {{ __('Open full editor') }}
            </button>
        </form>
    </div>
</div>
