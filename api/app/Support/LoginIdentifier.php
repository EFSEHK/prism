<?php

namespace App\Support;

class LoginIdentifier
{
    public static function domain(): string
    {
        return (string) config('efsc.login_email_domain', 'efsc-ya.com');
    }

    /**
     * Strip hyphens/spaces, lowercase, and drop any @domain suffix.
     */
    public static function normalizeLocalPart(string $value): string
    {
        $value = trim(strtolower($value));

        if (str_contains($value, '@')) {
            $value = explode('@', $value, 2)[0];
        }

        return preg_replace('/[\s\-]/', '', $value) ?? '';
    }

    public static function emailFromLocalPart(string $localPart): string
    {
        return self::normalizeLocalPart($localPart).'@'.self::domain();
    }

    /**
     * Resolve a login identifier (admission no., CNIC, or email) to a stored email.
     * Appends @{domain} when the user omits it; full emails are kept as entered.
     */
    public static function resolveEmail(string $input): string
    {
        $input = trim(strtolower($input));

        if (str_contains($input, '@')) {
            return $input;
        }

        return self::emailFromLocalPart($input);
    }
}
