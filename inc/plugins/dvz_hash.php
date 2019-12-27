<?php
/**
 * Copyright (c) 2016-2019, Tomasz 'Devilshakerz' Mlynski [devilshakerz.com]
 *
 * Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby
 * granted, provided that the above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN
 * AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 */

// common modules
require MYBB_ROOT . 'inc/plugins/dvz_hash/core.php';
require MYBB_ROOT . 'inc/plugins/dvz_hash/core_encryption.php';
require MYBB_ROOT . 'inc/plugins/dvz_hash/algorithm_interface.php';
require MYBB_ROOT . 'inc/plugins/dvz_hash/wrappable_algorithm_interface.php';

// autoload algorithm classes
spl_autoload_register(function ($path) {
    $prefix = 'dvzHash\\Algorithms\\';
    $baseDir = MYBB_ROOT . 'inc/plugins/dvz_hash/algorithms/';

    if (strpos($path, $prefix) === 0) {
        $className = substr($path, strlen($prefix));
        $file = $baseDir . $className . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

// hooks
require MYBB_ROOT . 'inc/plugins/dvz_hash/hooks_frontend.php';

$plugins->add_hook('create_password', 'dvzHash\create_password');
$plugins->add_hook('verify_user_password', 'dvzHash\verify_user_password');
$plugins->add_hook('datahandler_user_insert', 'dvzHash\datahandler_user_insert');
$plugins->add_hook('datahandler_user_update', 'dvzHash\datahandler_user_update');
$plugins->add_hook('datahandler_login_complete_end', 'dvzHash\datahandler_login_complete_end');

if (defined('IN_ADMINCP')) {
    require MYBB_ROOT . 'inc/plugins/dvz_hash/hooks_acp.php';

    $plugins->add_hook('admin_load', 'dvzHash\admin_load');
    $plugins->add_hook('admin_home_index_output_message', 'dvzHash\admin_home_index_output_message');
    $plugins->add_hook('admin_config_plugins_begin', 'dvzHash\admin_config_plugins_begin');
    $plugins->add_hook('admin_tools_action_handler', 'dvzHash\admin_tools_action_handler');
    $plugins->add_hook('admin_tools_menu', 'dvzHash\admin_tools_menu');
}

// MyBB plugin system
function dvz_hash_info()
{
    global $lang;

    $lang->load('dvz_hash');

    return [
        'name'          => 'DVZ Hash',
        'description'   => $lang->dvz_hash_plugin_description,
        'website'       => 'https://devilshakerz.com',
        'author'        => 'Tomasz \'Devilshakerz\' Mlynski',
        'authorsite'    => 'https://devilshakerz.com',
        'version'       => '1.2',
        'codename'      => 'dvz_hash',
        'compatibility' => '18*',
    ];
}

function dvz_hash_install()
{
    global $db;

    // database
    if (!$db->field_exists('password_algorithm', 'users')) {
        $db->add_column('users', 'password_algorithm', "varchar(30) NOT NULL DEFAULT ''");
    }

    if (!$db->field_exists('password_encryption', 'users')) {
        $db->add_column('users', 'password_encryption', "smallint NOT NULL DEFAULT 0");
    }

    if (!$db->field_exists('password_downgraded', 'users')) {
        $db->add_column('users', 'password_downgraded', "varchar(500) NOT NULL DEFAULT ''");
    }

    $db->modify_column('users', 'password', 'varchar(500)');
}

function dvz_hash_uninstall()
{
    global $mybb, $db, $lang, $page, $codename;

    if ($mybb->request_method == 'post') {
        if ($mybb->get_input('no')) {
            admin_redirect('index.php?module=config-plugins');
        } else {
            // database
            $db->drop_column('users', 'password_algorithm');
            $db->drop_column('users', 'password_encryption');
            $db->drop_column('users', 'password_downgraded');
            $db->modify_column('users', 'password', 'varchar(120)');
        }
    } else {
        $title = $lang->dvz_hash_uninstall_confirm_title;
        $message = $lang->dvz_hash_uninstall_confirm_message;
        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=' . $codename, $message, $title);
    }
}

function dvz_hash_is_installed()
{
    global $db;
    return $db->field_exists('password_algorithm', 'users') && $db->field_exists('password_downgraded', 'users');
}

function dvz_hash_activate()
{
    global $db;

    // settings
    $settings = [
        [
            'name'        => 'dvz_hash_preferred_algorithm',
            'title'       => 'Preferred Algorithm',
            'description' => 'Select the algorithm that will be used for new passwords.',
            'optionscode' => 'select' . PHP_EOL . dvzHash\getAlgorithmSelectString(false),
            'value'       => 'mybb',
        ],
        [
            'name'        => 'dvz_hash_update_on_the_fly',
            'title'       => 'Update on the Fly',
            'description' => 'Choose whether the password algorithm should be changed to the preferred one when raw passwords are provided.',
            'optionscode' => 'yesno',
            'value'       => '0',
        ],
        [
            'name'        => 'dvz_hash_bcrypt_cost',
            'title'       => 'Default bcrypt cost',
            'description' => 'Choose the default cost for the bcrypt algorithm. Higher values provide better security but decrease performance. Values lower than 12 are not recommended.',
            'optionscode' => 'numeric
min=4
max=31',
            'value'       => '12',
        ],
        [
            'name'        => 'dvz_hash_argon2_memory_cost',
            'title'       => 'Default Argon2 memory cost',
            'description' => 'Choose the default memory usage in kibibytes (2<sup>n</sup>) for Argon2 algorithms. Higher values provide better security but decrease performance.',
            'optionscode' => 'numeric
min=3',
            'value'       => '16',
        ],
        [
            'name'        => 'dvz_hash_argon2_time_cost',
            'title'       => 'Default Argon2 time cost',
            'description' => 'Choose the default number of iterations for Argon2 algorithms. Higher values provide better security but decrease performance.',
            'optionscode' => 'numeric
min=1',
            'value'       => '4',
        ],
        [
            'name'        => 'dvz_hash_argon2_threads',
            'title'       => 'Default Argon2 parallelism',
            'description' => 'Choose the default number of threads for Argon2 algorithms.',
            'optionscode' => 'numeric
min=1',
            'value'       => '1',
        ],
        [
            'name'        => 'dvz_hash_encryption',
            'title'       => 'Hash Encryption',
            'description' => 'Choose whether generated hashes should be stored encrypted. An encryption key must be available (see plugin documentation for details).',
            'optionscode' => 'yesno',
            'value'       => '0',
        ],
    ];

    $settingGroupId = $db->insert_query('settinggroups', [
        'name'        => 'dvz_hash',
        'title'       => 'DVZ Hash',
        'description' => 'Settings for DVZ Hash.',
    ]);

    $i = 1;

    foreach ($settings as &$row) {
        $row['gid']         = $settingGroupId;
        $row['title']       = $db->escape_string($row['title']);
        $row['description'] = $db->escape_string($row['description']);
        $row['disporder']   = $i++;
    }

    $db->insert_query_multiple('settings', $settings);

    rebuild_settings();
}

function dvz_hash_deactivate()
{
    global $db;

    // settings
    $settingGroupId = $db->fetch_field(
        $db->simple_select('settinggroups', 'gid', "name='dvz_hash'"),
        'gid'
    );

    $db->delete_query('settinggroups', 'gid=' . (int)$settingGroupId);
    $db->delete_query('settings', 'gid=' . (int)$settingGroupId);

    rebuild_settings();
}
