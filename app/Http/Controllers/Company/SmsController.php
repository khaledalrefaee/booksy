<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\OwnerNotification;
use App\Models\SmsAutomationSetting;
use App\Models\SmsMessage;
use App\Models\SmsPackage;
use App\Models\SmsPurchaseRequest;
use App\Models\SmsTemplate;
use App\Models\SmsWallet;
use App\Services\Sms\SmsCreditService;
use App\Services\Sms\SmsSegment;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Company-facing SMS panel. Shows the company its own GlowRez credits (per
 * branch, with the shared company pool), lets it opt each branch into the
 * confirmation / reminder / follow-up automations, edit templates, set a
 * low-balance alert, review history, and request more credits.
 *
 * No control here sends an SMS or implies live sending — delivery is wired to
 * Rassel separately once the real send endpoint is provided.
 */
class SmsController extends Controller
{
    public function __construct(private SmsCreditService $credits) {}

    private function company()
    {
        return Auth::guard('company')->user();
    }

    // ── Overview ─────────────────────────────────────────────────────────────

    public function overview()
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        // Ensure a wallet row exists per branch + the company pool, so the cards
        // always render (0/0/0 when nothing granted yet).
        $pool = $this->credits->firstOrCreateWallet($company->id, null);
        $walletCards = [];
        foreach ($branches as $branch) {
            $w = $branch->smsWallet ?: $this->credits->firstOrCreateWallet($company->id, $branch->id);
            $walletCards[] = ['branch' => $branch, 'wallet' => $w->refresh()];
        }

        $totals = [
            'allocated' => (int) SmsWallet::where('company_id', $company->id)->sum('total_purchased'),
            'used'      => (int) SmsWallet::where('company_id', $company->id)->sum('total_used'),
            'remaining' => (int) SmsWallet::where('company_id', $company->id)->sum('balance'),
        ];

        $byType = [];
        foreach (['confirmation', 'reminder', 'followup'] as $t) {
            $byType[$t] = (int) SmsMessage::where('company_id', $company->id)
                ->where('message_type', $t)->where('status', 'sent')->count();
        }

        $recent = SmsMessage::where('company_id', $company->id)->latest('id')->limit(6)->get();

        return view('company.sms.overview', compact('company', 'pool', 'walletCards', 'totals', 'byType', 'recent'));
    }

    // ── Automations ──────────────────────────────────────────────────────────

    public function automations()
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        $settings = SmsAutomationSetting::where('company_id', $company->id)
            ->get()->keyBy('branch_id');

        return view('company.sms.automations', compact('company', 'branches', 'settings'));
    }

    public function updateAutomations(Request $request, $branchId)
    {
        $company = $this->company();
        $branch  = $company->branches()->findOrFail($branchId);

        $data = $request->validate([
            'confirmation_enabled'    => ['nullable', 'boolean'],
            'reminder_enabled'        => ['nullable', 'boolean'],
            'reminder_offset_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'followup_enabled'        => ['nullable', 'boolean'],
            'followup_days'           => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        SmsAutomationSetting::updateOrCreate(
            ['company_id' => $company->id, 'branch_id' => $branch->id],
            [
                'confirmation_enabled'    => $request->boolean('confirmation_enabled'),
                'reminder_enabled'        => $request->boolean('reminder_enabled'),
                'reminder_offset_minutes' => $data['reminder_offset_minutes'],
                'followup_enabled'        => $request->boolean('followup_enabled'),
                'followup_days'           => $data['followup_days'],
            ]
        );

        return back()->with('success', __('Automations saved for :branch.', ['branch' => $branch->localizedName()]));
    }

    // ── Templates ────────────────────────────────────────────────────────────

    public function templates()
    {
        $company = $this->company();
        $keys    = ['confirmation', 'reminder', 'followup'];
        $locale  = app()->getLocale() === 'en' ? 'en' : 'ar';

        $templates = [];
        foreach ($keys as $key) {
            $tpl = SmsTemplate::where('company_id', $company->id)
                ->whereNull('branch_id')->where('key', $key)->where('locale', $locale)->first();
            $templates[$key] = $tpl?->body ?? SmsTemplate::defaultBody($key, $locale);
        }

        return view('company.sms.templates', [
            'company'   => $company,
            'templates' => $templates,
            'keys'      => $keys,
            'locale'    => $locale,
            'variables' => SmsTemplate::VARIABLES,
        ]);
    }

    public function updateTemplate(Request $request)
    {
        $company = $this->company();
        $locale  = app()->getLocale() === 'en' ? 'en' : 'ar';

        $data = $request->validate([
            'key'  => ['required', 'in:confirmation,reminder,followup'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        SmsTemplate::updateOrCreate(
            ['company_id' => $company->id, 'branch_id' => null, 'key' => $data['key'], 'locale' => $locale],
            ['body' => $data['body'], 'is_active' => true]
        );

        return back()->with('success', __('Template saved.'));
    }

    /** Char-counter / predicted-segment endpoint for the template editor. */
    public function previewSegments(Request $request)
    {
        $body = (string) $request->input('body', '');
        $a    = SmsSegment::analyze($body);

        return response()->json([
            'length'   => $a['length'],
            'segments' => $a['segments'],
            'encoding' => $a['encoding'],
            'per'      => $a['per_segment'],
            'credits'  => SmsSegment::credits($body),
        ]);
    }

    // ── History ──────────────────────────────────────────────────────────────

    public function history(Request $request)
    {
        $company = $this->company();
        $status  = $request->get('status', '');
        $type    = $request->get('type', '');

        $messages = SmsMessage::where('company_id', $company->id)
            ->with(['branch', 'customer'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('message_type', $type))
            ->latest('id')->paginate(20)->withQueryString();

        $statuses = ['sent', 'failed', 'skipped', 'queued'];
        $types    = ['confirmation', 'reminder', 'followup', 'manual'];

        return view('company.sms.history', compact('messages', 'statuses', 'types', 'status', 'type'));
    }

    // ── Purchase / request credits ───────────────────────────────────────────

    public function purchase()
    {
        $company  = $this->company();
        $packages = SmsPackage::where('is_active', true)->orderBy('credits')->get();
        $branches = $company->branches()->orderBy('sort_order')->get();
        $requests = SmsPurchaseRequest::where('company_id', $company->id)
            ->with(['package', 'branch'])->latest('id')->limit(10)->get();

        return view('company.sms.purchase', compact('company', 'packages', 'branches', 'requests'));
    }

    public function requestPurchase(Request $request)
    {
        $company = $this->company();

        $data = $request->validate([
            'package_id' => ['required', 'exists:sms_packages,id'],
            'branch_id'  => ['nullable', 'integer'],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        $package = SmsPackage::findOrFail($data['package_id']);

        // Branch (if given) must belong to this company.
        $branchId = null;
        if (! empty($data['branch_id'])) {
            $branch = $company->branches()->find($data['branch_id']);
            $branchId = $branch?->id;
        }

        $req = SmsPurchaseRequest::create([
            'company_id' => $company->id,
            'branch_id'  => $branchId,
            'package_id' => $package->id,
            'credits'    => $package->credits,
            'price'      => $package->price,
            'currency'   => $package->currency,
            'status'     => 'pending',
            'note'       => $data['note'] ?? null,
        ]);

        // Let the platform owner know a company wants more credits.
        try {
            OwnerNotification::create([
                'company_id' => $company->id,
                'type'       => 'sms_purchase_request',
                'title'      => __('SMS credit request'),
                'body'       => $company->localizedName() . ' — ' . $package->name . ' (' . number_format($package->credits) . ' SMS)',
                'icon'       => '💬',
                'color'      => '#B08D3F',
                'link'       => route('owner.sms.overview'),
                'data'       => ['request_id' => $req->id, 'company_id' => $company->id],
            ]);
        } catch (\Throwable) { /* notification is best-effort */ }

        return back()->with('success', __('Your request was sent. The GlowRez team will top up your credits shortly.'));
    }

    // ── Low-balance alert settings ───────────────────────────────────────────

    public function updateThreshold(Request $request)
    {
        $company = $this->company();

        $data = $request->validate([
            'wallet_id'             => ['required', 'integer'],
            'low_balance_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notify_low_balance'    => ['nullable', 'boolean'],
        ]);

        $wallet = SmsWallet::where('company_id', $company->id)->find($data['wallet_id']);
        if (! $wallet) {
            return back()->with('error', __('Wallet not found.'));
        }

        $wallet->update([
            'low_balance_threshold' => $data['low_balance_threshold'],
            'notify_low_balance'    => $request->boolean('notify_low_balance'),
            // Re-arm the alert so a new threshold can fire again.
            'notified_low_at'       => null,
        ]);

        return back()->with('success', __('Alert settings updated.'));
    }
}
