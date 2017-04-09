<?php

namespace dvzHash\Algorithms;

abstract class default_bcrypt implements WrappableAlgorithm
{
    public static function create(string $plaintext): array
    {
        $passwordFields = \dvzHash\createPasswordDefault($plaintext);

        $passwordFields = password_hash($passwordFields['password'], PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return $passwordFields;
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $stringPrehashed = \create_password($plaintext, $passwordFields['salt'], [
            'password_algorithm_force' => 'default',
        ]);

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

    public static function wrap(array $passwordFields): array
    {
        $passwordFields = password_hash($passwordFields['password'], PASSWORD_BCRYPT, [
            'cost' => (int)\dvzHash\getSettingValue('bcrypt_cost'),
        ]);

        return $passwordFields;
    }
}
