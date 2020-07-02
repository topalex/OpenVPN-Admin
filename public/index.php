<?php

$error = null;
$success = null;
$content = '';

session_start();

require(__DIR__ . '/../include/functions.php');
require(__DIR__ . '/../include/connect.php');

// Disconnecting ?
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: .');
    exit(-1);
}

// Get the configuration files ?
if (isset(
        $_POST['configuration_get'],
        $_POST['configuration_username'],
        $_POST['configuration_pass'],
        $_POST['configuration_os']
    ) && !empty($_POST['configuration_pass'])
) {
    $req = $bdd->prepare('SELECT * FROM user WHERE user_id = ?');
    $req->execute([$_POST['configuration_username']]);
    $data = $req->fetch();

    // Error ?
    if ($data && passEqual($_POST['configuration_pass'], $data['user_pass'])) {
        if (!file_exists($tmp = __DIR__ . "/../tmp")) {
            mkdir($tmp, 0700, true);
        }

        // Thanks http://stackoverflow.com/questions/4914750/how-to-zip-a-whole-folder-using-php
        if ($_POST['configuration_os'] === "gnu_linux") {
            $conf_dir = 'gnu-linux';
        } elseif ($_POST['configuration_os'] === "osx") {
            $conf_dir = 'osx';
        } else {
            $conf_dir = 'windows';
        }
        $rootPath = __DIR__ . "/$client_config_path/$conf_dir";

        // Initialize archive object
        $archive_base_name = "openvpn-$conf_dir";
        $archive_name = "$archive_base_name.zip";
        $archive_path = "$tmp/$archive_name";
        $zip = new ZipArchive();
        $zip->open($archive_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (new DirectoryIterator($rootPath) as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = $file->getFilename();

                // Add current file to archive
                $zip->addFile($filePath, "$archive_base_name/$relativePath");
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        // then send the headers to force download the zip file
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$archive_name");
        header("Content-Length: " . filesize($archive_path));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($archive_path);
    } else {
        $error = 'Login error';
    }
} else if (isset(
        $_POST['admin_login'],
        $_POST['admin_username'],
        $_POST['admin_pass']
    ) && !empty($_POST['admin_pass'])
) {
    // Admin login attempt ?
    $req = $bdd->prepare('SELECT * FROM admin WHERE admin_id = ?');
    $req->execute([$_POST['admin_username']]);
    $data = $req->fetch();

    // Error ?
    if ($data && passEqual($_POST['admin_pass'], $data['admin_pass'])) {
        $_SESSION['admin_id'] = $data['admin_id'];
        header('Location: index.php?admin');
        exit(-1);
    } else {
        $error = 'Login error';
    }
}

// --------------- INSTALLATION ---------------
if (isset($_GET['installation'])) {
    if (isInstalled($bdd) === true) {
        $error = 'OpenVPN-admin is already installed. Redirection.';
        header('refresh:3;url=index.php?admin');
        printIndex();
    }

    // If the user sent the installation form
    if (isset($_POST['admin_username'])) {
        $admin_username = $_POST['admin_username'];
        $admin_pass = $_POST['admin_pass'];
        $admin_repeat_pass = $_POST['repeat_admin_pass'];

        if ($admin_pass != $admin_repeat_pass) {
            $error = 'The passwords do not correspond. Redirection.';
            header('refresh:3;url=index.php?installation');
            printIndex();
        }

        // Create the initial tables
        $migrations = getMigrationSchemas();
        foreach ($migrations as $migration_value) {
            $sql_file = __DIR__ . "/../sql/schema-$migration_value.sql";
            try {
                $sql = file_get_contents($sql_file);
                $bdd->exec($sql);
            } catch (PDOException $e) {
                $error = $e->getMessage();
                printIndex(1);
            }

            unlink($sql_file);

            // Update schema to the new value
            updateSchema($bdd, $migration_value);
        }

        // Generate the hash
        $hash_pass = hashPass($admin_pass);

        // Insert the new admin
        $req = $bdd->prepare('INSERT INTO admin (admin_id, admin_pass) VALUES (?, ?)');
        $req->execute([$admin_username, $hash_pass]);

        rmdir(__DIR__ . '/../sql');
        $success = 'Well done, OpenVPN-Admin is installed. Redirection.';
        header('refresh:3;url=index.php?admin');
    } else {
        // Print the installation form
        $content .= renderTemplate(__DIR__ . '/../include/template/component/menu.php');
        $content .= renderTemplate(__DIR__ . '/../include/template/component/form/installation.php');
    }

    printIndex();
}


if (!isset($_GET['admin'])) {
    // --------------- CONFIGURATION ---------------
    $content .= renderTemplate(__DIR__ . '/../include/template/component/menu.php', ['is_admin' => false]);
    $content .= renderTemplate(__DIR__ . '/../include/template/component/form/configuration.php');
} else if (!isset($_SESSION['admin_id'])) {
    // --------------- LOGIN ---------------
    $content .= renderTemplate(__DIR__ . '/../include/template/component/menu.php', ['is_admin' => true]);
    $content .= renderTemplate(__DIR__ . '/../include/template/component/form/login.php');
} else {
    // --------------- GRIDS ---------------
    $content .= renderTemplate(__DIR__ . '/../include/template/component/header.php', [
        'admin_id' => $_SESSION['admin_id']
    ]);

    $content .= renderTemplate(__DIR__ . '/../include/template/component/grids.php');
}

printIndex();
