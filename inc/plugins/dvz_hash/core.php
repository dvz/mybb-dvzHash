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
function getKnownAlgorithms(bool $includeWrappable = true): array
{
    static $algorithms = null;

    if ($algorithms === null) {
        $algorithmsPath = MYBB_ROOT . 'inc/plugins/dvz_hash/algorithms/';

        $filenames = scandir($algorithmsPath);

        $filenames = array_filter(
            $filenames,
            function ($filename) use ($algorithmsPath, $includeWrappable) {
                $className = 'dvzHash\\Algorithms\\' . basename($filename, '.php');

                return is_file($algorithmsPath . $filename) && class_exists($className) && (
                    $includeWrappable ||
                    !is_subclass_of($className, '\\dvzHash\\Algorithms\\WrappableAlgorithm')
                );
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

function getAlgorithmSelectString(bool $includeWrappable = true): string
{
    $algorithms = \dvzHash\getKnownAlgorithms($includeWrappable);

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

function getAlgorithmSmokeTestResults(): array
{
    $results = [];

    $algorithms = [
        'bcrypt' => [
            'testStringHash' => '$2y$04$J36x46Cyl6ZVZ5QgPrlVJ./K5UhiOozM8RYluTlZ/QujFYD/eBNTa',
            'randomStringOptions' => [
                'cost' => 4,
            ],
        ],
        'argon2id' => [
            'testStringHash' => '$argon2id$v=19$m=8,t=1,p=1$bXliYi5jb20$DlL1Gw',
            'randomStringOptions' => [
                'threads' => 1,
                'memory_cost' => 8,
                'time_cost' => 1,
            ],
        ],
    ];

    $testString = 'free never tasted so good';
    $randomString = \random_str();

    foreach ($algorithms as $algorithmName => $algorithm) {
        if (defined('PASSWORD_' . strtoupper($algorithmName))) {
            $result = password_get_info($algorithm['testStringHash'])['algoName'] === $algorithmName;
            $result &= password_verify($testString, $algorithm['testStringHash']);
            $result &= password_verify(
                $randomString,
                password_hash($randomString, PASSWORD_BCRYPT, $algorithm['randomStringOptions'])
            );

            $results[$algorithmName] = $result;
        }
    }

    return $results;
}

// common
function getSettingValue(string $name): string
{
    global $mybb;
    return $mybb->settings['dvz_hash_' . $name];
}

function getRenderedGraph(array $data, array $options = []): \Graph
{
    require_once MYBB_ROOT.'inc/class_graph.php';

    if (isset($options['image_width']) || isset($options['graph_width'])) {
        $graph = (new \ReflectionClass('Graph'))->newInstanceWithoutConstructor();

        if (isset($options['image_width'])) {
            $graph->img_width = $options['image_width'];
        }

        if (isset($options['graph_width'])) {
            $graph->inside_width = $options['graph_width'];
        }

        $graph->__construct();
    } else {
        $graph = new \Graph();
    }

    $graph->add_points($data);
    $graph->add_x_labels(
        array_keys($data)
    );

    if (isset($options['label'])) {
        $graph->set_bottom_label($options['label']);
    }

    $graph->render();

    return $graph;
}
