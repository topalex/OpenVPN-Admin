<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    exit(-1);
}

require(__DIR__ . '/../include/connect.php');
require(__DIR__ . '/../include/functions.php');
require(__DIR__ . '/../include/management.php');

// ---------------- SELECT ----------------
if (isset($_GET['select'])) {
    // Select the users
    if ($_GET['select'] === 'user') {
        $req = $bdd->prepare('select user.*, max(log.log_start_time) as user_last_start, max(log.log_end_time) as user_last_end from user left join log using(user_id) group by user_id');
        $req->execute();

        if ($data = $req->fetch()) {
            $management_status = managementGetStatus();

            do {
                $list[] = [
                    'user_id' => $data['user_id'],
                    'user_pass' => $data['user_pass'],
                    'user_last_start' => $data['user_last_start'],
                    'user_last_end' => $data['user_last_end'],
                    'user_online' => managementGetUserStatus($data['user_id'], $management_status),
                    'user_enable' => $data['user_enable'],
                    'user_start_date' => $data['user_start_date'],
                    'user_end_date' => $data['user_end_date']
                ];
            } while ($data = $req->fetch());

            echo json_encode($list);
        } else {
            // If it is an empty answer, we need to encore an empty json object
            $list = [];
            echo json_encode($list);
        }
    } else if ($_GET['select'] === 'log' && isset($_GET['offset'], $_GET['limit'])) {
        // Select the logs
        $offset = intval($_GET['offset']);
        $limit = intval($_GET['limit']);

        // Creation of the LIMIT for build different pages
        $page = "LIMIT $offset, $limit";

        // ... filtering by the bootstrap table plugin
        // this is passed by the bootstrap table filter plugin (if a filter was chosen by the user):
        // these are the concrete set filters with their value
        $filter = isset($_GET['filter']) ? json_decode($_GET['filter'], true) : [];
        $where = !empty($filter) ? 'WHERE TRUE' : '';
        // these are valid filters that could be used (defined here for sql security reason)
        $allowed_query_filters = ['user_id', 'log_trusted_ip', 'log_trusted_port', 'log_remote_ip', 'log_remote_port'];
        $query_filters_existing = [];
        foreach ($filter as $unsanitized_filter_key => $unsanitized_filter_val) {
            // if this condition does not match: ignore it, because this parameter should not be passed
            if (in_array($unsanitized_filter_key, $allowed_query_filters)) {
                // if $unsanitized_filter_key is in array $allowed_query_filters its a valid key and can not be harmful,
                // so it can be considered sanitized
                $where .= " AND $unsanitized_filter_key = ?";
                $query_filters_existing[] = $unsanitized_filter_key;
            }
        }

        $order = 'log_id desc';

        $allowed_sort_columns = [
            'log_id',
            'user_id',
            'log_trusted_ip',
            'log_trusted_port',
            'log_remote_ip',
            'log_remote_port',
            'log_start_time',
            'log_end_time',
            'log_received',
            'log_send'
        ];
        $allowed_sort_orders = [
            'asc',
            'desc'
        ];

        if (isset($_GET['sort'], $_GET['order']) && in_array($_GET['sort'], $allowed_sort_columns) &&
            in_array($_GET['order'], $allowed_sort_orders)
        ) {
            $order = $_GET['sort'] . ' ' . $_GET['order'];
        }

        // Select the logs
        $req_string = "SELECT *, (SELECT COUNT(*) FROM log $where) AS nb FROM log $where ORDER BY $order $page";
        $req = $bdd->prepare($req_string);

        // dynamically bind the params
        // array_merge -> duplicated the array contents;
        // this is needed because our where clause is bound two times (in subquery + the outer query)
        foreach (array_merge($query_filters_existing, $query_filters_existing) as $i => $query_filter) {
            $req->bindValue($i + 1, $filter[$query_filter]);
        }

        $req->execute();

        $list = [];

        $data = $req->fetch();

        if ($data) {
            $nb = $data['nb'];

            do {
                // Better in Kb or Mb
                $received = ($data['log_received'] > 1000000) ?
                    $data['log_received'] / 1000000 . ' Mo' : $data['log_received'] / 1000 . ' Ko';
                $sent = ($data['log_send'] > 1000000) ?
                    $data['log_send'] / 1000000 . ' Mo' : $data['log_send'] / 1000 . ' Ko';

                // We add to the array the new line of logs
                array_push($list, [
                    'log_id' => $data['log_id'],
                    'user_id' => $data['user_id'],
                    'log_trusted_ip' => $data['log_trusted_ip'],
                    'log_trusted_port' => $data['log_trusted_port'],
                    'log_remote_ip' => $data['log_remote_ip'],
                    'log_remote_port' => $data['log_remote_port'],
                    'log_start_time' => $data['log_start_time'],
                    'log_end_time' => $data['log_end_time'],
                    'log_received' => $received,
                    'log_send' => $sent
                ]);
            } while ($data = $req->fetch());
        } else {
            $nb = 0;
        }

        // We finally print the result
        $result = ['total' => intval($nb), 'rows' => $list];

        echo json_encode($result);
    } else if ($_GET['select'] === 'admin') {
        // Select the admins
        $req = $bdd->prepare('SELECT * FROM admin');
        $req->execute();

        if ($data = $req->fetch()) {
            do {
                $list[] = [
                    'admin_id' => $data['admin_id'],
                    'admin_pass' => $data['admin_pass']
                ];
            } while ($data = $req->fetch());

            echo json_encode($list);
        } else {
            $list = [];
            echo json_encode($list);
        }
    } else if ($_GET['select'] === 'config' && isset($_GET['config_file']) &&
        in_array($_GET['config_file'], ['gnu-linux', 'windows', 'osx'])
    ) {
        $item = $_GET['config_file'];

        echo renderTemplate(__DIR__ . '/../include/template/config.php', [
            'file' => $item,
            'content' => file_get_contents(__DIR__ . "/$client_config_path/$item/$client_config_name"),
            'history' => getConfigHistory(__DIR__ . "/$client_config_path/$item/history/*")
        ]);
    }
} else if (isset($_POST['add_user'], $_POST['user_id'], $_POST['user_pass'])) {
    // ---------------- ADD USER ----------------
    // Put some default values
    $id = $_POST['user_id'];
    $pass = hashPass($_POST['user_pass']);
    $enable = 1;
    $start = null;
    $end = null;

    $req = $bdd->prepare('INSERT INTO user (user_id, user_pass, user_enable, user_start_date, user_end_date)
                        VALUES (?, ?, ?, ?, ?)');
    $req->execute([$id, $pass, $enable, $start, $end]);

    $res = [
        'user_id' => $id,
        'user_pass' => $pass,
        'user_last_start' => null,
        'user_last_end' => null,
        'user_online' => 0,
        'user_enable' => $enable,
        'user_start_date' => $start,
        'user_end_date' => $end
    ];

    echo json_encode($res);
} else if (isset($_POST['set_user'])) {
    // ---------------- UPDATE USER ----------------
    $valid = ['user_id', 'user_pass', 'user_enable', 'user_start_date', 'user_end_date'];

    $field = $_POST['name'];
    $value = $_POST['value'];
    $pk = $_POST['pk'];

    if (!isset($field) || !isset($pk) || !in_array($field, $valid)) {
        return;
    }

    if ($field === 'user_pass') {
        $value = hashPass($value);
    } else if (($field === 'user_start_date' || $field === 'user_end_date') && $value === '') {
        $value = null;
    }

    // /!\ SQL injection: field was checked with in_array function
    $req_string = 'UPDATE user SET ' . $field . ' = ? WHERE user_id = ?';
    $req = $bdd->prepare($req_string);
    $req->execute([$value, $pk]);
} else if (isset($_POST['del_user'], $_POST['del_user_id'])) {
    // ---------------- REMOVE USER ----------------
    $req = $bdd->prepare('DELETE FROM user WHERE user_id = ?');
    $req->execute([$_POST['del_user_id']]);
    managementKickUser($_POST['del_user_id']);
} else if (isset($_POST['kick_user'], $_POST['kick_user_id'])) {
    // ---------------- KICK USER ----------------
    managementKickUser($_POST['kick_user_id']);
} else if (isset($_POST['add_admin'], $_POST['admin_id'], $_POST['admin_pass'])) {
    // ---------------- ADD ADMIN ----------------
    $req = $bdd->prepare('INSERT INTO admin(admin_id, admin_pass) VALUES (?, ?)');
    $req->execute([$_POST['admin_id'], hashPass($_POST['admin_pass'])]);
} else if (isset($_POST['set_admin'])) {
    // ---------------- UPDATE ADMIN ----------------
    $valid = ['admin_id', 'admin_pass'];

    $field = $_POST['name'];
    $value = $_POST['value'];
    $pk = $_POST['pk'];

    if (!isset($field) || !isset($pk) || !in_array($field, $valid)) {
        return;
    }

    if ($field === 'admin_pass') {
        $value = hashPass($value);
    }

    $req_string = 'UPDATE admin SET ' . $field . ' = ? WHERE admin_id = ?';
    $req = $bdd->prepare($req_string);
    $req->execute([$value, $pk]);
} else if (isset($_POST['del_admin'], $_POST['del_admin_id'])) {
    // ---------------- REMOVE ADMIN ----------------
    $req = $bdd->prepare('DELETE FROM admin WHERE admin_id = ?');
    $req->execute([$_POST['del_admin_id']]);
} else if (isset($_POST['update_config']) && isset($_POST['config_content']) && isset($_POST['config_file']) &&
    in_array($_POST['config_file'], ['gnu-linux', 'windows', 'osx'])
) {
    // ---------------- UPDATE CONFIG ----------------
    $config_name = $_POST['config_file'];
    $config_full_path = __DIR__ . "/$client_config_path/$config_name";
    $config_full_uri = "$config_full_path/$client_config_name";

    /*
     * create backup for history
     */
    if (!file_exists($dir = "$config_full_path/history")) {
        mkdir($dir, 0700, true);
    }
    $ts = time();
    copy($config_full_uri, "$config_full_path/history/${ts}_${client_config_name}");

    /*
     *  write config
     */
    $conf_success = file_put_contents($config_full_uri, $_POST['config_content']);

    echo json_encode([
        'config_success' => $conf_success !== false,
    ]);
}
