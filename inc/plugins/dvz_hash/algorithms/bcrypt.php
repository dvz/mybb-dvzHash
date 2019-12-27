<?php

namespace dvzHash\Algorithms;

abstract class bcrypt implements Algorithm
{
    public static function create(string $plaintext): array
    {
        $hash = password_hash($plaintext, PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return [
            'password' => $hash,
        ];
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        return password_verify($plaintext, $passwordFields['password']);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        $passwordInfo = password_get_info($passwordFields['password']);

        return (
            !isset($passwordInfo['options']['cost']) ||
            $passwordInfo['options']['cost'] != (int)\dvzHash\getSettingValue('bcrypt_cost')
        );
    }
}
