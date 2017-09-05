<?php

namespace dvzHash\Algorithms;

abstract class mybb_argon2i implements WrappableAlgorithm
{
    public static function create(string $plaintext): array
    {
        $passwordFields = \dvzHash\Algorithms\mybb::create($plaintext);

        $hash = password_hash($passwordFields['password'], PASSWORD_ARGON2I, [
            'memory_cost' => 1 << (int)\dvzHash\getSettingValue('argon2i_memory_cost'),
            'time_cost' => (int)\dvzHash\getSettingValue('argon2i_time_cost'),
            'threads' => (int)\dvzHash\getSettingValue('argon2i_threads'),
        ]);

        return array_merge($passwordFields, [
            'password' => $hash,
        ]);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $stringPrehashed = \dvzHash\Algorithms\mybb::createWithParameters($plaintext, $passwordFields['salt']);

        return password_verify($stringPrehashed['password'], $passwordFields['password']);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        $passwordInfo = password_get_info($passwordFields['password']);

        return (
            !isset($passwordInfo['options']['memory_cost']) ||
            !isset($passwordInfo['options']['time_cost']) ||
            !isset($passwordInfo['options']['threads']) ||
            $passwordInfo['options']['memory_cost'] < (int)\dvzHash\getSettingValue('argon2i_memory_cost') ||
            $passwordInfo['options']['time_cost'] < (int)\dvzHash\getSettingValue('argon2i_time_cost') ||
            $passwordInfo['options']['threads'] < (int)\dvzHash\getSettingValue('argon2i_threads')
        );
    }

    public static function wrap(array $passwordFields): array
    {
        $hash = password_hash($passwordFields['password'], PASSWORD_ARGON2I, [
            'memory_cost' => 1 << (int)\dvzHash\getSettingValue('argon2i_memory_cost'),
            'time_cost' => (int)\dvzHash\getSettingValue('argon2i_time_cost'),
            'threads' => (int)\dvzHash\getSettingValue('argon2i_threads'),
        ]);

        return [
            'password' => $hash,
        ];
    }
}
