<?php

namespace dvzHash;

// operations
function encrypt(string $plaintext, int $keyId = null): array
{
    require_once MYBB_ROOT . 'inc/3rdparty/defuse-crypto.phar';

    if ($keyId === null) {
        $keyId = \dvzHash\getDefaultEncryptionKeyId();
    }

    $key = \dvzHash\getEncryptionKey($keyId);

    return [
        'ciphertext' => \Defuse\Crypto\Crypto::encrypt($plaintext, $key),
        'key_id' => $keyId,
    ];
}

function decrypt(string $ciphertext, int $keyId): string
{
    require_once MYBB_ROOT . 'inc/3rdparty/defuse-crypto.phar';

    try {
        return \Defuse\Crypto\Crypto::decrypt($ciphertext, \dvzHash\getEncryptionKey($keyId));
    } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
        return false;
    }
}

function convertUserPasswordEncryption(int $fromKey, int $toKey, int $limit = null): bool
{
    global $db;

    require_once MYBB_ROOT . 'inc/3rdparty/defuse-crypto.phar';

    if (
        !($fromKey === 0 || \dvzHash\testEncryptionKey($fromKey)) ||
        !($toKey === 0 || \dvzHash\testEncryptionKey($toKey))
    ) {
        return false;
    }

    if ($limit) {
        $options = [
            'limit' => abs((int)$limit),
        ];
    } else {
        $options = [];
    }

    $query = $db->simple_select('users', 'uid,password,password_encryption', 'password_encryption = ' . (int)$fromKey, $options);

    while ($row = $db->fetch_array($query)) {
        if ($row['password_encryption'] === '0') {
            $rawValue = $row['password'];
        } else {
            $rawValue = \dvzHash\decrypt($row['password'], $row['password_encryption']);
        }

        if ($toKey === 0) {
            $newValue = $rawValue;
            $newKeyId = 0;
        } else {
            $encryptionData = \dvzHash\encrypt($rawValue, $toKey);
            $newValue = $encryptionData['ciphertext'];
            $newKeyId = (int)$encryptionData['key_id'];
        }

        $db->update_query('users', [
            'password' => $newValue,
            'password_encryption' => $newKeyId,
        ], 'uid=' . (int)$row['uid']);
    }

    return true;
}

// data
function getEncryptionKey(int $keyId): \Defuse\Crypto\Key
{
    global $mybb, $error_handler;

    require_once MYBB_ROOT . 'inc/3rdparty/defuse-crypto.phar';

    $asciiValues = \dvzHash\getEncryptionKeyAsciiValues();

    if ($asciiValues) {
        if (isset($asciiValues[$keyId])) {
            $asciiValue = $asciiValues[$keyId];
        } else {
            $error_handler->trigger('Could not find matching encryption key.', E_USER_ERROR);
            return false;
        }

        try {
            $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($asciiValue);
        } catch (\Exception $e) {
            $error_handler->trigger('Could not load the encryption key.', E_USER_ERROR);
            return false;
        }

        return $key;
    } else {
        $error_handler->trigger('No encryption keys found.', E_USER_ERROR);
        return false;
    }
}

function testEncryptionKey(int $keyId = null): bool
{
    global $mybb;

    if ($keyId === null) {
        $keyId = \dvzHash\getDefaultEncryptionKeyId();
    }

    if ($keyId == 0) {
        return false;
    }

    try {
        \dvzHash\getEncryptionKey($keyId);
    } catch (\Exception $e) {
        return false;
    }

    return true;
}

function getDefaultEncryptionKeyId(): int
{
    $asciiValues = \dvzHash\getEncryptionKeyAsciiValues();

    if ($asciiValues) {
        return max(array_keys($asciiValues));
    } else {
        return 0;
    }
}

function getEncryptionKeyAsciiValues(): array
{
    static $asciiValues = null;

    if ($asciiValues === null) {
        $asciiValues =
            \dvzHash\getEncryptionKeyAsciiValuesFromConfigFile() +
            \dvzHash\getEncryptionKeyAsciiValuesFromEnv()
        ;
    }

    return $asciiValues;
}

function getEncryptionKeyAsciiValuesFromConfigFile(): array
{
    global $mybb;

    if (
        isset($mybb->config['dvz_hash']['password_encryption_keys']) &&
        $variable = $mybb->config['dvz_hash']['password_encryption_keys']
    ) {
        if (is_array($variable)) {
            return array_filter($variable);
        }
    }

    return [];
}

function getEncryptionKeyAsciiValuesFromEnv(): array
{
    if ($value = getenv('dvz_hash_password_encryption_keys')) {
        $array = explode(';', $value);

        return array_filter(
            array_combine(
                range(1, count($array)),
                array_values($array)
            )
        );
    } else {
        return [];
    }
}

function encryptionEnabled(): bool
{
    return \dvzHash\getSettingValue('encryption');
}

function encryptionKeyAvailable(): bool
{
    $asciiValues = \dvzHash\getEncryptionKeyAsciiValues();

    return !empty($asciiValues);
}
