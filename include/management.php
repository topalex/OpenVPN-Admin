<?php

function managementConnect()
{
    global $management_host, $management_port, $management_pass, $management_timeout;

    $handle = @fsockopen($management_host, $management_port, $errno, $errstr, $management_timeout);
    if (!$handle) {
        return false;
    }

    fwrite($handle, $management_pass . "\n\n\n");

    return $handle;
}

function managementGetStatus()
{
    $handle = managementConnect();
    if ($handle === false) {
        return false;
    }

    $online = [];

    while (($buffer = fgets($handle)) !== false) {
        if (strpos($buffer, '>INFO:') !== false) {
            fwrite($handle, "status 2\n\n\n");
        } else if (strpos($buffer, 'ENTER PASSWORD:') === false) {
            $line = explode(',', trim($buffer));

            if ($line[0] === 'END') {
                fwrite($handle, "quit\n\n\n");
            } else if ($line[0] === 'CLIENT_LIST') {
                $online[] = $line[1];
            }
        }
    }
    if (!feof($handle)) {
        return false;
    }

    fclose($handle);

    return $online;
}

function managementGetUserStatus($cn, $statuses)
{
    if (!empty($statuses) && in_array($cn, $statuses)) {
        return 1;
    }

    return 0;
}

function managementKickUser($cn)
{
    $handle = managementConnect();
    if ($handle === false) {
        return false;
    }

    $cn = preg_replace('/[^a-z]*/i', '', $cn);

    // todo: implement

    return true;
}
