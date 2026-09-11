<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\SendOwnerBroadcastJob;
use App\Models\Company;
use App\Models\OwnerEmail;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Owner-composed broadcast emails. The owner picks a verified "From" identity,
 * an audience (all / active / selected companies / manual addresses), writes a
 * subject + body, and the send is handed to {@see SendOwnerBroadcastJob}.
 */
class EmailController extends Controller
{
    public function index(Request $request): View
    {
        $senders = config('mail.owner_senders', []);

        // Companies that actually have an email, for the "selected" picker.
        $companies = Company::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'email']);

        // Per-page: 10 / 20 / 50 / all. Anything else falls back to 10.
        $perPage = $request->query('per_page', '10');
        $allowed = ['10', '20', '50'];
        $isAll   = $perPage === 'all';
        if (! $isAll && ! in_array($perPage, $allowed, true)) {
            $perPage = '10';
        }

        // Server-side filters so search/status/audience span ALL records, not
        // just the current page.
        $query = OwnerEmail::query()
            ->with('owner:id,name')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('subject', 'like', $term)
                    ->orWhere('from_address', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('audience'), fn ($q) => $q->where('audience', $request->query('audience')))
            ->latest('id');

        $history = $query
            ->paginate($isAll ? max(1, (clone $query)->count()) : (int) $perPage)
            ->withQueryString();

        return view('owner.emails.index', compact('senders', 'companies', 'history', 'perPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $senders = collect(config('mail.owner_senders', []));
        $allowedFrom = $senders->pluck('address')->all();

        $validated = $request->validate([
            'from_address' => ['required', 'string', Rule::in($allowedFrom)],
            'subject'      => ['required', 'string', 'max:200'],
            'body'         => ['required', 'string', 'max:5000'],
            'audience'     => ['required', 'string', Rule::in(['all', 'active', 'selected', 'manual'])],
            'company_ids'   => ['required_if:audience,selected', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'manual_emails' => ['required_if:audience,manual', 'nullable', 'string', 'max:20000'],
        ]);

        // The display name is taken from config, never from the request, so the
        // sender identity can't be tampered with.
        $fromName = (string) ($senders->firstWhere('address', $validated['from_address'])['name'] ?? 'GlowRez');

        $recipients = $this->resolveRecipients($validated);

        if ($recipients->isEmpty()) {
            return redirect()->back()->withInput()
                ->with('error', __('No valid recipients matched your selection.'));
        }

        $email = OwnerEmail::create([
            'owner_id'         => Auth::guard('owner')->id(),
            'from_address'     => $validated['from_address'],
            'from_name'        => $fromName,
            'subject'          => $validated['subject'],
            'body'             => $validated['body'],
            'audience'         => $validated['audience'],
            'recipients'       => $recipients->values()->all(),
            'recipients_count' => $recipients->count(),
            'status'           => 'queued',
        ]);

        SendOwnerBroadcastJob::dispatch($email->id, app()->getLocale() === 'ar');

        OwnerAudit::record(
            'emails.broadcast',
            new: [
                'from'       => $validated['from_address'],
                'subject'    => $validated['subject'],
                'audience'   => $validated['audience'],
                'recipients' => $recipients->count(),
            ],
            label: $validated['subject'],
        );

        return redirect()->route('owner.emails.index')
            ->with('success', __('Email queued for :count recipient(s).', ['count' => $recipients->count()]));
    }

    /**
     * Resolve the chosen audience into a de-duplicated list of
     * [['email' => …, 'name' => …], …]. Keyed by lower-cased email so the same
     * address is never mailed twice in one broadcast.
     */
    private function resolveRecipients(array $validated): \Illuminate\Support\Collection
    {
        $audience = $validated['audience'];

        if ($audience === 'manual') {
            return $this->parseManualEmails($validated['manual_emails'] ?? '');
        }

        $companies = Company::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->when($audience === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($audience === 'selected', fn ($q) => $q->whereIn('id', $validated['company_ids'] ?? []))
            ->get(['id', 'name_en', 'name_ar', 'email']);

        return $companies
            ->filter(fn (Company $c) => filter_var($c->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (Company $c) => [
                Str::lower($c->email) => ['email' => $c->email, 'name' => $c->localizedName()],
            ]);
    }

    /** Split a free-text blob (commas / semicolons / whitespace / newlines) into valid emails. */
    private function parseManualEmails(string $raw): \Illuminate\Support\Collection
    {
        return collect(preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn ($e) => [Str::lower($e) => ['email' => $e, 'name' => '']]);
    }
}
