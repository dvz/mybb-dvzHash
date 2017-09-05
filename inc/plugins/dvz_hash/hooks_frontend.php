<?php

namespace dvzHash;

function create_password(array &$parameters)
{
    if (isset($parameters['user']['password_algorithm'])) {
        if (in_array($parameters['user']['password_algorithm'], ['', 'mybb'])) {
            $algorithm = 'mybb';
        } elseif (\dvzHash\isKnownAlgorithm($parameters['user']['password_algorithm'])) {
            $algorithm = $parameters['user']['password_algorithm'];
        } else {
            $algorithm = \dvzHash\getPreferredAlgorithm();
        }
    } else {
        $algorithm = \dvzHash\getPreferredAlgorithm();
    }

    $returnedFields = \dvzHash\hash($algorithm, $parameters['password']);

    $returnedFields = \dvzHash\wrapPasswordFields($returnedFields);

    if (!isset($returnedFields['salt'])) {
        $returnedFields['salt'] = \generate_salt();
    }

    if ($returnedFields['password_algorithm'] == 'mybb') {
        $returnedFields['password_algorithm'] = '';
    }

    $parameters['fields'] = $returnedFields;
}

function verify_user_password(array &$parameters)
{
    if (
        !isset($parameters['user']['password_algorithm']) ||
        in_array($parameters['user']['password_algorithm'], ['', 'mybb']) ||
        !empty($parameters['user']['password_downgraded'])
    ) {
        $algorithm = 'mybb';
    } elseif (\dvzHash\isKnownAlgorithm($parameters['user']['password_algorithm'])) {
        $algorithm = $parameters['user']['password_algorithm'];
    }

    if (isset($algorithm)) {
        $parameters['result'] = \dvzHash\verify($algorithm, $parameters['password'], $parameters['user']);
    }
}

function datahandler_user_insert(\UserDataHandler $UserDataHandler)
{
    if (isset($UserDataHandler->data['password_algorithm'])) {
        $UserDataHandler->user_insert_data['password_algorithm'] = $UserDataHandler->data['password_algorithm'];
    }

    if (isset($UserDataHandler->data['password_encryption'])) {
        $UserDataHandler->user_insert_data['password_encryption'] = (int)$UserDataHandler->data['password_encryption'];
    }

    if (isset($UserDataHandler->data['password'])) {
        $UserDataHandler->user_insert_data['password_downgraded'] = '';
    }
}

function datahandler_user_update(\UserDataHandler $UserDataHandler)
{
    if (isset($UserDataHandler->data['password_algorithm'])) {
        $UserDataHandler->user_update_data['password_algorithm'] = $UserDataHandler->data['password_algorithm'];
    }

    if (isset($UserDataHandler->data['password_encryption'])) {
        $UserDataHandler->user_update_data['password_encryption'] = (int)$UserDataHandler->data['password_encryption'];
    }

    if (isset($UserDataHandler->data['password'])) {
        $UserDataHandler->user_update_data['password_downgraded'] = '';
    }
}

function datahandler_login_complete_end(\LoginDataHandler $LoginDataHandler)
{
    global $db;

    if (isset($LoginDataHandler->login_data['password_algorithm'])) {
        if (
            \dvzHash\getSettingValue('update_on_the_fly') &&
            empty($LoginDataHandler->login_data['password_downgraded']) &&
            \dvzHash\getPreferredAlgorithm() != 'mybb'
        ) {
            if (
                $LoginDataHandler->login_data['password_algorithm'] != \dvzHash\getPreferredAlgorithm() ||
                \dvzHash\needsRehash(\dvzHash\getPreferredAlgorithm(), $LoginDataHandler->login_data)
            ) {
                $data = \create_password($LoginDataHandler->data['password']);

                $db->update_query('users', $data, 'uid=' . (int)$LoginDataHandler->login_data['uid']);
            }
        }
    }
}
