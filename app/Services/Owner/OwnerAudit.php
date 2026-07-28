<?php

namespace App\Services\Owner;

use App\Models\OwnerAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OwnerAudit
{
    /**
     * Record an owner-panel action.
     *
     * $old/$new: pass only the fields that changed. Sensitive attributes
     * (passwords, tokens) are stripped automatically.
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
        ?string $label = null,
    ): void {
        OwnerAuditLog::create([
            'owner_id'        => Auth::guard('owner')->id(),
            'action'          => $action,
            'auditable_type'  => $auditable?->getMorphClass(),
            'auditable_id'    => $auditable?->getKey(),
            'auditable_label' => $label ?? self::labelFor($auditable),
            'old_values'      => self::clean($old),
            'new_values'      => self::clean($new),
            'reason'          => $reason,
            'ip'              => request()->ip(),
        ]);
    }

    /** Diff helper: audit an update using the model's dirty state (call before save, after fill). */
    public static function recordChanges(string $action, Model $auditable, ?string $reason = null, ?string $label = null): void
    {
        $dirty = $auditable->getDirty();

        if ($dirty === []) {
            return;
        }

        $old = collect($dirty)->mapWithKeys(
            fn ($v, $key) => [$key => $auditable->getOriginal($key)]
        )->all();

        self::record($action, $auditable, $old, $dirty, $reason, $label);
    }

    private static function labelFor(?Model $auditable): ?string
    {
        if ($auditable === null) {
            return null;
        }

        if (method_exists($auditable, 'localizedName')) {
            return $auditable->localizedName();
        }

        return $auditable->name ?? $auditable->title ?? null;
    }

    private static function clean(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        unset($values['password'], $values['remember_token'], $values['_token']);

        return $values ?: null;
    }
}
