<?php

namespace App\Services\Owner;

use App\Models\Owner;
use Illuminate\Support\Facades\Auth;

final class OwnerContext
{
    public function isPlatformOwner(): bool
    {
        return Auth::guard('owner')->check();
    }

    public function owner(): ?Owner
    {
        /** @var Owner|null */
        return Auth::guard('owner')->user();
    }
}
