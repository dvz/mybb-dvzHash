<?php

namespace dvzHash\Algorithms;

abstract class mybb_bcrypt extends bcrypt implements WrappableAlgorithm
{
    public static function create(string $plaintext): array
    {
        $passwordFieldsPrehashed = \dvzHash\Algorithms\mybb::create($plaintext);
        $passwordFields = \dvzHash\Algorithms\bcrypt::create($passwordFieldsPrehashed['password']);

        return array_merge($passwordFieldsPrehashed, $passwordFields);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $passwordFieldsPrehashed = \dvzHash\Algorithms\mybb::createWithParameters($plaintext, $passwordFields['salt']);

        return \dvzHash\Algorithms\bcrypt::verify($passwordFieldsPrehashed['password'], $passwordFields);
    }

    public static function wrap(array $passwordFields): array
    {
        return \dvzHash\Algorithms\bcrypt::create($passwordFields['password']);
    }
}
