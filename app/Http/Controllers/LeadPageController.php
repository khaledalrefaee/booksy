<?php

namespace App\Http\Controllers;

use App\Services\Leads\LeadAttribution;
use App\Services\Leads\LeadSubmissionService;
use App\Support\LeadCatalog;
use App\Support\LeadContact;
use App\Support\LeadPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public pre-launch funnel: /welcome (three paths) → /for-business → /join (form).
 * No login anywhere — the only thing a visitor ever submits is the interest form.
 */
class LeadPageController extends Controller
{
    public function __construct(
        private LeadAttribution $attribution,
        private LeadSubmissionService $submissions,
    ) {
    }

    public function welcome(Request $request, bool $trackHere = false): View
    {
        $this->arabicByDefault($request);

        // Routed pages are tracked by the lead.track middleware; "/" (gateway on root) isn't routed through it.
        if ($trackHere) {
            $this->attribution->recordVisit($request, 'welcome', $this->attribution->capture($request));
        }

        return view('leads.welcome');
    }

    public function join(Request $request): View
    {
        $this->arabicByDefault($request);

        return view('leads.join', [
            'formToken' => Crypt::encryptString((string) time()),
            'cities'    => LeadCatalog::options('cities'),
            'types'     => LeadCatalog::options('business_types'),
            'interests' => LeadCatalog::options('interests'),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->arabicByDefault($request);

        // 1. Bots that fill every input: pretend it worked, store nothing.
        if (filled($request->input('hp_extra'))) {
            Log::notice('Lead form dropped: honeypot field was filled.', ['ip' => $request->ip(), 'ua' => (string) $request->userAgent()]);

            return $this->success($request, null, false);
        }

        // 2. Time trap: the form carries an encrypted render timestamp.
        $renderedAt = $this->renderedAt((string) $request->input('_t'));
        if ($renderedAt === null || (time() - $renderedAt) > 86400 * (int) config('leads.max_form_age_days', 7)) {
            return $this->fail($request, __('This page has expired. Please reload it and try again.'), 422);
        }
        if ((time() - $renderedAt) < (int) config('leads.min_fill_seconds', 3)) {
            Log::notice('Lead form dropped: submitted faster than a person can fill it.', ['ip' => $request->ip(), 'seconds' => time() - $renderedAt]);

            return $this->success($request, null, false);
        }

        // 3. Validate + sanitize. "Same number on WhatsApp" is the common case, so it is one tick, not a second field.
        if ($request->boolean('whatsapp_same')) {
            $request->merge(['whatsapp' => $request->input('phone')]);
        }
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $data = $this->sanitize($data);

        // 4. Save (or fold into the existing lead) — attribution comes from the session/cookie, never the form.
        [$lead, $duplicate] = $this->submissions->submit($data, $this->attribution->current($request), $request);

        return $this->success($request, $lead, $duplicate);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $this->arabicByDefault($request);

        $result = $request->session()->get('lead_result');
        if (! is_array($result)) {
            return redirect()->route('leads.join');
        }

        return view('leads.thanks', $result);
    }

    /* ── helpers ────────────────────────────────────────────────────────── */

    /** Nobody has picked a language yet (no session choice) ⇒ Arabic. An explicit EN/عربي choice always wins. */
    private function arabicByDefault(Request $request): void
    {
        if (! $request->session()->has('locale')) {
            app()->setLocale('ar');
        }
    }

    private function success(Request $request, $lead, bool $duplicate): JsonResponse|RedirectResponse
    {
        $name     = $lead?->full_name ?? (string) $request->input('full_name');
        $business = $lead?->business_name ?? (string) $request->input('business_name');
        $message  = app()->getLocale() === 'ar'
            ? "مرحباً GlowRez، أنا {$name} من {$business}. سجّلت اهتمامي بالانضمام."
            : "Hello GlowRez, I'm {$name} from {$business}. I just registered my interest in joining.";

        $result = [
            'duplicate'    => $duplicate,
            'firstName'    => Str::of($name)->squish()->before(' ')->limit(30, '')->toString(),
            'whatsapp_url' => LeadContact::whatsappUrl($message),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => true,
                'message' => $duplicate ? __('Your details are already with us.') : __('Your details were saved.'),
                'data'    => $result,
            ]);
        }

        return redirect()->route('leads.thanks')->with('lead_result', $result);
    }

    private function fail(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => false, 'message' => $message, 'data' => null], $status);
        }

        return back()->withInput()->withErrors(['form' => $message]);
    }

    private function renderedAt(string $token): ?int
    {
        try {
            $ts = (int) Crypt::decryptString($token);
        } catch (\Throwable) {
            return null;
        }

        return $ts > 0 ? $ts : null;
    }

    private function rules(): array
    {
        $phone = function (string $attribute, mixed $value, \Closure $fail) {
            if (! LeadPhone::isValid(is_string($value) ? $value : null)) {
                $fail(__('Please enter a valid phone number.'));
            }
        };

        return [
            'full_name'          => ['required', 'string', 'min:2', 'max:120'],
            'phone'              => ['required', 'string', 'max:40', $phone],
            'whatsapp'           => ['nullable', 'string', 'max:40', $phone],
            'email'              => ['nullable', 'email:rfc', 'max:150'],
            'business_name'      => ['required', 'string', 'min:2', 'max:150'],
            'business_type'      => ['required', Rule::in(LeadCatalog::keys('business_types'))],
            'city'               => ['required', 'string', 'max:80'],
            'city_other'         => ['nullable', 'required_if:city,other', 'string', 'max:80'],
            'area'               => ['nullable', 'string', 'max:100'],
            'number_of_branches' => ['required', 'integer', 'min:1', 'max:99'],
            'website'            => ['nullable', 'string', 'max:255'],
            'instagram'          => ['nullable', 'string', 'max:255', 'regex:/^(@?[A-Za-z0-9._]{1,60}|https?:\/\/\S+)$/'],
            'facebook'           => ['nullable', 'string', 'max:255'],
            'interests'          => ['nullable', 'array', 'max:12'],
            'interests.*'        => ['string', Rule::in(LeadCatalog::keys('interests'))],
        ];
    }

    private function messages(): array
    {
        return [
            'required'            => __('This field is required.'),
            'required_if'         => __('This field is required.'),
            'instagram.regex'     => __('Enter your Instagram username or link.'),
            'email.email'         => __('Enter a valid email address.'),
            'min'                 => __('This is too short.'),
            'max'                 => __('This is too long.'),
        ];
    }

    private function attributes(): array
    {
        return [
            'full_name'          => __('Full name'),
            'phone'              => __('Phone number'),
            'business_name'      => __('Business name'),
            'business_type'      => __('Business type'),
            'city'               => __('City'),
            'number_of_branches' => __('Number of branches'),
        ];
    }

    /** Strip markup/control chars, squash whitespace, normalise the link-ish fields. */
    private function sanitize(array $d): array
    {
        $clean = function (?string $v, int $max): ?string {
            if ($v === null) {
                return null;
            }
            $v = strip_tags($v);
            $v = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v) ?? '';
            $v = trim(preg_replace('/\s+/u', ' ', $v) ?? '');

            return $v === '' ? null : Str::limit($v, $max, '');
        };

        $city = $d['city'] === 'other'
            ? ($clean($d['city_other'] ?? null, 80) ?? 'other')
            : $clean($d['city'], 80);

        $website = $clean($d['website'] ?? null, 255);
        if ($website && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://'.$website;
        }
        if ($website && ! filter_var($website, FILTER_VALIDATE_URL)) {
            $website = null;
        }

        $email = $clean($d['email'] ?? null, 150);

        return [
            'full_name'          => $clean($d['full_name'], 120),
            'phone'              => $clean($d['phone'], 40),
            'whatsapp'           => $clean($d['whatsapp'] ?? null, 40) ?: null,
            'email'              => $email ? Str::lower($email) : null,
            'business_name'      => $clean($d['business_name'], 150),
            'business_type'      => $d['business_type'],
            'city'               => $city,
            'area'               => $clean($d['area'] ?? null, 100),
            'number_of_branches' => (int) $d['number_of_branches'],
            'website'            => $website,
            'instagram'          => $clean($d['instagram'] ?? null, 255),
            'facebook'           => $clean($d['facebook'] ?? null, 255),
            'interests'          => array_values(array_unique($d['interests'] ?? [])),
        ];
    }
}
