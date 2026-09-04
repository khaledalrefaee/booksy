<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\SmsMessage;
use App\Models\SmsPackage;
use App\Models\SmsSetting;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Services\Sms\RasselAccountClient;
use App\Services\Sms\SmsCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Owner-side SMS control room. Every figure comes from GlowRez's OWN ledger
 * (sms_wallets / sms_transactions / sms_messages) — the credits the owner
 * distributes to companies and branches. Rassel's real provider balance is
 * shown strictly separately (RasselAccountClient) as reference/monitoring and
 * is never mixed into a branch's credit figures.
 */
class SmsController extends Controller
{
    public function __construct(private SmsCreditService $credits) {}

    // ── Overview ─────────────────────────────────────────────────────────────

    public function overview(RasselAccountClient $rassel)
    {
        // GlowRez credit ledger totals (our own system, not Rassel).
        $totals = [
            'distributed' => (int) SmsWallet::sum('total_purchased'),
            'used'        => (int) SmsWallet::sum('total_used'),
            'remaining'   => (int) SmsWallet::sum('balance'),
        ];

        $messageStats = [
            'sent'     => (int) SmsMessage::where('status', 'sent')->count(),
            'failed'   => (int) SmsMessage::where('status', 'failed')->count(),
            'skipped'  => (int) SmsMessage::where('status', 'skipped')->count(),
            'segments' => (int) SmsMessage::where('status', 'sent')->sum('credits_used'),
        ];

        $companiesWithCredits = (int) SmsWallet::where('balance', '>', 0)->distinct('company_id')->count('company_id');
        $lowWallets = SmsWallet::with(['company', 'branch'])
            ->whereColumn('balance', '<=', 'low_balance_threshold')
            ->where('balance', '>', 0)
            ->orderBy('balance')->limit(5)->get();

        // Top consumers (companies by credits used).
        $topConsumers = Company::query()
            ->select('companies.*')
            ->selectRaw('(select coalesce(sum(total_used),0) from sms_wallets where sms_wallets.company_id = companies.id) as used')
            ->orderByDesc('used')->limit(6)->get()
            ->filter(fn ($c) => $c->used > 0)->values();

        $recentTx = SmsTransaction::with(['wallet.company', 'wallet.branch'])
            ->latest('id')->limit(8)->get();

        // Rassel provider status — separate, reference-only.
        $provider = $rassel->snapshot();

        // Capacity guard: the real number of SMS segments Rassel can still send
        // this cycle (plan pool + active free grants). Compared against the
        // GlowRez credits companies still hold (outstanding balance), so the
        // owner can see when they've distributed more than Rassel can deliver.
        $providerOk = $provider['ok'] ?? false;
        $capacity = $providerOk
            ? (int) ($provider['remaining_segments'] ?? 0) + (int) ($provider['free_grant']['remaining'] ?? 0)
            : null;
        $outstanding = (int) $totals['remaining'];
        $capacityMeta = [
            'available'      => $providerOk,
            'capacity'       => $capacity,
            'outstanding'    => $outstanding,
            'plan_remaining' => $providerOk ? (int) ($provider['remaining_segments'] ?? 0) : 0,
            'grant_remaining'=> $providerOk ? (int) ($provider['free_grant']['remaining'] ?? 0) : 0,
            'over'           => $capacity !== null && $outstanding > $capacity,
            'over_by'        => $capacity !== null ? max(0, $outstanding - $capacity) : 0,
            'used_pct'       => ($capacity !== null && $capacity > 0) ? min(100, round($outstanding / $capacity * 100)) : ($outstanding > 0 ? 100 : 0),
        ];

        return view('owner.sms.overview', compact(
            'totals', 'messageStats', 'companiesWithCredits',
            'lowWallets', 'topConsumers', 'recentTx', 'provider', 'capacityMeta'
        ));
    }

    // ── Analytics ────────────────────────────────────────────────────────────

    public function analytics()
    {
        $since = now()->subDays(30)->startOfDay();

        // Daily sent counts split by message type (for the trend chart).
        $rows = SmsMessage::query()
            ->where('status', 'sent')->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, message_type, count(*) as c, coalesce(sum(credits_used),0) as seg')
            ->groupBy('d', 'message_type')->get();

        $days = collect(range(0, 29))->map(fn ($i) => now()->subDays(29 - $i)->toDateString());
        $types = ['confirmation', 'reminder', 'followup', 'manual'];

        $series = [];
        foreach ($types as $t) {
            $series[$t] = $days->map(function ($d) use ($rows, $t) {
                return (int) ($rows->firstWhere(fn ($r) => $r->d === $d && $r->message_type === $t)->c ?? 0);
            })->all();
        }

        $byType = [];
        foreach ($types as $t) {
            $byType[$t] = [
                'messages' => (int) SmsMessage::where('status', 'sent')->where('message_type', $t)->count(),
                'segments' => (int) SmsMessage::where('status', 'sent')->where('message_type', $t)->sum('credits_used'),
            ];
        }

        $statusBreakdown = [
            'sent'    => (int) SmsMessage::where('status', 'sent')->count(),
            'failed'  => (int) SmsMessage::where('status', 'failed')->count(),
            'skipped' => (int) SmsMessage::where('status', 'skipped')->count(),
            'queued'  => (int) SmsMessage::where('status', 'queued')->count(),
        ];

        return view('owner.sms.analytics', compact('days', 'series', 'types', 'byType', 'statusBreakdown', 'since'));
    }

    // ── Companies usage ──────────────────────────────────────────────────────

    public function companies(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $companies = Company::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) =>
                $w->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")))
            ->selectRaw('companies.*')
            ->selectRaw('(select coalesce(sum(total_purchased),0) from sms_wallets where sms_wallets.company_id = companies.id) as allocated')
            ->selectRaw('(select coalesce(sum(total_used),0) from sms_wallets where sms_wallets.company_id = companies.id) as used')
            ->selectRaw('(select coalesce(sum(balance),0) from sms_wallets where sms_wallets.company_id = companies.id) as remaining')
            ->selectRaw('(select count(*) from sms_messages where sms_messages.company_id = companies.id and status = "sent") as sent_count')
            ->orderByDesc('used')->orderBy('name_en')
            ->paginate(15)->withQueryString();

        return view('owner.sms.companies', compact('companies', 'search'));
    }

    // ── Branches usage ───────────────────────────────────────────────────────

    public function branches(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $branches = Branch::query()
            ->with('company')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) =>
                $w->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")))
            ->selectRaw('branches.*')
            ->selectRaw('(select coalesce(sum(total_purchased),0) from sms_wallets where sms_wallets.branch_id = branches.id) as allocated')
            ->selectRaw('(select coalesce(sum(total_used),0) from sms_wallets where sms_wallets.branch_id = branches.id) as used')
            ->selectRaw('(select coalesce(sum(balance),0) from sms_wallets where sms_wallets.branch_id = branches.id) as remaining')
            ->selectRaw('(select count(*) from sms_messages where sms_messages.branch_id = branches.id and status = "sent") as sent_count')
            ->orderByDesc('used')->orderBy('name_en')
            ->paginate(15)->withQueryString();

        return view('owner.sms.branches', compact('branches', 'search'));
    }

    // ── Transactions (credit ledger) ─────────────────────────────────────────

    public function transactions(Request $request)
    {
        $type      = $request->get('type', '');
        $companyId = $request->get('company', '');

        $tx = SmsTransaction::query()
            ->with(['wallet.company', 'wallet.branch', 'package'])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($companyId !== '', fn ($q) => $q->whereHas('wallet', fn ($w) => $w->where('company_id', $companyId)))
            ->latest('id')->paginate(25)->withQueryString();

        $companies = Company::orderBy('name_en')->get(['id', 'name_en', 'name_ar']);
        $types = ['grant', 'purchase', 'consume', 'refund', 'expire', 'adjustment'];

        return view('owner.sms.transactions', compact('tx', 'companies', 'types', 'type', 'companyId'));
    }

    // ── Message logs ─────────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $status = $request->get('status', '');
        $type   = $request->get('type', '');

        $messages = SmsMessage::query()
            ->with(['company', 'branch', 'customer'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('message_type', $type))
            ->latest('id')->paginate(25)->withQueryString();

        $statuses = ['sent', 'failed', 'skipped', 'queued'];
        $types = ['confirmation', 'reminder', 'followup', 'manual'];

        return view('owner.sms.logs', compact('messages', 'statuses', 'types', 'status', 'type'));
    }

    // ── Packages ─────────────────────────────────────────────────────────────

    public function packages()
    {
        $packages = SmsPackage::orderBy('sort_order')->orderBy('credits')->get();
        $setting  = SmsSetting::current();

        return view('owner.sms.packages', compact('packages', 'setting'));
    }

    public function storePackage(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'credits'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:8'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active'     => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        SmsPackage::create($data);

        return back()->with('success', __('Package created.'));
    }

    public function updatePackage(Request $request, SmsPackage $package)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'credits'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:8'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active'     => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $package->update($data);

        return back()->with('success', __('Package updated.'));
    }

    public function destroyPackage(SmsPackage $package)
    {
        $package->delete();

        return back()->with('success', __('Package deleted.'));
    }

    // ── Pricing ──────────────────────────────────────────────────────────────

    public function pricing()
    {
        $setting  = SmsSetting::current();
        $packages = SmsPackage::orderBy('credits')->get();

        return view('owner.sms.pricing', compact('setting', 'packages'));
    }

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'price_per_sms' => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:8'],
        ]);

        SmsSetting::current()->update($data);

        return back()->with('success', __('Pricing updated.'));
    }

    // ── Grant free credits (to a company pool or a specific branch) ───────────

    public function grant(Request $request)
    {
        $data = $request->validate([
            'company_id'    => ['required', 'exists:companies,id'],
            'branch_id'     => ['nullable', 'exists:branches,id'],
            'credits'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'note'          => ['nullable', 'string', 'max:255'],
        ]);

        // Branch must belong to the chosen company.
        if (! empty($data['branch_id'])) {
            $branch = Branch::find($data['branch_id']);
            if (! $branch || $branch->company_id != $data['company_id']) {
                return back()->with('error', __('Selected branch does not belong to that company.'));
            }
        }

        $wallet = $this->credits->firstOrCreateWallet($data['company_id'], $data['branch_id'] ?? null);

        $this->credits->grant($wallet, (int) $data['credits'], [
            'note'       => $data['note'] ?? __('Owner grant'),
            'owner_id'   => auth('owner')->id(),
            'expires_at' => ! empty($data['validity_days']) ? now()->addDays((int) $data['validity_days']) : null,
        ]);

        return back()->with('success', __(':n SMS credits added.', ['n' => $data['credits']]));
    }

    /** Branches for a company — feeds the grant modal's dependent select. */
    public function companyBranches(Company $company)
    {
        return response()->json(
            $company->branches()->orderBy('sort_order')->get()->map(fn ($b) => [
                'id'   => $b->id,
                'name' => $b->localizedName(),
            ])
        );
    }
}
