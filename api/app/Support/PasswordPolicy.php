<?php

namespace App\Support;

class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /**
     * @return list<string>
     */
    public static function usernameCandidates(string $rawIdentifier, ?string $email = null): array
    {
        $candidates = [];
        $local = LoginIdentifier::normalizeLocalPart($rawIdentifier);
        if ($local !== '') {
            $candidates[] = $local;
        }

        if ($email) {
            $emailLocal = LoginIdentifier::normalizeLocalPart($email);
            if ($emailLocal !== '' && ! in_array($emailLocal, $candidates, true)) {
                $candidates[] = $emailLocal;
            }
        }

        return $candidates;
    }

    /**
     * @param  list<string>  $usernames
     */
    public static function containsConsecutiveUsernameChars(string $password, array $usernames, int $length = 3): bool
    {
        $password = strtolower($password);

        foreach ($usernames as $username) {
            $username = strtolower($username);
            if (strlen($username) < $length) {
                continue;
            }

            for ($i = 0; $i <= strlen($username) - $length; $i++) {
                $substr = substr($username, $i, $length);
                if ($substr !== '' && str_contains($password, $substr)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function validateStrength(string $password, string $rawIdentifier, ?string $email = null): ?string
    {
        if (strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least 8 characters.';
        }

        if (! preg_match('/[a-z]/', $password)) {
            return 'Password must include upper and lower case letters, a number, and a special character.';
        }

        if (! preg_match('/[A-Z]/', $password)) {
            return 'Password must include upper and lower case letters, a number, and a special character.';
        }

        if (! preg_match('/[0-9]/', $password)) {
            return 'Password must include upper and lower case letters, a number, and a special character.';
        }

        if (! preg_match('/[\W_]/', $password)) {
            return 'Password must include upper and lower case letters, a number, and a special character.';
        }

        $usernames = self::usernameCandidates($rawIdentifier, $email);
        if (self::containsConsecutiveUsernameChars($password, $usernames)) {
            return 'Password must not contain 3 consecutive characters from your username.';
        }

        return null;
    }
}
