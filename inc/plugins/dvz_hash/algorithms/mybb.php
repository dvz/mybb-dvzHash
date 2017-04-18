<?php

namespace dvzHash\Algorithms;

abstract class mybb implements Algorithm
{
    public static function create(string $plaintext): array
    {
        return \create_password($plaintext, false, [
            'dvz_hash_bypass' => true,
        ]);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        return \verify_user_password(array_merge($passwordFields, [
            'dvz_hash_bypass' => true,
        ]), $plaintext);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        return false;
    }
}
