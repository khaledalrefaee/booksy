<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fold the old free-text statuses onto the AppointmentStatus enum.
 *
 * There is no production data yet, so this converts in place with no
 * dual-write compatibility window. If that ever stops being true, this
 * migration must be replaced by a backfill + dual-read phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (AppointmentStatus::legacyMap() as $old => $new) {
            DB::table('appointments')->where('status', $old)->update(['status' => $new]);
        }

        // Anything not in the enum would silently break the state machine, so
        // park it in a terminal status rather than leave an unknown value.
        $known = array_column(AppointmentStatus::cases(), 'value');

        DB::table('appointments')
            ->whereNotIn('status', $known)
            ->update(['status' => AppointmentStatus::CancelledBySalon->value]);
    }

    public function down(): void
    {
        foreach (AppointmentStatus::legacyMap() as $old => $new) {
            DB::table('appointments')->where('status', $new)->update(['status' => $old]);
        }
    }
};
