<?php

namespace App\Services\Leads;

use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Support\LeadPhone;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Turns a validated form submission into a Lead.
 *
 * Duplicate strategy (same person raising their hand again): the phone number is
 * reduced to canonical digits and matched against leads.phone_normalized (UNIQUE,
 * so two simultaneous posts can't both insert); an e-mail match counts too. A match
 * never creates a second row and never deletes anything:
 *   - empty fields are filled, changed business fields take the newer value (the old
 *     value is kept in the timeline entry),
 *   - interests are merged (union),
 *   - interaction_count++ and last_interaction_at = now,
 *   - a "resubmitted" activity records exactly what was sent and from which source,
 *   - a lead the team had closed (not interested / lost) is re-opened as "new".
 * First-touch attribution stays on the lead; the new touch lives in the activity.
 */
class LeadSubmissionService
{
    /** Business columns that take the newest submitted value on a repeat submission. */
    private const OVERWRITE = ['full_name', 'business_name', 'business_type', 'city', 'number_of_branches'];

    /** Columns only filled when still empty (never replace something the team may have confirmed). */
    private const FILL_IF_EMPTY = ['whatsapp', 'email', 'area', 'website', 'instagram', 'facebook'];

    /**
     * @param  array  $data  sanitized, validated form fields
     * @param  array  $attr  attribution (source, campaign, utm_*, landing_page, referral)
     * @return array{0: Lead, 1: bool}  [lead, wasDuplicate]
     */
    public function submit(array $data, array $attr, Request $request): array
    {
        $phoneN = LeadPhone::normalize($data['phone']);
        $email  = $data['email'] ?? null;

        $existing = $this->findExisting($phoneN, $email);

        if ($existing === null) {
            try {
                $lead = $this->create($data, $attr, $request, $phoneN);
                $this->notify($lead, false);

                return [$lead, false];
            } catch (UniqueConstraintViolationException $e) {
                // Lost a race with an identical submission a few ms earlier.
                $existing = $this->findExisting($phoneN, $email);
                if ($existing === null) {
                    throw $e;
                }
            }
        }

        $lead = $this->refresh($existing, $data, $attr);
        $this->notify($lead, true);

        return [$lead, true];
    }

    private function findExisting(string $phoneN, ?string $email): ?Lead
    {
        return Lead::query()->where('phone_normalized', $phoneN)->first()
            ?? ($email ? Lead::query()->where('email', $email)->first() : null);
    }

    private function create(array $data, array $attr, Request $request, string $phoneN): Lead
    {
        return DB::transaction(function () use ($data, $attr, $request, $phoneN) {
            $lead = Lead::create([
                'full_name'           => $data['full_name'],
                'phone'               => $data['phone'],
                'phone_normalized'    => $phoneN,
                'whatsapp'            => $data['whatsapp'] ?? null,
                'email'               => $data['email'] ?? null,
                'business_name'       => $data['business_name'],
                'business_type'       => $data['business_type'],
                'city'                => $data['city'],
                'area'                => $data['area'] ?? null,
                'number_of_branches'  => $data['number_of_branches'],
                'website'             => $data['website'] ?? null,
                'instagram'           => $data['instagram'] ?? null,
                'facebook'            => $data['facebook'] ?? null,
                'interests'           => array_values($data['interests'] ?? []),
                'source'              => $attr['source'] ?? 'direct',
                'campaign'            => $attr['campaign'] ?? null,
                'landing_page'        => $attr['landing_page'] ?? null,
                'referral'            => $attr['referral'] ?? null,
                'utm_source'          => $attr['utm_source'] ?? null,
                'utm_medium'          => $attr['utm_medium'] ?? null,
                'utm_campaign'        => $attr['utm_campaign'] ?? null,
                'utm_content'         => $attr['utm_content'] ?? null,
                'status'              => 'new',
                'interaction_count'   => 1,
                'last_interaction_at' => now(),
                'locale'              => app()->getLocale(),
                'ip_address'          => $request->ip(),
                'user_agent'          => Str::limit((string) $request->userAgent(), 250, ''),
            ]);

            $lead->activities()->create([
                'type'       => LeadActivity::CREATED,
                'meta'       => ['source' => $lead->source, 'campaign' => $lead->campaign],
                'created_at' => now(),
            ]);

            return $lead;
        });
    }

    private function refresh(Lead $lead, array $data, array $attr): Lead
    {
        return DB::transaction(function () use ($lead, $data, $attr) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);

            $changes = [];
            $update  = [];

            foreach (self::OVERWRITE as $field) {
                $new = $data[$field] ?? null;
                if ($new !== null && $new !== '' && (string) $new !== (string) $lead->{$field}) {
                    $changes[$field] = [$lead->{$field}, $new];
                    $update[$field]  = $new;
                }
            }

            foreach (self::FILL_IF_EMPTY as $field) {
                $new = $data[$field] ?? null;
                if (($lead->{$field} === null || $lead->{$field} === '') && $new !== null && $new !== '') {
                    $update[$field] = $new;
                } elseif ($new && $new !== $lead->{$field}) {
                    $changes[$field] = [$lead->{$field}, $new];   // visible in the timeline, not applied
                }
            }

            $interests = array_values(array_unique(array_merge($lead->interests ?? [], $data['interests'] ?? [])));
            $addedInterests = array_values(array_diff($interests, $lead->interests ?? []));
            if ($addedInterests) {
                $update['interests'] = $interests;
            }

            $update['interaction_count']   = $lead->interaction_count + 1;
            $update['last_interaction_at'] = now();

            $oldStatus = $lead->status;
            $reopen = in_array($oldStatus, (array) config('leads.reopen_statuses', []), true);
            if ($reopen) {
                $update['status'] = 'new';
            }

            $lead->forceFill($update)->save();

            $lead->activities()->create([
                'type' => LeadActivity::RESUBMITTED,
                'meta' => array_filter([
                    'source'           => $attr['source'] ?? null,
                    'campaign'         => $attr['campaign'] ?? null,
                    'phone_submitted'  => $data['phone'],
                    'changes'          => $changes ?: null,
                    'added_interests'  => $addedInterests ?: null,
                ], fn ($v) => $v !== null),
                'created_at' => now(),
            ]);

            if ($reopen) {
                $lead->activities()->create([
                    'type' => LeadActivity::REOPENED,
                    'meta' => ['from' => $oldStatus, 'to' => 'new'],
                    'created_at' => now(),
                ]);
            }

            return $lead->refresh();
        });
    }

    /**
     * Tell the team. Queued, and never allowed to fail the visitor's submission —
     * the lead is already saved, the worst case is a missing email (logged).
     */
    private function notify(Lead $lead, bool $repeat): void
    {
        // One address, or several separated by commas / semicolons.
        $to = array_values(array_filter(array_map('trim', preg_split('/[;,]+/', (string) config('leads.notification_email')) ?: []),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        if (! $to) {
            Log::warning('Lead saved but LEADS_NOTIFICATION_EMAIL is not set — no email sent.', ['lead' => $lead->id]);

            return;
        }

        try {
            Mail::to($to)->queue(new NewLeadMail($lead->id, $repeat));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
