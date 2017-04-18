<?php

namespace dvzHash;

// operations
function hash(string $algorithm, string $plaintext): array
{
    $class = '\dvzHash\Algorithms\\' . $algorithm;

    $passwordFields = $class::create($plaintext);

    $passwordFields = \dvzHash\wrapPasswordFields($passwordFields);

    if ($algorithm == 'mybb') {
        $passwordFields['password_algorithm'] = '';
    } else {
        $passwordFields['password_algorithm'] = $algorithm;
    }

    return $passwordFields;
}

function verify(string $algorithm, string $plaintext, array $passwordFields): bool
{

    if (!empty($passwordFields['password_downgraded'])) {
        $comparePasswordFields = \create_password($plaintext, $passwordFields['salt'], [
            'dvz_hash_bypass' => true,
        ]);

        $result = \my_hash_equals($passwordFields['password'], $comparePasswordFields['password']);
    } elseif (\dvzHash\isKnownAlgorithm($algorithm)) {
        $passwordFields = \dvzHash\unwrapPasswordFields($passwordFields);

        $class = '\dvzHash\Algorithms\\' . $algorithm;

        $result = $class::verify($plaintext, $passwordFields);
    } else {
        $result = null;
    }

    return $result;
}

function needsRehash(string $algorithm, array $passwordFields): bool
{
    $passwordFields = \dvzHash\unwrapPasswordFields($passwordFields);

    $class = '\dvzHash\Algorithms\\' . $algorithm;

    return $class::needsRehash($passwordFields);
}

function wrapAlgorithm(string $toAlgorithm, array $passwordFields): array
{
    if ($passwordFields['password_downgraded']) {
        return false;
    } else {
        $passwordFields = \dvzHash\unwrapPasswordFields($passwordFields);

        $class = '\dvzHash\Algorithms\\' . $toAlgorithm;

        $passwordFields = $class::wrap($passwordFields);

        if ($toAlgorithm == 'mybb') {
            $passwordFields['password_algorithm'] = '';
        } else {
            $passwordFields['password_algorithm'] = $toAlgorithm;
        }

        $passwordFields = \dvzHash\wrapPasswordFields($passwordFields);

        return $passwordFields;
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

function downgradeUserPassword(int $uid, string $plaintext): bool
{
    global $db;

    $data = \create_password($plaintext, false, [
        'dvz_hash_bypass' => true,
    ]);

    $db->update_query('users', [
        'password_downgraded' => 'CONCAT(`password`, \':SALT:\', `salt`)',
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

    if ($user = \get_user($uid)) {
        $values = explode(':SALT:', $user['password_downgraded']);

        if (isset($values[1])) {
            $salt = $values[1];
        } else {
            $salt = generate_salt();
        }

        $db->update_query('users', [
            'password' => $db->escape_string($values[0]),
            'salt' => $db->escape_string($salt),
            'password_downgraded' => '',
        ], 'uid = ' . (int)$uid . " AND password_downgraded != ''");

        return $db->affected_rows() == 1;
    } else {
        return false;
    }
}

function wrapUserPasswordAlgorithm(string $fromAlgorithm, string $toAlgorithm, int $limit = null): bool
{
    global $db;

    if (!\dvzHash\algorithmsWrappable($fromAlgorithm, $toAlgorithm)) {
        return false;
    }

    if ($fromAlgorithm == 'mybb') {
        $algorithmId = '';
    } else {
        $algorithmId = $fromAlgorithm;
    }

    if ($limit) {
        $options = [
            'limit' => abs((int)$limit),
        ];
    } else {
        $options = [];
    }

    $query = $db->simple_select('users', 'uid,password,password_encryption', "password_algorithm='" . $db->escape_string($algorithmId) . "' AND password_downgraded=''", $options);

    while ($row = $db->fetch_array($query)) {
        $passwordFields = \dvzHash\wrapAlgorithm($toAlgorithm, $row);

        $db->update_query('users', $passwordFields, 'uid=' . (int)$row['uid']);
    }

    return true;
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
                return is_file($algorithmsPath . $filename);
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
