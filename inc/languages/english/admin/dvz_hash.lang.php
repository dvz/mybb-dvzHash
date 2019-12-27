<?php

$l['dvz_hash_plugin_description'] = 'Upgrades the password hash algorithm and converts old passwords on the fly.';
$l['dvz_hash_uninstall_confirm_title'] = 'Confirm uninstallation';
$l['dvz_hash_uninstall_confirm_message'] = 'The plugin has been deactivated. Do you want to remove installed password-related fields from the database (this might result in corrupted password information)?';

$l['dvz_hash_admin_active_downgrades'] = '<p><em>DVZ Hash:</em> Administratively downgraded passwords exist and should be restored as soon as possible. <a href="{1}"><strong>Show details &rarr;</strong></a></p>';

$l['dvz_hash_admin'] = 'DVZ Hash';

$l['dvz_hash_admin_tab_algorithms'] = 'Algorithms';
$l['dvz_hash_admin_tab_algorithms_description'] = 'Here you can inspect the statistics of user password hashes and wrap hashing algorithms. Wrapped algorithms can be used to secure passwords hashed with outdated algorithms (such as MyBB\'s default), requiring to compute layered hashes when comparing passwords.';
$l['dvz_hash_admin_tab_encryption'] = 'Encryption';
$l['dvz_hash_admin_tab_encryption_description'] = 'Here you can manage and change the password hash encryption.';
$l['dvz_hash_admin_tab_downgrades'] = 'Downgrades';
$l['dvz_hash_admin_tab_downgrades_description'] = 'Here you can inspect temporary downgrades of the password algorithm to MyBB\'s default and without encryption. This tool should be only used for compatibility reasons (like logging in during the upgrade process) and the password should be restored as soon as possible.';
$l['dvz_hash_admin_tab_benchmark'] = 'Benchmark';
$l['dvz_hash_admin_tab_benchmark_description'] = 'Here you can inspect the performance of password hashing algorithms with specified parameters in current environment.<br /><br />Provide a <code>from..to</code> range in one of the parameters to measure execution time for values in between. Use the <code>from+step..to</code> format to use custom step values (e.g. <code>4+2..10</code> will test 4, 6, 8 and 10). Use the <code>from+step^startExponent..to</code> format to increment values exponentially.';

$l['dvz_hash_admin_algorithms_overview'] = 'Password Algorithms Overview';
$l['dvz_hash_admin_algorithm'] = 'Algorithm';
$l['dvz_hash_admin_algorithm_default'] = 'MyBB default';
$l['dvz_hash_admin_number_of_users'] = 'Number of Users';
$l['dvz_hash_admin_algorithm_known'] = 'Recognized by DVZ Hash';

$l['dvz_hash_admin_wrap_algorithm'] = 'Password Algorithm Wrapping';
$l['dvz_hash_admin_wrap_algorithm_from'] = 'Algorithm in Use';
$l['dvz_hash_admin_wrap_algorithm_to'] = 'Destination Algorithm';
$l['dvz_hash_admin_wrap_algorithm_per_page'] = 'Users to Wrap';
$l['dvz_hash_admin_wrap_algorithm_success'] = 'Password hash algorithm wrapping was completed successfully.';
$l['dvz_hash_admin_wrap_algorithm_error'] = 'Could not wrap selected password hash algorithms.';
$l['dvz_hash_admin_wrap_algorithm_not_possible'] = 'Selected password hash algorithms cannot be wrapped.';

$l['dvz_hash_admin_encryption'] = 'Password hash encryption';
$l['dvz_hash_admin_encryption_keys'] = 'Encryption Keys';
$l['dvz_hash_admin_encryption_key_id'] = 'Key ID';
$l['dvz_hash_admin_no_encryption'] = 'No encryption';
$l['dvz_hash_admin_encryption_key_known'] = 'Recognized by DVZ Hash';
$l['dvz_hash_admin_encryption_key_location'] = 'Key Location';
$l['dvz_hash_admin_encryption_key_location_config'] = 'MyBB Configuration file';
$l['dvz_hash_admin_encryption_key_location_env'] = 'Server environment variable';
$l['dvz_hash_admin_no_encryption_keys'] = 'No encryption keys found.';

$l['dvz_hash_admin_encryption_conversion'] = 'Encryption Conversion';
$l['dvz_hash_admin_encryption_conversion_from'] = 'From Key ID';
$l['dvz_hash_admin_encryption_conversion_to'] = 'To Key ID';
$l['dvz_hash_admin_encryption_conversion_per_page'] = 'Users to Convert';
$l['dvz_hash_admin_encryption_conversion_success'] = 'Password hash encryption conversion was completed successfully.';
$l['dvz_hash_admin_encryption_conversion_error'] = 'Could not convert password hash encryption. Make sure that both keys are available.';

$l['dvz_hash_admin_downgrades'] = 'Downgraded Passwords';
$l['dvz_hash_admin_no_downgrades'] = 'There are no administratively downgraded passwords.';
$l['dvz_hash_admin_downgrade_added'] = 'The password of selected user has been downgraded. Please remember to revert the downgrade once it is no longer needed.';
$l['dvz_hash_admin_downgrade_restored'] = 'The password of selected user has been restored.';
$l['dvz_hash_admin_downgrade'] = 'Create a Downgraded Password';
$l['dvz_hash_admin_downgrade_user'] = 'User';
$l['dvz_hash_admin_downgrade_user_description'] = 'Choose the account which password should be downgraded.';
$l['dvz_hash_admin_downgrade_password'] = 'Temporary Password';
$l['dvz_hash_admin_downgrade_password_description'] = 'The password that will temporarily replace the current one. Do not use the current password here nor reuse this value anywhere.';
$l['dvz_hash_admin_downgrade_restore'] = 'Restore';
$l['dvz_hash_admin_no_perms_super_admin'] = 'You do not have permission to perform this operation because you are not a super administrator.';
$l['dvz_hash_admin_already_downgraded'] = 'The selected user\'s password is already downgraded.';

$l['dvz_hash_admin_environment'] = 'Environment Information';
$l['dvz_hash_admin_memory_limit_php_ini'] = 'Memory Limit for Scripts &mdash; php.ini';
$l['dvz_hash_admin_max_execution_time'] = 'Maximum Execution Time (seconds) &mdash; php.ini';
$l['dvz_hash_admin_password_algorithms'] = 'PHP Password Algorithms';
$l['dvz_hash_admin_server_load'] = 'Server Load';
$l['dvz_hash_admin_smoke_test'] = '{1} Smoke Test';

$l['dvz_hash_admin_benchmark'] = 'Algorithm Benchmark';
$l['dvz_hash_admin_sample_size'] = 'Sample Size';
$l['dvz_hash_admin_sample_size_description'] = 'Choose how many times each parameter will be tested, resulting in an average of all tests. High values take more time to compute, but provide more accurate results.';
$l['dvz_hash_admin_time_limit'] = 'Maximum Execution Time (seconds)';
$l['dvz_hash_admin_time_limit_description'] = 'Override PHP\'s default execution time limit.';
$l['dvz_hash_admin_option_argon2id_memory_cost'] = 'Memory to use in kibibytes. Must be no lower than <code>8 * threads</code>.';
$l['dvz_hash_admin_benchmark_results'] = 'Benchmark Results';
$l['dvz_hash_admin_benchmark_results_label'] = 'Mean execution time (seconds) by parameter value';
$l['dvz_hash_admin_copy'] = 'Copy Text';
$l['dvz_hash_admin_copied'] = 'Copied to clipboard.';
$l['dvz_hash_admin_measurements'] = 'Measured {1} case(s) with sample size of {2} ({3} measurements total in {4} s).';
$l['dvz_hash_admin_interquartile_mean'] = 'interquartile mean';
$l['dvz_hash_admin_relative_standard_error'] = 'relative standard error';
$l['dvz_hash_admin_sample_coefficient_of_variation'] = 'coefficient of variation';
$l['dvz_hash_admin_sample_max'] = 'sample maximum value';
$l['dvz_hash_admin_sample_mean'] = 'sample mean';
$l['dvz_hash_admin_sample_median'] = 'sample median';
$l['dvz_hash_admin_sample_midrange'] = 'sample mid-range';
$l['dvz_hash_admin_sample_min'] = 'sample minimum value';
$l['dvz_hash_admin_sample_range'] = 'sample range';
$l['dvz_hash_admin_sample_standard_deviation'] = 'sample standard deviation';
$l['dvz_hash_admin_sample_sum'] = 'sample sum';
$l['dvz_hash_admin_sample_variance'] = 'sample variance';
$l['dvz_hash_admin_standard_error_of_the_mean'] = 'standard error of the mean';
$l['dvz_hash_admin_hashes_per_second'] = 'hashes per second';

$l['dvz_hash_admin_user'] = 'User';
$l['dvz_hash_admin_controls'] = 'Controls';
$l['dvz_hash_admin_submit'] = 'Submit';


$l['setting_group_dvz_hash_desc'] = 'Settings for DVZ Hash.';

$l['setting_dvz_hash_preferred_algorithm'] = 'Preferred Algorithm';
$l['setting_dvz_hash_preferred_algorithm_desc'] = 'Select the algorithm that will be used for new passwords.';

$l['setting_dvz_hash_update_on_the_fly'] = 'Update on the Fly';
$l['setting_dvz_hash_update_on_the_fly_desc'] = 'Choose whether the password algorithm should be changed to the preferred one when raw passwords are provided.';

$l['setting_dvz_hash_bcrypt_cost'] = 'Default bcrypt cost';
$l['setting_dvz_hash_bcrypt_cost_desc'] = 'Choose the default cost for the bcrypt algorithm. Higher values provide better security but decrease performance. Values lower than 12 are not recommended.';

$l['setting_dvz_hash_argon2_memory_cost'] = 'Default Argon2 memory cost';
$l['setting_dvz_hash_argon2_memory_cost_desc'] = 'Choose the default memory usage in kibibytes (2<sup>n</sup>) for Argon2 algorithms. Higher values provide better security but decrease performance.';

$l['setting_dvz_hash_argon2_time_cost'] = 'Default Argon2 time cost';
$l['setting_dvz_hash_argon2_time_cost_desc'] = 'Choose the default number of iterations for Argon2 algorithms. Higher values provide better security but decrease performance.';

$l['setting_dvz_hash_argon2_threads'] = 'Default Argon2 parallelism';
$l['setting_dvz_hash_argon2_threads_desc'] = 'Choose the default number of threads for Argon2 algorithms.';

$l['setting_dvz_hash_encryption'] = 'Hash Encryption';
$l['setting_dvz_hash_encryption_desc'] = 'Choose whether generated hashes should be stored encrypted. An encryption key must be available (see plugin documentation for details).';
