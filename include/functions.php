<?php

function getMigrationSchemas()
{
    return [0, 5, 6, 7];
}

function updateSchema($bdd, $newKey)
{
    if ($newKey === 0) {
        $req_string = 'INSERT INTO `application` (sql_schema) VALUES (?)';
    } else {
        $req_string = 'UPDATE `application` SET `sql_schema` = ?';
    }

    $req = $bdd->prepare($req_string);
    $req->execute([$newKey]);
}

function isInstalled($bdd)
{
    $req = $bdd->prepare("SHOW TABLES LIKE 'admin'");
    $req->execute();

    if (!$req->fetch()) {
        return false;
    }

    return true;
}

function hashPass($pass)
{
    return password_hash($pass, PASSWORD_DEFAULT);
}

function passEqual($pass, $hash)
{
    return password_verify($pass, $hash);
}

/** @noinspection PhpUnusedParameterInspection */
function renderTemplate($path, $data = [])
{
    ob_start();
    /** @noinspection PhpIncludeInspection */
    require $path;
    return ob_get_clean();
}

function printIndex($code = -1)
{
    global $error, $success, $content;

    echo renderTemplate(__DIR__ . '/../include/template/index.php', [
        'error' => $error,
        'success' => $success,
        'content' => $content
    ]);

    exit($code);
}

function getConfigHistory($path)
{
    return array_map(function ($item) {
        $chunks = explode('_', basename($item));

        return [
            'name' => sprintf('[%s] %s', date('r', $chunks[0]), $chunks[1]),
            'content' => file_get_contents($item)
        ];
    }, array_reverse(glob($path)));
}
