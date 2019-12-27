<?php

namespace dvzHash\Algorithms;

abstract class argon2i implements Algorithm
{
    public static function create(string $plaintext): array
    {
        $hash = password_hash($plaintext, PASSWORD_ARGON2I, [
            'memory_cost' => 1 << (int)\dvzHash\getSettingValue('argon2_memory_cost'),
            'time_cost' => (int)\dvzHash\getSettingValue('argon2_time_cost'),
            'threads' => (int)\dvzHash\getSettingValue('argon2_threads'),
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
            !isset($passwordInfo['options']['memory_cost']) ||
            !isset($passwordInfo['options']['time_cost']) ||
            !isset($passwordInfo['options']['threads']) ||
            $passwordInfo['options']['memory_cost'] != 1 << (int)\dvzHash\getSettingValue('argon2_memory_cost') ||
            $passwordInfo['options']['time_cost'] != (int)\dvzHash\getSettingValue('argon2_time_cost') ||
            $passwordInfo['options']['threads'] != (int)\dvzHash\getSettingValue('argon2_threads')
        );
    }
}
