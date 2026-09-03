<?php

namespace App\Rules;

use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function __construct(
        private string $identifier,
        private ?string $resolvedEmail = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Password must be at least 8 characters.');

            return;
        }

        $message = PasswordPolicy::validateStrength($value, $this->identifier, $this->resolvedEmail);
        if ($message) {
            $fail($message);
        }
    }
}
