<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Auditor
{
    public static function actor(): array
    {
        if (Auth::guard('company')->check()) {
            $u = Auth::guard('company')->user();
            return ['type' => 'company', 'id' => $u->id, 'name' => $u->localizedName()];
        }
        if (Auth::guard('owner')->check()) {
            $u = Auth::guard('owner')->user();
            return ['type' => 'owner', 'id' => $u->id, 'name' => $u->name ?? 'Owner'];
        }
        return ['type' => 'system', 'id' => 0, 'name' => 'System'];
    }

    public static function log(string $description, ?Model $subject = null, array $properties = []): void
    {
        $actor = static::actor();

        try {
            ActivityLog::create([
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'causer_type'  => $actor['type'],
                'causer_id'    => $actor['id'],
                'causer_name'  => $actor['name'],
                'properties'   => $properties ?: null,
                'ip_address'   => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Never let audit failure break the main flow
        }
    }

    public static function logChange(string $action, Model $subject, array $old, array $new): void
    {
        static::log($action, $subject, ['old' => $old, 'new' => $new]);
    }
}
