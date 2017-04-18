<?php

namespace dvzHash\Algorithms;

abstract class mybb_bcrypt implements WrappableAlgorithm
{
    public static function create(string $plaintext): array
    {
        $passwordFields = \create_password($plaintext, false, [
            'dvz_hash_bypass' => true,
        ]);

        $hash = password_hash($passwordFields['password'], PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return array_merge($passwordFields, [
            'password' => $hash,
        ]);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $stringPrehashed = \create_password($plaintext, $passwordFields['salt'], [
            'dvz_hash_bypass' => true,
        ]);

        return password_verify($stringPrehashed['password'], $passwordFields['password']);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        $passwordInfo = password_get_info($passwordFields['password']);

        return (
            !isset($passwordInfo['options']['cost']) ||
            $passwordInfo['options']['cost'] < (int)\dvzHash\getSettingValue('bcrypt_cost')
        );
    }

    public static function wrap(array $passwordFields): array
    {
        $hash = password_hash($passwordFields['password'], PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return [
            'password' => $hash,
        ];
    }
}
