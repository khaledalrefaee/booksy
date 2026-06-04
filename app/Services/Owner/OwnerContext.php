<?php

namespace App\Services\Owner;

/**
 * Platform owner panel — full access to all companies, branches, and services.
 * Not scoped to a single company account.
 */
final class OwnerContext
{
    public function isPlatformOwner(): bool
    {
        return true;
    }
}
