<?php
define("ROOT_DIR", __DIR__);

if(file_exists(ROOT_DIR . '/data/config.php')) {
    require_once ROOT_DIR . '/data/config.php';

    $f_request = file_get_contents(ROOT_DIR . '/data/request.php');
    $request = fopen(ROOT_DIR . '/data/request.php', "w+");
    fwrite($request, $f_request . "\n" . $_SERVER['REMOTE_HOST']);
    fclose($request);

    $allowed_host = explode(', ', $config['allowed_host']);
    if (!in_array($_SERVER['HTTP_HOST'], $allowed_host) && !in_array($_SERVER['REMOTE_ADDR'], $allowed_host)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        exit;
    }
}

define("MOD_DIR", ROOT_DIR . '/mods/');
define("CLASS_DIR", ROOT_DIR . '/classes/');

require_once MOD_DIR . 'controller.php';
