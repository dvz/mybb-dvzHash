<?php

namespace dvzHash\Algorithms;

abstract class sha512_bcrypt implements Algorithm
{
    public static function create(string $plaintext): array
    {
        $stringPrehashed = hash('sha512', $plaintext);

        $hash = password_hash($stringPrehashed, PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return [
            'password' => $hash,
        ];
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $stringPrehashed = hash('sha512', $plaintext);

        return password_verify($stringPrehashed, $passwordFields['password']);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        $passwordInfo = password_get_info($passwordFields['password']);

        return (
            !isset($passwordInfo['options']['cost']) ||
            $passwordInfo['options']['cost'] < (int)\dvzHash\getSettingValue('bcrypt_cost')
        );
    }
}
