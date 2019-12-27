<?php

namespace dvzHash\Algorithms;

abstract class sha512_bcrypt extends bcrypt implements Algorithm
{
    public static function create(string $plaintext): array
    {
        $stringPrehashed = hash('sha512', $plaintext);

        return \dvzHash\Algorithms\bcrypt::create($stringPrehashed);
    }

    public static function verify(string $plaintext, array $passwordFields): bool
    {
        $stringPrehashed = hash('sha512', $plaintext);

        return \dvzHash\Algorithms\bcrypt::verify($stringPrehashed, $passwordFields);
    }
}
