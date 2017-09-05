<?php

namespace dvzHash;

// operations
function hash(string $algorithm, string $plaintext): array
{
    $class = '\dvzHash\Algorithms\\' . $algorithm;

    $passwordFields = $class::create($plaintext);

    $passwordFields['password_algorithm'] = $algorithm;

    return $passwordFields;
}

function verify(string $algorithm, string $plaintext, array $passwordFields): bool
{
    $passwordFields = \dvzHash\unwrapPasswordFields($passwordFields);

    $class = '\dvzHash\Algorithms\\' . $algorithm;

    return $class::verify($plaintext, $passwordFields);
}

function needsRehash(string $algorithm, array $passwordFields): bool
{
    if ($passwordFields['password_downgraded']) {
        return false;
    } else {
        $data = $passwordFields;

        if ($passwordFields['password_encryption'] !== '0' && \dvzHash\encryptionKeyAvailable()) {
            $data['password'] = \dvzHash\decrypt($passwordFields['password'], $passwordFields['password_encryption']);
        }

        $class = '\dvzHash\Algorithms\\' . $algorithm;

        return $class::needsRehash($data);
    }
}

function wrapPasswordFields(array $passwordFields): array
{
    if (\dvzHash\encryptionEnabled() && \dvzHash\encryptionKeyAvailable()) {
        $encryptionData = \dvzHash\encrypt($passwordFields['password']);

        $passwordFields['password'] = $encryptionData['ciphertext'];
        $passwordFields['password_encryption'] = $encryptionData['key_id'];
    } else {
        $passwordFields['password_encryption'] = '0';
    }

    return $passwordFields;
}

function unwrapPasswordFields(array $passwordFields): array
{
    if ($passwordFields['password_encryption'] !== '0' && \dvzHash\encryptionKeyAvailable() && !$passwordFields['password_downgraded']) {
        $passwordFields['password'] = \dvzHash\decrypt($passwordFields['password'], $passwordFields['password_encryption']);
    }

    return $passwordFields;
}

function wrapAlgorithm(string $toAlgorithm, array $passwordFields): array
{
    if ($passwordFields['password_downgraded']) {
        return false;
    } else {
        $passwordFields = \dvzHash\unwrapPasswordFields($passwordFields);

        $class = '\dvzHash\Algorithms\\' . $toAlgorithm;

        $passwordFields = $class::wrap($passwordFields);

        $passwordFields['password_algorithm'] = $toAlgorithm;

        $passwordFields = \dvzHash\wrapPasswordFields($passwordFields);

        return $passwordFields;
    }
}

function wrapUserPasswordAlgorithm(string $fromAlgorithm, string $toAlgorithm, int $limit = null): bool
{
    global $db;

    if (!\dvzHash\algorithmsWrappable($fromAlgorithm, $toAlgorithm)) {
        return false;
    }

    if ($fromAlgorithm == 'mybb') {
        $algorithmIds = [
            '',
            'mybb',
        ];
    } else {
        $algorithmIds = [
            $fromAlgorithm
        ];
    }

    if ($limit) {
        $options = [
            'limit' => abs((int)$limit),
        ];
    } else {
        $options = [];
    }

    $algorithmIdsEscaped = array_map(function ($algorithmId) use ($db) {
        return "'" . $db->escape_string($algorithmId) . "'";
    }, $algorithmIds);

    $query = $db->simple_select('users', 'uid,password,password_encryption', "password_algorithm IN (" . implode(',', $algorithmIdsEscaped) . ") AND password_downgraded=''", $options);

    while ($row = $db->fetch_array($query)) {
        $passwordFields = \dvzHash\wrapAlgorithm($toAlgorithm, $row);
        $db->update_query('users', $passwordFields, 'uid=' . (int)$row['uid']);
    }

    return true;
}

function downgradeUserPassword(int $uid, string $plaintext): bool
{
    global $db;

    $data = \dvzHash\Algorithms\mybb::create($plaintext);

    $db->update_query('users', [
        'password_downgraded' => '`password`',
    ], 'uid=' . (int)$uid, false, true);

    if ($db->affected_rows()) {
        $db->update_query('users', $data, 'uid=' . (int)$uid);

        return $db->affected_rows() == 1;
    }

    return false;
}

function restoreDowngradedUserPassword(int $uid): bool
{
    global $db;

    $db->update_query('users', [
        'password' => '`password_downgraded`',
        'salt' => "'" . $db->escape_string(\generate_salt()) . "'",
        'password_downgraded' => "''",
    ], 'uid = ' . (int)$uid . " AND password_downgraded != ''", false, true);

    return $db->affected_rows() == 1;
}

// data
function getKnownAlgorithms(): array
{
    static $algorithms = null;

    if ($algorithms === null) {
        $algorithmsPath = MYBB_ROOT . 'inc/plugins/dvz_hash/algorithms/';

        $filenames = scandir($algorithmsPath);

        $filenames = array_filter(
            $filenames,
            function ($filename) use ($algorithmsPath) {
                return is_file($algorithmsPath . $filename) && class_exists('dvzHash\\Algorithms\\' . basename($filename, '.php'));
            }
        );

        $algorithms = str_replace('.php', null, $filenames);
    }

    return $algorithms;
}

function isKnownAlgorithm(string $algorithm): bool
{
    return in_array($algorithm, \dvzHash\getKnownAlgorithms());
}

function getAlgorithmSelectString(): string
{
    $algorithms = \dvzHash\getKnownAlgorithms();

    array_walk($algorithms, function (&$value) {
        $value = $value . '=' . $value;
    });

    return implode(PHP_EOL, $algorithms);
}

function getAlgorithmSelectArray(): array
{
    $algorithms = \dvzHash\getKnownAlgorithms();

    return array_combine($algorithms, $algorithms);
}

function getPreferredAlgorithm(): string
{
    $preferredAlgorithm = \dvzHash\getSettingValue('preferred_algorithm');

    if (\dvzHash\isKnownAlgorithm($preferredAlgorithm)) {
        return $preferredAlgorithm;
    } else {
        return 'mybb';
    }
}

function algorithmsWrappable(string $fromAlgorithm, string $toAlgorithm): bool
{
    return
        \dvzHash\isKnownAlgorithm($fromAlgorithm) &&
        \dvzHash\isKnownAlgorithm($toAlgorithm) &&
        strpos($toAlgorithm, $fromAlgorithm . '_') === 0
    ;
}

// common
function getSettingValue(string $name): string
{
    global $mybb;
    return $mybb->settings['dvz_hash_' . $name];
}
