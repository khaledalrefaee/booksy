@if(session()->has('impersonator_owner_id') && auth('company')->check())
    <div class="d-flex align-items-center justify-content-center gap-3 px-3 py-2 text-white"
         style="background:#b02a37; position:sticky; top:0; z-index:1090;"
         role="alert" aria-live="polite">
        <i data-feather="eye" style="width:16px;height:16px;" class="flex-shrink-0"></i>
        <span class="tx-13 fw-semibold">
            {{ __('You are browsing as :company — actions here affect their real data.', ['company' => auth('company')->user()->localizedName()]) }}
        </span>
        <form method="post" action="{{ route('owner.impersonation.stop') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-light rounded-pill fw-semibold py-0 px-3">
                {{ __('Back to owner panel') }}
            </button>
        </form>
    </div>
@endif
