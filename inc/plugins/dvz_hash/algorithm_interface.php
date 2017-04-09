<?php

namespace dvzHash\Algorithms;

interface Algorithm
{
    public static function create(string $string): array;
    public static function verify(string $string, array $user): bool;
    public static function needsRehash(array $user): bool;
}
