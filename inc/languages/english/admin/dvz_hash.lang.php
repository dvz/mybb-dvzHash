<?php

$l['dvz_hash_plugin_description'] = 'Upgrades the password hash algorithm and converts old passwords on the fly.';
$l['dvz_hash_uninstall_confirm_title'] = 'Confirm uninstallation';
$l['dvz_hash_uninstall_confirm_message'] = 'The plugin has been deactivated. Do you want to remove installed password-related fields from the database (this might result in corrupted password information)?';

$l['dvz_hash_admin_active_downgrades'] = '<p><em>DVZ Hash:</em> Administratively downgraded passwords exist and should be restored as soon as possible. <a href="{1}"><strong>Show details &rarr;</strong></a></p>';

$l['dvz_hash_admin'] = 'DVZ Hash';

$l['dvz_hash_admin_tab_overview'] = 'Overview';
$l['dvz_hash_admin_tab_overview_description'] = 'Here you can inspect the statistics of user password hashes and wrap hashing algorithms. Wrapped algorithms can be used to secure passwords hashed with outdated algorithms (such as MyBB\'s default), requiring to compute layered hashes when comparing passwords.';
$l['dvz_hash_admin_tab_encryption'] = 'Encryption';
$l['dvz_hash_admin_tab_encryption_description'] = 'Here you can manage and change the password hash encryption.';
$l['dvz_hash_admin_tab_downgrades'] = 'Downgrades';
$l['dvz_hash_admin_tab_downgrades_description'] = 'Here you can inspect temporary downgrades of the password algorithm to MyBB\'s default and without encryption. This tool should be only used for compatibility reasons (like logging in during the upgrade process) and the password should be restored as soon as possible.';

$l['dvz_hash_admin_algorithms_overview'] = 'Password algorithms overview';
$l['dvz_hash_admin_algorithm'] = 'Algorithm';
$l['dvz_hash_admin_algorithm_default'] = 'MyBB default';
$l['dvz_hash_admin_number_of_users'] = 'Number of users';
$l['dvz_hash_admin_algorithm_known'] = 'Recognized by DVZ Hash';

$l['dvz_hash_admin_wrap_algorithm'] = 'Password hash algorithm wrapping';
$l['dvz_hash_admin_wrap_algorithm_from'] = 'Algorithm in use';
$l['dvz_hash_admin_wrap_algorithm_to'] = 'Destination algorithm';
$l['dvz_hash_admin_wrap_algorithm_per_page'] = 'Users to wrap';
$l['dvz_hash_admin_wrap_algorithm_success'] = 'Password hash algorithm wrapping was completed successfully.';
$l['dvz_hash_admin_wrap_algorithm_error'] = 'Could not wrap selected password hash algorithms.';
$l['dvz_hash_admin_wrap_algorithm_not_possible'] = 'Selected password hash algorithms cannot be wrapped.';

$l['dvz_hash_admin_encryption'] = 'Password hash encryption';
$l['dvz_hash_admin_encryption_keys'] = 'Encryption keys';
$l['dvz_hash_admin_encryption_key_id'] = 'Key ID';
$l['dvz_hash_admin_no_encryption'] = 'No encryption';
$l['dvz_hash_admin_encryption_key_known'] = 'Recognized by DVZ Hash';
$l['dvz_hash_admin_encryption_key_location'] = 'Key location';
$l['dvz_hash_admin_encryption_key_location_config'] = 'MyBB configuration file';
$l['dvz_hash_admin_encryption_key_location_env'] = 'Server environment variable';
$l['dvz_hash_admin_no_encryption_keys'] = 'No encryption keys found.';

$l['dvz_hash_admin_encryption_conversion'] = 'Encryption conversion';
$l['dvz_hash_admin_encryption_conversion_from'] = 'From key ID';
$l['dvz_hash_admin_encryption_conversion_to'] = 'To key ID';
$l['dvz_hash_admin_encryption_conversion_per_page'] = 'Users to convert';
$l['dvz_hash_admin_encryption_conversion_success'] = 'Password hash encryption conversion was completed successfully.';
$l['dvz_hash_admin_encryption_conversion_error'] = 'Could not convert password hash encryption. Make sure that both keys are available.';

$l['dvz_hash_admin_downgrades'] = 'Downgraded passwords';
$l['dvz_hash_admin_no_downgrades'] = 'There are no administratively downgraded passwords.';
$l['dvz_hash_admin_downgrade_added'] = 'The password of selected user has been downgraded. Please remember to revert the downgrade once it is no longer needed.';
$l['dvz_hash_admin_downgrade_restored'] = 'The password of selected user has been restored.';
$l['dvz_hash_admin_downgrade'] = 'Create a downgraded password';
$l['dvz_hash_admin_downgrade_user'] = 'User';
$l['dvz_hash_admin_downgrade_user_description'] = 'Choose the account which password should be downgraded.';
$l['dvz_hash_admin_downgrade_password'] = 'Temporary password';
$l['dvz_hash_admin_downgrade_password_description'] = 'The password that will temporarily replace the current one. Do not use the current password here nor reuse this value anywhere.';
$l['dvz_hash_admin_downgrade_restore'] = 'Restore';
$l['dvz_hash_admin_no_perms_super_admin'] = 'You do not have permission to perform this operation because you are not a super administrator.';
$l['dvz_hash_admin_already_downgraded'] = 'The selected user\'s password is already downgraded.';

$l['dvz_hash_admin_user'] = 'User';
$l['dvz_hash_admin_controls'] = 'Controls';
$l['dvz_hash_admin_submit'] = 'Submit';


$l['setting_group_dvz_hash_desc'] = 'Settings for DVZ Hash.';

$l['setting_dvz_hash_preferred_algorithm'] = 'Preferred algorithm';
$l['setting_dvz_hash_preferred_algorithm_desc'] = 'Select an algorithm which password hashes should be kept in.';

$l['setting_dvz_hash_update_on_the_fly'] = 'Update on the fly';
$l['setting_dvz_hash_update_on_the_fly_desc'] = 'Choose whether the password algorithm should be updated once the raw password is provided.';

$l['setting_dvz_hash_bcrypt_cost'] = 'Default bcrypt cost';
$l['setting_dvz_hash_bcrypt_cost_desc'] = 'Choose the default cost for the bcrypt algorithm. Higher values provide better security but decrease performance. Values lower than 12 are not recommended.';

$l['setting_dvz_hash_encryption'] = 'Hash encryption';
$l['setting_dvz_hash_encryption_desc'] = 'Choose whether generated hashes should be stored encrypted. An encryption key must be available (see plugin documentation).';
