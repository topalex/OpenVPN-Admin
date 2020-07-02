<?php

$db_host = 'localhost';
$db_port = 3306;
$db_name = 'openvpn-admin';
$db_user = 'openvpn-admin';
$db_pass = 'openvpn-admin';

$management_host = 'localhost';
$management_port = 7505;
$management_pass = 'openvpn-admin';
$management_timeout = 30;

$client_config_path = '../client-conf';
$client_config_name = 'client.ovpn';

if (is_file(__DIR__ . '/config_local.php')) {
    /** @noinspection PhpIncludeInspection */
    require(__DIR__ . '/config_local.php');
}
