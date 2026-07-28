<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds appointment_transitions from the legacy status_changed_by_* columns.
 *
 * Those columns only ever remembered the last hop, so this cannot reconstruct a
 * real history — but without it every pre-existing appointment shows an empty
 * timeline and the audit information it used to display is simply lost.
 *
 * Each appointment gets an opening row (null → its first known status) and, when
 * the legacy columns recorded a change, a second row for that hop.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointments')
            ->select([
                'id', 'company_id', 'status', 'status_previous', 'created_at',
                'status_changed_at', 'status_changed_by_type',
                'status_changed_by_id', 'status_changed_by_name', 'rejection_reason',
            ])
            ->orderBy('id')
            ->chunk(500, function ($appointments) {
                $rows = [];

                foreach ($appointments as $a) {
                    $changed = $a->status_changed_at !== null && $a->status_previous !== null;

                    // Opening entry: the status the appointment was born with.
                    $rows[] = [
                        'appointment_id' => $a->id,
                        'company_id'     => $a->company_id,
                        'from_status'    => null,
                        'to_status'      => $changed ? $a->status_previous : $a->status,
                        'actor_type'     => 'system',
                        'actor_id'       => null,
                        'actor_name'     => null,
                        'reason'         => null,
                        'automatic'      => 0,
                        'meta'           => json_encode(['backfilled' => true]),
                        'created_at'     => $a->created_at,
                    ];

                    if (! $changed) {
                        continue;
                    }

                    $rows[] = [
                        'appointment_id' => $a->id,
                        'company_id'     => $a->company_id,
                        'from_status'    => $a->status_previous,
                        'to_status'      => $a->status,
                        // 'owner' is not a TransitionActor; it maps onto the salon side.
                        'actor_type'     => in_array($a->status_changed_by_type, ['company', 'employee', 'customer'], true)
                            ? $a->status_changed_by_type
                            : ($a->status_changed_by_type === 'owner' ? 'company' : 'system'),
                        'actor_id'       => $a->status_changed_by_id,
                        'actor_name'     => $a->status_changed_by_name,
                        'reason'         => $a->rejection_reason,
                        'automatic'      => 0,
                        'meta'           => json_encode(['backfilled' => true]),
                        'created_at'     => $a->status_changed_at,
                    ];
                }

                if ($rows) {
                    DB::table('appointment_transitions')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        DB::table('appointment_transitions')
            ->whereJsonContains('meta->backfilled', true)
            ->delete();
    }
};
