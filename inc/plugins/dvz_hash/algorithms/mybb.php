<?php

namespace dvzHash\Algorithms;

abstract class mybb implements Algorithm
{
    public static function create(string $plaintext): array
    {
        return self::createWithParameters($plaintext);
    }

    public static function createWithParameters(string $plaintext, string $salt = null): array
    {
        if ($salt === null) {
            $salt = \generate_salt();
        }

        $hash = md5(md5($salt) . md5($plaintext));

        return [
            'salt' => $salt,
            'password' => $hash,
        ];
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $mirrorHash = self::createWithParameters($plaintext, $passwordFields['salt']);

        return \my_hash_equals($passwordFields['password'], $mirrorHash['password']);
    }

    public static function needsRehash(array $passwordFields): bool
    {
        return false;
    }
}
