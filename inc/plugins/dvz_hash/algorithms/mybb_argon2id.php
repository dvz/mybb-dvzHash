<?php

namespace dvzHash\Algorithms;

abstract class mybb_argon2id extends argon2id implements WrappableAlgorithm
{
    public static function create(string $plaintext): array
    {
        $passwordFieldsPrehashed = \dvzHash\Algorithms\mybb::create($plaintext);
        $passwordFields = \dvzHash\Algorithms\argon2id::create($passwordFieldsPrehashed['password']);

        return array_merge($passwordFieldsPrehashed, $passwordFields);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $passwordFieldsPrehashed = \dvzHash\Algorithms\mybb::createWithParameters($plaintext, $passwordFields['salt']);

        return \dvzHash\Algorithms\argon2id::verify($passwordFieldsPrehashed['password'], $passwordFields);
    }

    public static function wrap(array $passwordFields): array
    {
        return \dvzHash\Algorithms\argon2id::create($passwordFields['password']);
    }
}
