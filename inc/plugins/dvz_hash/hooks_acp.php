<?php

namespace dvzHash;

function admin_load()
{
    global $mybb, $db, $lang, $run_module, $action_file, $page, $sub_tabs;

    if ($run_module == 'tools' && $action_file == 'dvz_hash') {
        $pageUrl = 'index.php?module=' . $run_module . '-' . $action_file;

        $lang->load('dvz_hash');

        $page->add_breadcrumb_item($lang->dvz_hash_admin, 'index.php?module=config-dvz_hash');

        $sub_tabs = [];

        $tabs = [
            'algorithms',
            'encryption',
            'downgrades',
        ];

        if (version_compare(PHP_VERSION, '7.4', '>=')) {
            $tabs[] = 'benchmark';
        }

        foreach ($tabs as $tabName) {
            $sub_tabs[$tabName] = [
                'link'        => $pageUrl . '&amp;action=' . $tabName,
                'title'       => $lang->{'dvz_hash_admin_tab_' . $tabName},
                'description' => $lang->{'dvz_hash_admin_tab_' . $tabName . '_description'},
            ];
        }

        if ($mybb->input['action'] == 'algorithms' || empty($mybb->input['action'])) {
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
            $page->output_nav_tabs($sub_tabs, 'algorithms');

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
        } elseif ($mybb->input['action'] == 'benchmark' && version_compare(PHP_VERSION, '7.4', '>=')) {
            $benchmarkAlgorithms = [];

            if (defined('PASSWORD_BCRYPT')) {
                $benchmarkAlgorithms['bcrypt'] = [
                    'closure' => function (string $string, array $options) {
                        return password_hash($string, PASSWORD_BCRYPT, $options);
                    },
                    'options' => [
                        'cost' => [
                            'min' => 4,
                            'max' => 31,
                            'default' => '4..14',
                        ],
                    ],
                ];
            }
            if (defined('PASSWORD_ARGON2ID')) {
                $benchmarkAlgorithms['argon2id'] = [
                    'closure' => function (string $string, array $options) {
                        return password_hash($string, PASSWORD_ARGON2ID, $options);
                    },
                    'options' => [
                        'threads' => [
                            'min' => 1,
                            'max' => (2 << 24) - 1,
                            'default' => 1,
                        ],
                        'memory_cost' => [
                            'min' => 8,
                            'max' => (2 << 32) - 1,
                            'default' => (int)PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                        ],
                        'time_cost' => [
                            'min' => 1,
                            'max' => (2 << 32) - 1,
                            'default' => '1..8',
                        ],
                    ],
                ];
            }

            if ($mybb->request_method == 'post' && $mybb->get_input('ajax') == 1) {
                if ($mybb->get_input('ajax_action') == 'graph') {
                    $inputData = json_decode($mybb->get_input('data'), true, 2, JSON_OBJECT_AS_ARRAY);

                    $graph = \dvzHash\getRenderedGraph(
                        array_combine(
                            array_map('strval', array_keys($inputData)),
                            array_map('floatval', $inputData)
                        ),
                        [
                            'label' => $lang->dvz_hash_admin_benchmark_results_label,
                            'image_width' => abs($mybb->get_input('width', \MyBB::INPUT_INT)),
                            'graph_width' => abs($mybb->get_input('width', \MyBB::INPUT_INT)) - 80,
                        ]
                    );

                    $graph->output();
                    exit;
                } elseif ($mybb->get_input('ajax_action') == 'benchmark') {
                    $response = [];

                    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
                    });

                    try {
                        $requestedAlgorithmName = $mybb->get_input('algorithm');
                        $requestedOptions = $mybb->get_input('options', \MyBB::INPUT_ARRAY);
                        $requestedSampleSize = abs($mybb->get_input('sample_size', \MyBB::INPUT_INT));

                        if (array_key_exists($mybb->get_input('algorithm'), $benchmarkAlgorithms)) {
                            $benchmarkAlgorithm = $benchmarkAlgorithms[$requestedAlgorithmName];

                            $benchmarkOptions = [];

                            $rangedOptionName = null;
                            $rangeStart = null;
                            $rangeStep = null;
                            $rangeStepExponent = null;
                            $rangeEnd = null;

                            foreach ($benchmarkAlgorithm['options'] as $optionName => $option) {
                                if (array_key_exists($optionName, $requestedOptions)) {
                                    if (preg_match('#^(\d+)(?:\+(\d+)(?:\^(\d+))?)?\.\.(\d+)$#', $requestedOptions[$optionName], $matches)) {
                                        if ($rangedOptionName !== null) {
                                            throw new \InvalidArgumentException('Too many ranged options');
                                        }

                                        $rangedOptionName = $optionName;
                                        $rangeStart = (int)$matches[1];
                                        $rangeEnd = (int)$matches[4];

                                        if ($matches[2] === '') {
                                            $rangeStep = 1;
                                        } else {
                                            $rangeStep = (int)$matches[2];
                                        }

                                        if ($matches[3] !== '') {
                                            $rangeStepExponent = (int)$matches[3];

                                            if ($rangeStepExponent < 1) {
                                                throw new \InvalidArgumentException('Incorrect option range');
                                            }
                                        }

                                        if ($rangeStart < 0 || $rangeEnd < 1 || $rangeStart >= $rangeEnd || $rangeStep < 1) {
                                            throw new \InvalidArgumentException('Incorrect option range');
                                        }
                                    } elseif (ctype_digit($requestedOptions[$optionName])) {
                                        $benchmarkOptions[$optionName] = $requestedOptions[$optionName];
                                    } else {
                                        throw new \InvalidArgumentException('Incorrect algorithm option value (' . $optionName . ')');
                                    }
                                } else {
                                    throw new \InvalidArgumentException('Missing algorithm options');
                                }
                            }

                            if ($rangedOptionName === null) {
                                $algorithmOptionSets = [$benchmarkOptions];
                                $range = null;
                            } else {
                                $algorithmOptionSets = [];
                                $range = [];

                                $i = -1;
                                $value = $rangeStart;
                                while ($value <= $rangeEnd) {
                                    $algorithmOptionSet = $benchmarkOptions;

                                    $algorithmOptionSet[$rangedOptionName] = $value;
                                    $range[] = $value;

                                    $algorithmOptionSets[] = $algorithmOptionSet;

                                    if ($rangeStepExponent !== null) {
                                        $value += $rangeStep ** ($rangeStepExponent + $i);
                                    } else {
                                        $value += $rangeStep;
                                    }

                                    $i++;
                                }
                            }

                            require_once MYBB_ROOT . 'inc/plugins/dvz_hash/Benchmark.php';

                            $benchmark = new \dvzHash\Benchmark(Benchmark::SUBJECT_TYPE_ARGUMENT_SETS);

                            $randomString = \random_str(8);

                            $benchmark->setBaseClosure(function (array $options) use ($benchmarkAlgorithm, $randomString) {
                                $benchmarkAlgorithm['closure']($randomString, $options);
                            });
                            $benchmark->setArgumentSets(array_map(
                                function ($value) {
                                    return [$value];
                                },
                                $algorithmOptionSets
                            ));
                            $benchmark->setSampleSize($requestedSampleSize);

                            set_time_limit(min(
                                $mybb->get_input('time_limit', \MyBB::INPUT_INT),
                                86400
                            ));

                            $benchmark->run();

                            $casesStatistics = $benchmark->getCaseStatistics();

                            if ($range !== null) {
                                $caseMeans = array_combine($range, array_column($casesStatistics, 'mean'));

                                $response['results'] = $caseMeans;
                            }

                            $resultsText = $lang->sprintf(
                                $lang->dvz_hash_admin_measurements,
                                count($casesStatistics),
                                $requestedSampleSize,
                                count($casesStatistics) * $requestedSampleSize,
                                round($benchmark->getTotalSampleTime(), 4)
                            ) . PHP_EOL . PHP_EOL;

                            foreach ($casesStatistics as $i => $caseStatistics) {
                                $optionSet = $algorithmOptionSets[$i];

                                $parameterStrings = [];

                                foreach ($optionSet as $name => $value) {
                                    $value = \htmlspecialchars_uni($value);

                                    if ($name === $rangedOptionName) {
                                        $value = '<strong>' . $value . '</strong>';
                                    }

                                    $parameterStrings[] .= \htmlspecialchars_uni($name) . '=' . $value;
                                }

                                $parametersString = implode(', ', $parameterStrings);

                                $resultsText .= '<div class="benchmark__sample">';
                                $resultsText .= '<div class="benchmark__sample__heading">';

                                $resultsText .= '#' . ($i + 1) . ' <strong>' . \htmlspecialchars_uni($requestedAlgorithmName) . '</strong>';
                                $resultsText .= '(' . $parametersString . '): <span class="benchmark__sample__property" title="' . $lang->dvz_hash_admin_sample_mean . '"><span style="font-family: serif">x&#772;</span> = <strong>' . round($caseStatistics['mean'], 10) . ' s</strong></span>';

                                if ($caseStatistics['mean'] != 0) {
                                    $resultsText .= ' - <span class="benchmark__sample__property" title="' . $lang->dvz_hash_admin_hashes_per_second . '">' . round(1 / $caseStatistics['mean'], 4) . ' H/s</span>';
                                }

                                $resultsText .= PHP_EOL;

                                $resultsText .= '</div>';

                                if ($requestedSampleSize !== 1) {
                                    $details = [
                                        [
                                            [
                                                'name' => 'IQM',
                                                'value' => round($caseStatistics['interquartileMean'], 10),
                                                'title' => $lang->dvz_hash_admin_interquartile_mean,
                                            ],
                                            [
                                                'name' => 'Md',
                                                'value' => round($caseStatistics['median'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_median,
                                            ],
                                            [
                                                'name' => '∑',
                                                'value' => round($caseStatistics['sum'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_sum,
                                            ],
                                        ],
                                        [
                                            [
                                                'name' => 'Min',
                                                'value' => round($caseStatistics['min'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_min,
                                            ],
                                            [
                                                'name' => 'Max',
                                                'value' => round($caseStatistics['max'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_max,
                                            ],
                                            [
                                                'name' => 'R',
                                                'value' => round($caseStatistics['range'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_range,
                                            ],
                                            [
                                                'name' => 'MR',
                                                'value' => round($caseStatistics['midrange'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_midrange,
                                            ],
                                        ],
                                        [
                                            [
                                                'name' => 's',
                                                'value' => round($caseStatistics['standardDeviation'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_standard_deviation,
                                            ],
                                            [
                                                'name' => 'CV',
                                                'value' => round($caseStatistics['coefficientOfVariation'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_coefficient_of_variation,
                                            ],
                                            [
                                                'name' => 's&sup2;',
                                                'value' => round($caseStatistics['variance'], 10),
                                                'title' => $lang->dvz_hash_admin_sample_variance,
                                            ],
                                            [
                                                'name' => 'SEM',
                                                'value' => round($caseStatistics['standardErrorOfTheMean'], 10),
                                                'title' => $lang->dvz_hash_admin_standard_error_of_the_mean,
                                            ],
                                            [
                                                'name' => 'RSE',
                                                'value' => round($caseStatistics['relativeStandardError'] * 100, 10) . '%',
                                                'title' => $lang->dvz_hash_admin_relative_standard_error,
                                            ],
                                        ],
                                    ];

                                    $detailsStrings = [];

                                    foreach ($details as $row) {
                                        $rowStrings = [];

                                        foreach ($row as $property) {
                                            $attributes = [
                                                'class="benchmark__sample__property"',
                                            ];

                                            if (isset($property['title'])) {
                                                $attributes[] = 'title="' . $property['title'] . '"';
                                            }

                                            $string = '<span ' . implode(' ', $attributes) . '>' . $property['name'] . ' = ' . $property['value'] . '</span>';

                                            $rowStrings[] = $string;
                                        }

                                        $detailsStrings[] = implode(', ', $rowStrings);
                                    }

                                    $detailsString = implode(PHP_EOL . '   ', $detailsStrings);

                                    $resultsText .= '   <span class="benchmark__sample__details">' . $detailsString . '</span>' . PHP_EOL;
                                }

                                $resultsText .= '</div>';
                            }

                            $response['resultsText'] = $resultsText;
                        } else {
                            throw new \Exception('Algorithm not supported');
                        }
                    } catch (\Exception $e) {
                        $response['errors'] = [
                            $e->getMessage(),
                        ];
                    }

                    restore_error_handler();

                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit;
                }
            }

            $page->output_header($lang->dvz_hash_admin_tab_benchmark);
            $page->output_nav_tabs($sub_tabs, 'benchmark');

            $environmentData = [
                [
                    'title' => $lang->dvz_hash_admin_memory_limit_php_ini,
                    'value' => ini_get('memory_limit') ?? null,
                ],
                [
                    'title' => $lang->dvz_hash_admin_max_execution_time,
                    'value' => ini_get('max_execution_time') ?? null,
                ],
                [
                    'title' => $lang->dvz_hash_admin_password_algorithms,
                    'value' => implode("; ", array_filter([
                        'PASSWORD_BCRYPT',
                        'PASSWORD_ARGON2I',
                        'PASSWORD_ARGON2ID',
                    ], 'defined'))
                ],
                [
                    'title' => $lang->dvz_hash_admin_server_load,
                    'value' => get_server_load(),
                ],
            ];

            $smokeTestResults = \dvzHash\getAlgorithmSmokeTestResults();

            foreach ($smokeTestResults as $algorithmName => $result) {
                $environmentData[] = [
                    'title' => $lang->sprintf(
                        $lang->dvz_hash_admin_smoke_test,
                        '<code>' . \htmlspecialchars_uni($algorithmName) . '</code>'
                    ),
                    'value' => $result ? $lang->ok : $lang->error,
                ];
            }

            $table = new \Table;

            foreach ($environmentData as $entry) {
                $table->construct_cell('<strong>' . $entry['title'] . '</strong>', [
                    'width' => '40%',
                ]);
                $table->construct_cell(\htmlspecialchars_uni($entry['value']), [
                    'width' => '60%',
                ]);
                $table->construct_row();
            }

            $table->output($lang->dvz_hash_admin_environment);

            // add form
            $testableAlgorithms = array_keys($benchmarkAlgorithms);

            $form = new \Form(null, null, 'benchmarkForm');

            $form_container = new \FormContainer($lang->dvz_hash_admin_benchmark, 'benchmark');
            $form_container->output_row(
                $lang->dvz_hash_admin_algorithm,
                null,
                $form->generate_select_box('algorithm', array_combine(
                    $testableAlgorithms,
                    $testableAlgorithms
                )),
                'argon2id'
            );

            $peekers = null;

            foreach ($testableAlgorithms as $algorithmName) {
                foreach ($benchmarkAlgorithms[$algorithmName]['options'] as $optionName => $option) {
                    $form_container->output_row(
                        $optionName,
                        $lang->{'dvz_hash_admin_option_' . $algorithmName . '_' . $optionName} ?? null,
                        '<input type="text" name="options[' . $optionName . ']" value="' . $option['default'] . '" class="text_input" required pattern="\d+((\+\d+(\^\d+)?)?\.\.\d+)?" min="' . $option['min'] . '" max="' . $option['max'] . '">',
                        null,
                        [],
                        [
                            'class' => 'option--' . $algorithmName,
                        ]
                    );
                }

                $peekers .= 'new Peeker($("select[name=\'algorithm\']"), $(".option--' . $algorithmName . '"), "' . $algorithmName . '", false)' . PHP_EOL;
            }

            $form_container->output_row(
                $lang->dvz_hash_admin_sample_size,
                $lang->dvz_hash_admin_sample_size_description,
                $form->generate_numeric_field('sample_size', 3, [
                    'min' => 1,
                ])
            );
            $form_container->output_row(
                $lang->dvz_hash_admin_time_limit,
                $lang->dvz_hash_admin_time_limit_description,
                $form->generate_numeric_field('time_limit', ini_get('max_execution_time') ?? 30, [
                    'min' => 1,
                    'max' => 86400,
                ])
            );
            $form_container->end();

            $form->output_submit_wrapper([
                $form->generate_submit_button($lang->dvz_hash_admin_submit)
            ]);
            $form->end();

            echo <<<HTML
<br />
<fieldset class="benchmark__results" hidden>
    <legend>{$lang->dvz_hash_admin_benchmark_results}</legend>
    <img class="benchmark__results__graph" hidden />
    <div class="benchmark__controls">
        <div class="benchmark__controls__control benchmark__controls__control--select">{$lang->dvz_hash_admin_copy}</div>
    </div>
    <div class="benchmark__results__text">
        <pre><code></code></pre>
    </div>
</fieldset>

<style>
.benchmark__results__graph { margin-bottom: 10px; border-bottom: solid 1px #CCCCCC; }
.benchmark__controls { text-align: right; margin-bottom: -30px; }
.benchmark__controls__control { display: inline-block; padding: 6px; border: solid 1px rgba(0,0,0,0.1); border-radius: 3px; cursor: pointer; font-size: 0.8em; color: #666666; text-transform: uppercase; }
.benchmark__sample { padding: 4px 0; }
.benchmark__sample:nth-child(even) { background-color: rgba(0,0,0,0.02); }
.benchmark__sample:hover { background-color: rgba(255,255,200,0.5); }
.benchmark__sample__heading { margin-bottom: 2px; }
.benchmark__sample__details { color: #888888; }
.benchmark__sample__property:hover { background-color: rgba(255,255,255,0.8); }
</style>

<script type="text/javascript" src="./jscripts/peeker.js"></script>
<script>
{$peekers}

var my_post_key = '{$mybb->post_code}';
lang.dvz_hash_admin_copied = '{$lang->dvz_hash_admin_copied}';
</script>
<script src="./jscripts/dvz_hash_benchmark.js"></script>
HTML;

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
