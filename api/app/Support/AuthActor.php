<?php

namespace App\Support;

use App\Models\User;

class AuthActor
{
    public static function user(): ?User
    {
        if (app()->bound('auth.actor')) {
            return app('auth.actor');
        }

        return auth()->user();
    }

    public static function canManageUsers(): bool
    {
        $actor = self::user();

        return $actor?->hasAnyRole(['superadmin', 'admin', 'computer_operator']) ?? false;
    }

    public static function canEditUsers(): bool
    {
        $actor = self::user();

        return $actor?->hasAnyRole(['superadmin', 'admin']) ?? false;
    }

    public static function canImpersonateUsers(): bool
    {
        $actor = self::user();

        return $actor?->hasRole('superadmin') ?? false;
    }
}
