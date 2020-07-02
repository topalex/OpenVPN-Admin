<?php

require(__DIR__ . '/config.php');

$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$bdd = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass, $options);
