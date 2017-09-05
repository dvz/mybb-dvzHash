<?php

namespace dvzHash;

function admin_load()
{
    global $mybb, $cache, $db, $lang, $run_module, $action_file, $page, $sub_tabs;

    if ($run_module == 'tools' && $action_file == 'dvz_hash') {
        $lang->load('dvz_hash');

        $page->add_breadcrumb_item($lang->dvz_hash_admin, 'index.php?module=config-dvz_hash');

        $sub_tabs['overview'] = [
            'link'        => 'index.php?module=tools-dvz_hash&action=overview',
            'title'       => $lang->dvz_hash_admin_tab_overview,
            'description' => $lang->dvz_hash_admin_tab_overview_description,
        ];
        $sub_tabs['encryption'] = [
            'link'        => 'index.php?module=tools-dvz_hash&action=encryption',
            'title'       => $lang->dvz_hash_admin_tab_encryption,
            'description' => $lang->dvz_hash_admin_tab_encryption_description,
        ];
        $sub_tabs['downgrades'] = [
            'link'        => 'index.php?module=tools-dvz_hash&action=downgrades',
            'title'       => $lang->dvz_hash_admin_tab_downgrades,
            'description' => $lang->dvz_hash_admin_tab_downgrades_description,
        ];

        if ($mybb->input['action'] == 'overview' || empty($mybb->input['action'])) {
            if ($mybb->get_input('wrap_algorithm') && $mybb->get_input('algorithm') != $mybb->get_input('to_algorithm')) {
                if (\dvzHash\algorithmsWrappable($mybb->get_input('algorithm'), $mybb->get_input('to_algorithm'))) {
                    if (\is_super_admin($mybb->user['uid'])) {
                        if (\dvzHash\wrapUserPasswordAlgorithm(
                            $mybb->get_input('algorithm'),
                            $mybb->get_input('to_algorithm'),
                            $mybb->get_input('per_page', \MyBB::INPUT_INT)
                        )) {
                            \flash_message($lang->dvz_hash_admin_wrap_algorithm_success, 'success');
                        } else {
                            \flash_message($lang->dvz_hash_admin_wrap_algorithm_error, 'error');
                        }
                    } else {
                        \flash_message($lang->dvz_hash_admin_no_perms_super_admin, 'error');
                    }
                } else {
                    \flash_message($lang->dvz_hash_admin_wrap_algorithm_not_possible, 'error');
                }
            }

            $page->output_header($lang->dvz_hash_admin);
            $page->output_nav_tabs($sub_tabs, 'overview');

            $wrapCandidateAlgorithms = [];

            $query = $db->simple_select('users', 'COUNT(uid) AS n, password_algorithm', '', [
                'group_by' => 'password_algorithm',
            ]);

            $table = new \Table;
            $table->construct_header($lang->dvz_hash_admin_algorithm, ['width' => '40%', 'class' =>  'align_center']);
            $table->construct_header($lang->dvz_hash_admin_number_of_users, ['width' => '25%', 'class' => 'align_center']);
            $table->construct_header($lang->dvz_hash_admin_algorithm_known, ['width' => '25%', 'class' => 'align_center']);

            while ($row = $db->fetch_array($query)) {
                if (in_array($row['password_algorithm'], ['', 'mybb'])) {
                    $name = $lang->dvz_hash_admin_algorithm_default;
                    $wrapCandidateAlgorithms[] = 'mybb';
                } else {
                    $name = htmlspecialchars_uni($row['password_algorithm']);
                    $wrapCandidateAlgorithms[] = $row['password_algorithm'];
                }

                if (\dvzHash\isKnownAlgorithm($row['password_algorithm'])) {
                    $recognized = $lang->yes;
                } else {
                    $recognized = $lang->no;
                }

                $table->construct_cell($name, ['class' => 'align_center']);
                $table->construct_cell((int)$row['n'], ['class' => 'align_center']);
                $table->construct_cell($recognized, ['class' => 'align_center']);
                $table->construct_row();
            }


            $table->output($lang->dvz_hash_admin_algorithms_overview);

            // conversion
            $form = new \Form('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']), 'post');
            $form_container = new \FormContainer($lang->dvz_hash_admin_wrap_algorithm);

            $form_container->output_row_header($lang->dvz_hash_admin_wrap_algorithm_from, ['style' => 'width: 40%;']);
            $form_container->output_row_header($lang->dvz_hash_admin_wrap_algorithm_to, ['style' => 'width: 40%;']);
            $form_container->output_row_header($lang->dvz_hash_admin_wrap_algorithm_per_page, ['style' => 'width: 30%;']);
            $form_container->output_row_header('&nbsp;', ['style' => 'width: 10%;']);

            $form_container->output_cell($form->generate_select_box('algorithm', array_combine($wrapCandidateAlgorithms, $wrapCandidateAlgorithms)));
            $form_container->output_cell($form->generate_select_box('to_algorithm', \dvzHash\getAlgorithmSelectArray()));
            $form_container->output_cell($form->generate_numeric_field('per_page', 100, ['style' => 'width: 150px;', 'min' => 0]));
            $form_container->output_cell($form->generate_submit_button($lang->go, ['name' => 'wrap_algorithm']));
            $form_container->construct_row();

            $form_container->end();
            $form->end();

        } elseif ($mybb->input['action'] == 'encryption') {
            if ($mybb->get_input('convert') && $mybb->get_input('from_key') != $mybb->get_input('to_key')) {
                if (\is_super_admin($mybb->user['uid'])) {
                    if (\dvzHash\convertUserPasswordEncryption(
                        $mybb->get_input('from_key', \MyBB::INPUT_INT),
                        $mybb->get_input('to_key', \MyBB::INPUT_INT),
                        $mybb->get_input('per_page', \MyBB::INPUT_INT)
                    )) {
                        \flash_message($lang->dvz_hash_admin_encryption_conversion_success, 'success');
                    } else {
                        \flash_message($lang->dvz_hash_admin_encryption_conversion_error, 'error');
                    }
                } else {
                    \flash_message($lang->dvz_hash_admin_no_perms_super_admin, 'error');
                }
            }

            $page->output_header($lang->dvz_hash_admin_encryption);
            $page->output_nav_tabs($sub_tabs, 'encryption');

            // keys
            $keyLocations = [
                0 => null,
            ];
            $keyUsage = [];
            $keyOptions = [
                '0' => $lang->dvz_hash_admin_no_encryption,
            ];

            foreach (\dvzHash\getEncryptionKeyAsciiValuesFromConfigFile() as $keyId => $key) {
                if ($key) {
                    $keyLocations[ (int)$keyId ] = 'config';
                }
            }

            foreach (\dvzHash\getEncryptionKeyAsciiValuesFromEnv() as $keyId => $key) {
                if ($key) {
                    $keyLocations[ (int)$keyId ] = 'env';
                }
            }

            $query = $db->simple_select('users', 'COUNT(uid) AS n, password_encryption', '', [
                'group_by' => 'password_encryption',
            ]);

            if ($db->num_rows($query)) {
                while ($row = $db->fetch_array($query)) {
                    $keyUsage[ (int)$row['password_encryption'] ] = $row['n'];
                }
            }

            $keyToDisplay = array_unique(
                array_merge(
                    array_keys($keyLocations),
                    array_keys($keyUsage)
                )
            );

            $table = new \Table;
            $table->construct_header($lang->dvz_hash_admin_encryption_key_id, ['width' => '20%', 'class' =>  'align_center']);
            $table->construct_header($lang->dvz_hash_admin_encryption_key_known, ['width' => '30%', 'class' => 'align_center']);
            $table->construct_header($lang->dvz_hash_admin_encryption_key_location, ['width' => '30%', 'class' => 'align_center']);
            $table->construct_header($lang->dvz_hash_admin_number_of_users, ['width' => '20%', 'class' => 'align_center']);

            if ($keyToDisplay) {
                foreach ($keyToDisplay as $keyId) {
                    if ($keyId === 0) {
                        $id = $lang->dvz_hash_admin_no_encryption;
                        $recognized = $lang->na;
                        $location = $lang->na;
                    } else {
                        $id = (int)$keyId;

                        if (isset($keyLocations[$keyId])) {
                            $keyOptions[$keyId] = $keyId;
                            $recognized = $lang->yes;

                            switch ($keyLocations[$keyId]) {
                                case 'config':
                                    $location = $lang->dvz_hash_admin_encryption_key_location_config;
                                    break;
                                case 'env':
                                    $location = $lang->dvz_hash_admin_encryption_key_location_env;
                                    break;
                            }
                        } else {
                            $recognized = $lang->no;
                            $location = $lang->na;
                        }
                    }

                    if (isset($keyUsage[$keyId])) {
                        $usage = $keyUsage[$keyId];
                    } else {
                        $usage = 0;
                    }

                    $table->construct_cell($id, ['class' => 'align_center']);
                    $table->construct_cell($recognized, ['class' => 'align_center']);
                    $table->construct_cell($location, ['class' => 'align_center']);
                    $table->construct_cell($usage, ['class' => 'align_center']);
                    $table->construct_row();
                }
            } else {
                $table->construct_cell($lang->dvz_hash_admin_no_encryption_keys, ['colspan' => '3', 'class' =>  'align_center']);
                $table->construct_row();
            }

            $table->output($lang->dvz_hash_admin_encryption_keys);

            // conversion
            $form = new \Form('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']), 'post');
            $form_container = new \FormContainer($lang->dvz_hash_admin_encryption_conversion);

            $form_container->output_row_header($lang->dvz_hash_admin_encryption_conversion_from, ['style' => 'width: 40%;']);
            $form_container->output_row_header($lang->dvz_hash_admin_encryption_conversion_to, ['style' => 'width: 40%;']);
            $form_container->output_row_header($lang->dvz_hash_admin_encryption_conversion_per_page, ['style' => 'width: 30%;']);
            $form_container->output_row_header('&nbsp;', ['style' => 'width: 10%;']);

            $form_container->output_cell($form->generate_select_box('from_key', $keyOptions));
            $form_container->output_cell($form->generate_select_box('to_key', $keyOptions));
            $form_container->output_cell($form->generate_numeric_field('per_page', 500, ['style' => 'width: 150px;', 'min' => 0]));
            $form_container->output_cell($form->generate_submit_button($lang->go, ['name' => 'convert']));
            $form_container->construct_row();

            $form_container->end();
            $form->end();

        } elseif ($mybb->input['action'] == 'downgrades') {
            if ($mybb->get_input('restore') && \verify_post_check($mybb->get_input('my_post_key'))) {
                $user = get_user($mybb->get_input('restore', \MyBB::INPUT_INT));

                if ($user && $user['password_downgraded']) {
                    if (\is_super_admin($mybb->user['uid']) || !\is_super_admin($user['uid'])) {
                        if (\dvzHash\restoreDowngradedUserPassword($user['uid'])) {
                            \flash_message($lang->dvz_hash_admin_downgrade_restored, 'success');
                            \admin_redirect('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']));
                        }
                    } else {
                        \flash_message($lang->dvz_hash_admin_no_perms_super_admin, 'error');
                        \admin_redirect('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']));
                    }
                }

                \flash_message($lang->dvz_hash_downgrade_reverted, 'success');
            } else {
                if ($mybb->request_method == 'post' && $mybb->get_input('downgrade')) {
                    $user = \get_user_by_username($mybb->get_input('downgrade_username'), [
                        'fields' => [
                            'password_downgraded',
                            'password_algorithm',
                        ],
                    ]);

                    if ($user) {
                        if (!$user['password_downgraded'] && !in_array($user['password_algorithm'], ['', 'mybb'])) {
                            if (\is_super_admin($mybb->user['uid']) || !\is_super_admin($user['uid'])) {
                                if (\dvzHash\downgradeUserPassword($user['uid'], $mybb->get_input('password'))) {
                                    \flash_message($lang->dvz_hash_admin_downgrade_added, 'success');
                                    \admin_redirect('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']));
                                }
                            } else {
                                \flash_message($lang->dvz_hash_admin_no_perms_super_admin, 'error');
                                \admin_redirect('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']));
                            }
                        } else {
                            \flash_message($lang->dvz_hash_admin_already_downgraded, 'error');
                            \admin_redirect('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']));
                        }
                    }
                }

                $page->output_header($lang->dvz_hash_admin_downgrades);
                $page->output_nav_tabs($sub_tabs, 'downgrades');

                // list
                $query = $db->simple_select('users', 'uid,username', "password_downgraded != ''");

                $table = new \Table;
                $table->construct_header($lang->dvz_hash_admin_user, ['width' => '80%', 'class' =>  'align_center']);
                $table->construct_header($lang->dvz_hash_admin_controls, ['width' => '20%', 'class' => 'align_center']);

                if ($db->num_rows($query) != 0) {
                    while ($user = $db->fetch_array($query)) {
                        $profileLink = '<a href="index.php?module=user-users&amp;action=edit&amp;uid=' . $user['uid'] . '">' . \htmlspecialchars_uni($user['username']) . '</a>';
                        $controls = '<a href="index.php?module=' . $run_module . '-' . $action_file . '&amp;action=' . urlencode($mybb->input['action']) . '&amp;restore=' . (int)$user['uid'] . '&amp;my_post_key=' . $mybb->post_code . '">' . $lang->dvz_hash_admin_downgrade_restore . '</a>';

                        $table->construct_cell($profileLink, ['class' => 'align_center']);
                        $table->construct_cell($controls, ['class' => 'align_center']);
                        $table->construct_row();
                    }
                } else {
                    $table->construct_cell($lang->dvz_hash_admin_no_downgrades, ['colspan' => '3', 'class' =>  'align_center']);
                    $table->construct_row();
                }

                $table->output($lang->dvz_hash_admin_downgrades);

                // add form
                $form = new \Form('index.php?module=' . $run_module . '-' . $action_file . '&action=' . urlencode($mybb->input['action']) . '&downgrade=1', 'post');

                $form_container = new \FormContainer($lang->dvz_hash_admin_downgrade);
                $form_container->output_row(
                    $lang->dvz_hash_admin_downgrade_user,
                    $lang->dvz_hash_admin_downgrade_user_description,
                    $form->generate_text_box('downgrade_username', null, ['id' => 'downgrade_username']),
                    'downgrade_username'
                );
                $form_container->output_row(
                    $lang->dvz_hash_admin_downgrade_password,
                    $lang->dvz_hash_admin_downgrade_password_description,
                    '<input type="text" name="password" value="' . random_str(40) . '" class="text_input" autocomplete="off" onfocus="this.select()" />'
                );
                $form_container->end();

                echo '
<link rel="stylesheet" href="../jscripts/select2/select2.css">
<script type="text/javascript" src="../jscripts/select2/select2.min.js?ver=1804"></script>
<script type="text/javascript">
<!--
$("#downgrade_username").select2({
placeholder: "' . $lang->search_for_a_user . '",
minimumInputLength: 2,
multiple: false,
ajax: {
    url: "../xmlhttp.php?action=get_users",
    dataType: \'json\',
    data: function (term, page) {
        return {
            query: term,
        };
    },
    results: function (data, page) {
        return { results: data };
    }
},
initSelection: function(element, callback) {
    var value = $(element).val();
    if (value !== "") {
        callback({
            id: value,
            text: value
        });
    }
},
});
// -->
</script>';

                $buttons = [
                    $form->generate_submit_button($lang->dvz_hash_admin_submit)
                ];
                $form->output_submit_wrapper($buttons);
                $form->end();
            }

        }

        $page->output_footer();
    }

}

function admin_home_index_output_message()
{
    global $db, $lang, $page;

    $n = $db->fetch_field(
        $db->simple_select('users', 'COUNT(uid) AS n', "password_downgraded != ''"),
        'n'
    );

    if ($n != 0) {
        $page->output_error($lang->sprintf($lang->dvz_hash_admin_active_downgrades, 'index.php?module=tools-dvz_hash&action=downgrades'));
    }
}

function admin_config_plugins_begin()
{
    global $lang;
    $lang->load('dvz_hash');
}

function admin_tools_action_handler(array &$actions)
{
    $actions['dvz_hash'] = [
        'active' => 'dvz_hash',
        'file'   => 'dvz_hash',
    ];
}

function admin_tools_menu(array &$sub_menu)
{
    global $lang;

    $lang->load('dvz_hash');

    $sub_menu[] = [
        'id' => 'dvz_hash',
        'title' => $lang->dvz_hash_admin,
        'link' => 'index.php?module=tools-dvz_hash',
    ];
}
