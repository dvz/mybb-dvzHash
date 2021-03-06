# DVZ Hash

While this plugin is considered stable, it is highly recommended that only webmasters familiar with principles of password hashing, encryption (if used) and MyBB's architecture & database structure maintain the plugin on live boards.

View [`SECURITY.md`](SECURITY.md) for a basic advisory on secure usage.

**Requirements:**
- MyBB >= 1.8.11
- PHP >= 7.0
  - PHP >= 7.2 for _Argon2i_
  - PHP >= 7.3 for _Argon2id_
  - PHP >= 7.4 for the benchmark tool (_Tools & Maintenance → DVZ Hash → Benchmark_)
- [defuse/php-encryption](https://github.com/defuse/php-encryption/) >= 2.0.0 for encryption features (included)

### Hashing
The plugin creates hooks in password-, login-, and user-related functions to take over the process of creating and verifying passwords. Changes included in MyBB 1.8.11 allow saving and receiving multiple fields from the `mybb_users` table &mdash; the plugin introduces `password_algorithm`, `password_encryption`, and  `password_downgraded` fields and extends the size of the `password` field to store its information.

Algorithms can be added by creating a class that implements the `dvzHash\Algorithms\Algorithm` interface in the `inc/plugins/dvz_hash/algorithms/` directory. The plugin needs to be reactivated to update the algorithm list in its settings.

#### On-the-fly rehashing
Having access to plaintext password values during login, the plugin can change the hashing algorithm according to the current _Preferred Algorithm_ setting value in the background.

#### Algorithm wrapping
It is possible to increase the hash strength of existing hashes without providing plaintext values. The plugin includes `mybb_*` algorithms that create and verify passwords using both the MyBB's default algorithm and additional ones on top.

#### Algorithm downgrades

The plugin allows administrators to set temporary passwords saved using the MyBB's default algorithm for user accounts, addressing compatibility issues (e.g. to allow them to log in during the upgrade process, where plugins cannot run).
The existing password's value will be moved to `mybb_users.password_downgraded` and the temporary password will be placed in `mybb_users.password`, allowing MyBB to handle the comparison.

### Encryption
The hashed passwords can be encrypted to provide an additional layer of protection (effective when values from the database are obtained by an adversary).

The plugin uses keys stored in the MyBB's PHP configuration file and environment variables to encrypt and decrypt password hashes with AES-256 when they are saved and read from the database, respectively. The key IDs associated with passwords are stored in the `mybb_users.password_encryption` field. When the _Hash encryption_ setting is set to _Yes_, the plugin will encrypt hashes using the key with the largest ID number.

The plugin's tools (Admin CP: ***Tools & Maintenance → DVZ Hash***) allow to add, remove and convert between encryption keys.

To generate an encryption key acquire the [defuse/php-encryption](https://github.com/defuse/php-encryption/releases) library by downloading the `defuse-crypto.phar` package (also included in plugin's repository) and run the PHP code in a secure environment:
```php
<?php

require 'defuse-crypto.phar';

$key = Defuse\Crypto\Key::createNewRandomKey();

echo $key->saveToAsciiSafeString();
```

The resulting ASCII representation can be saved in:

- ##### MyBB configuration file

  Add a `$config['dvz_hash']['password_encryption_keys']` array containing ASCII keys with key ID numbers as array keys to **inc/config.php**:

  ```php
  $config['dvz_hash']['password_encryption_keys'] = [
      1 => '...key 1...',
      3 => '...key 3...',
  ];
  ```

- ##### Environment variable

  Add a `dvz_hash_password_encryption_keys` environment variable containing ASCII keys separated by semicolons (`;`) to the server configuration. Content of some values can be omitted to provide values for keys with higher IDs.

  **Apache** servers:
  ```
  SetEnv dvz_hash_password_encryption_keys ...key 1...;;...key 3...
  ```
  **nginx** servers:
  ```
  fastcgi_param dvz_hash_password_encryption_keys "...key 1...;;...key 3...";
  ```
