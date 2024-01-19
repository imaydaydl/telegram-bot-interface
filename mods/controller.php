<?php
session_start();
require_once CLASS_DIR . 'template.class.php';
require_once CLASS_DIR . 'mysql.class.php';

if(file_exists(ROOT_DIR . '/data/db.php')) {
    require_once MOD_DIR . 'user.php';
    require_once ROOT_DIR . '/data/db.php';
}

$request = $_SERVER['REQUEST_URI'];
$mainRequest = explode('/', $request)[1];

if(!file_exists(ROOT_DIR . '/data/db.php') && !in_array($mainRequest, ['install', 'getMainData'])) {
    header('Location: /install');
}

if(file_exists(ROOT_DIR . '/data/db.php')) {
    $userM = new UserMod();
    $rules_name = $userM->getRules();
    $user = false;

    if(isset($_SESSION['user_id'])) {
        $user = $userM->getUser($_SESSION['user_id']);

        if($user == null || $user['password'] != $_SESSION['password']) {
            $userM->clearSession();
            header('Location: /login');
        }
    }

    if((!isset($_SESSION['user_id']) || (isset($_SESSION['user_id']) && $_SESSION['date'] < date('Y-m-d'))) && !in_array($mainRequest, ['install', 'getMainData', 'login', 'bot'])) {
        $userM->clearSession();
        header('Location: /login');
    }
}

$view = new Template();
$view->path = ROOT_DIR . '/views/';

switch($mainRequest) {
    case 'profile':
    case 'user':
    case 'login':
        if(isset(explode('/', $request)[2]) && explode('/', $request)[2] !== '') $mainRequest = explode('/', $request)[2];
        $q = new UserMod($mainRequest, $_POST);
        break;
    case '':
    case '/':
    case 'history':
    case 'users':
    case 'log':
    case 'settings':
        if(isset(explode('/', $request)[2]) && explode('/', $request)[2] !== '') $mainRequest = explode('/', $request)[2];
        require_once MOD_DIR . 'home.php';
        $q = new Home($mainRequest, $_POST);
        break;
    case 'bot':
        if(isset(explode('/', $request)[2]) && explode('/', $request)[2] !== '') $mainRequest = explode('/', $request)[2];
        require_once MOD_DIR . 'telegram.php';
        $q = new Telegram($mainRequest, $_POST);
        break;
    case 'install':
        if(file_exists(ROOT_DIR . '/data/db.php') && file_exists(ROOT_DIR . '/data/config.php')) {
            header('Location: /');
        }
        require_once MOD_DIR . 'installer.php';
        if(isset(explode('/', $request)[2]) && explode('/', $request)[2] !== '') $mainRequest = explode('/', $request)[2];
        if(!empty($_POST)) {
            $q = new Installer($mainRequest, $_POST);
        } else {
            $q = new Installer($mainRequest);
        }
        break;
    default:
        http_response_code(404);
        break;
}

$res = $q->run();
echo $res;
