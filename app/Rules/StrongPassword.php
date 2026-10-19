<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (! is_string($value) || strlen($value) < 12) {
            return false;
        }
        if (! preg_match('/[A-Z]/', $value)) return false;
        if (! preg_match('/[a-z]/', $value)) return false;
        if (! preg_match('/[0-9]/', $value)) return false;
        if (! preg_match('/[^a-zA-Z0-9]/', $value)) return false;
        $blacklist = ['Password1!', 'Password123!', 'Admin@123'];
        if (in_array($value, $blacklist, true)) return false;
        if (preg_match('/(.)\1{3,}/', $value)) return false;
        return true;
    }

    public function message(): string
    {
        return 'The :attribute must be at least 12 characters with uppercase, lowercase, number and symbol.';
    }
}
