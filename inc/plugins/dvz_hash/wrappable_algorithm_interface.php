<?php

namespace dvzHash\Algorithms;

interface WrappableAlgorithm extends Algorithm
{
    public static function wrap(array $user): array;
}
