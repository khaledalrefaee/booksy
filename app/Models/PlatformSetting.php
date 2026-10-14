<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of the platform key/value store. Read and written ONLY through
 * App\Services\Platform\PlatformSettings (which owns casting and cache
 * invalidation) — never use this model directly in views.
 */
class PlatformSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'updated_by'];
}
