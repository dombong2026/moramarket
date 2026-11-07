<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class EmailNormalizer
{
    /**
     * Use the same canonical email form when checking uniqueness, signing in,
     * sending reset links and redeeming reset tokens.
     *
     * Only normalize valid scalar text. Leave malformed inputs for Laravel's
     * request validation to reject (do not cast arrays into the string "Array").
     */
    public static function normalizeRequest(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }
    }
}
