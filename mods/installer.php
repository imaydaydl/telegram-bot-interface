<?php

session_start();

class Installer {
    protected $view;
    protected $query;
    protected $fileDb;
    protected $configFile;
    protected $configFolder;
    public $post;

    public function __construct($query, $post = array()) {
        global $view;

        $this->view = $view;
        $this->configFolder = ROOT_DIR . '/data/';
        $this->fileDb = ROOT_DIR . '/data/db.php';
        $this->configFile = ROOT_DIR . '/data/config.php';
        $this->query = $query;
        $this->post = $post;
    }

    public function run() {
        switch($this->query) {
            default:
            case '':
                $this->index();
                break;
            case 'create_db':
                return $this->createDb();
                break;
            case 'create_config':
                return $this->createConfig();
                break;
            case 'creat_connect':
                $this->creatConnect();
                break;
        }
    }

    private function index() {
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';
        $view2->templ('child/install.html');

        if(!file_exists($this->fileDb)) {
            $status_db = "<span class=\"status_db text-danger\">Не знайдено!</span>";
        } elseif(is_writable($this->fileDb)) {
            $status_db = "<span class=\"status_db text-success\">Дозволено</span>";
        } else {
            @chmod($this->fileDb, 0777);
            if(is_writable($this->fileDb)) {
                $status_db = "<span class=\"status_db text-success\">Дозволено</span>";
            } else {
                @chmod($this->fileDb, 0755);
                if(is_writable($this->fileDb)) {
                    $status_db = "<span class=\"status_db text-success\">Дозволено</span>";
                } else {
                    $status_db = "<span class=\"status_db text-danger\">Заборонено</span>";
                }
            }
        }

        if(!file_exists($this->configFolder)) {
            $status_folder = "<span class=\"status_folder text-danger\">Не знайдено!</span>";
        } elseif(is_writable($this->configFolder)) {
            $status_folder = "<span class=\"status_folder text-success\">Дозволено</span>";
        } else {
            @chmod($this->configFolder, 0777);
            if(is_writable($this->configFolder)) {
                $status_folder = "<span class=\"status_folder text-success\">Дозволено</span>";
            } else {
                @chmod($this->configFolder, 0755);
                if(is_writable($this->configFolder)) {
                    $status_folder = "<span class=\"status_folder text-success\">Дозволено</span>";
                } else {
                    $status_folder = "<span class=\"status_folder text-danger\">Заборонено</span>";
                }
            }
        }

        if(!file_exists($this->configFile)) {
            $status_config = "<span class=\"configFile text-danger\">Не знайдено!</span>";
        } elseif(is_writable($this->configFile)) {
            $status_config = "<span class=\"configFile text-success\">Дозволено</span>";
        } else {
            @chmod($this->configFile, 0777);
            if(is_writable($this->configFile)) {
                $status_config = "<span class=\"configFile text-success\">Дозволено</span>";
            } else {
                @chmod($this->configFile, 0755);
                if(is_writable($this->configFile)) {
                    $status_config = "<span class=\"configFile text-success\">Дозволено</span>";
                } else {
                    $status_config = "<span class=\"configFile text-danger\">Заборонено</span>";
                }
            }
        }

        $view2->chmod_folder = @decoct(@fileperms($this->configFolder)) % 1000;
        $view2->status_folder = $status_folder;
        $view2->chmod_db = @decoct(@fileperms($this->fileDb)) % 1000;
        $view2->status_db = $status_db;
        $view2->chmod_config = @decoct(@fileperms($this->configFile)) % 1000;
        $view2->status_config = $status_config;

        $view2->compile('install');

        $this->view->content = $view2->result['install'];
        $this->view->user_name = 'Користувач';
        $this->view->title = 'Встановлення проекта';
        $this->view->year = date('Y') == '2024' ? date('Y') : '2024 - ' . date('Y');

        $this->view->render('index.html');
    }

    private function creatConnect() {
        global $config;

        if(!file_exists(ROOT_DIR . '/data/config.php')) {
            echo json_encode(['status' => 'error', 'message' => 'Спочатку встановіть файл конфігурації']);
            die();
        }

        if(file_exists(ROOT_DIR . '/data/config.php')) {
            if(!isset($config['bot_token']) || $config['bot_token'] == '') {
                echo json_encode(['status' => 'error', 'message' => 'Спочатку вкажіть токен бота']);
                die();
            } else {
                $url = "https://api.telegram.org/bot" . $config['bot_token'] . "/setWebHook?url=https://" . $_SERVER['HTTP_HOST'] . "/bot/webhook";
                echo json_encode(['status' => 'success', 'url' => $url]);
            }
        }
    }

    private function createDb() {
        if(!file_exists(ROOT_DIR . '/data/config.php')) {
            return json_encode(['status' => 'error', 'message' => 'Спочатку створіть файл конфігурації']);
        } else {
            include_once ROOT_DIR . '/classes/mysql.class.php';

            $dbhost = $this->post['host'];
            $dbname = $this->post['db'];
            $dbuser = $this->post['user'];
            $dbpasswd = $this->post['pass'];

            $check_db = new db;
            define ("COLLATE", "utf8mb4");
            if ( !$check_db->connect($dbuser, $dbpasswd, $dbname, $dbhost, false) ) {
                return json_encode(["status" => "error", "message" => "Неможливо підключитись до БД із вказаними даними."]);
            }

            $db = <<<HTML
            <?php

            define ("DB_HOST", "{$dbhost}");

            define ("DB_NAME", "{$dbname}");

            define ("DB_USER", "{$dbuser}");

            define ("DB_PASS", "{$dbpasswd}");

            if(!defined("COLLATE")) define ("COLLATE", "utf8mb4");

            \$db = new db;

            \$db->connect(DB_USER, DB_PASS, DB_NAME, DB_HOST);

            HTML;

            $con_file = fopen($this->fileDb, "w+") or die("Неможливо створити файл <b>.data/db.php</b>.<br />Перевірте правильність CHMOD!");
            fwrite($con_file, $db);
            fclose($con_file);
            @chmod($this->fileDb, 0666);

            $fillDb = $this->fillDb() ?? null;

            if($fillDb && isset($fillDb['status']) && $fillDb['status'] == 'error') {
                return json_encode($fillDb);
            } else {
                return json_encode(['status' => 'success', 'file_status' => is_writable($this->fileDb), 'chmod' => @decoct(@fileperms($this->fileDb)) % 1000]);
            }
        }
    }

    private function createConfig() {
        try {
            $config = <<<HTML
            <?php

            \$config = array(

            'allowed_host' => '{$_SERVER['HTTP_HOST']}, https://api.telegram.org/, https://telegram.org/, 172.71.99.48',

            'hash' => '{$this->post['hash']}',

            'bot_token' => '{$this->post['tg_bot']}',

            'bot_url' => '{$this->post['tg_url']}'

            );

            HTML;

            $con_file = fopen($this->configFile, "w+") or die("Неможливо створити файл <b>.data/config.php</b>.<br />Перевірте правильність CHMOD!");
            fwrite($con_file, $config);
            fclose($con_file);
            @chmod($this->configFile, 0666);

            return json_encode(['status' => 'success', 'file_status' => is_writable($this->configFile), 'chmod' => @decoct(@fileperms($this->configFile)) % 1000]);
        } catch(Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function fillDb() {
        global $config;

        if(!file_exists(ROOT_DIR . '/data/db.php')) return;

        require_once ROOT_DIR . '/data/db.php';

        $storage_engine = "InnoDB";
        $collate = 'utf8mb4';
        $tableSchema = array();

        $tableSchema[] = "DROP TABLE IF EXISTS telegram_messages";

        $tableSchema[] = "CREATE TABLE telegram_messages (
            `id` mediumint(8) unsigned NOT NULL auto_increment,
            `user_id` mediumint(8) unsigned NOT NULL default 1,
            `chat_ids` longtext NOT NULL,
            `text` longtext default NULL,
            `added` int(10) default NULL,
            `status` int(1) default 0,
            PRIMARY KEY  (`id`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS telegram_users";

        $tableSchema[] = "CREATE TABLE telegram_users (
            `id` mediumint(8) unsigned NOT NULL auto_increment,
            `username` varchar(50) default NULL,
            `chat_id` int(111) NOT NULL,
            `name` varchar(50) NOT NULL default '',
            `added` int(10) default NULL,
            PRIMARY KEY  (`id`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS telegram_log";

        $tableSchema[] = "CREATE TABLE telegram_log (
            `id` mediumint(8) unsigned NOT NULL auto_increment,
            `username` varchar(50) default NULL,
            `chat_id` int(111) NOT NULL,
            `name` varchar(50) NOT NULL default '',
            `action` text default '',
            `added` int(10) default NULL,
            PRIMARY KEY  (`id`),
            KEY `added` (`added`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS telegram_two_factory";

        $tableSchema[] = "CREATE TABLE telegram_two_factory (
            `chat_id` int(111) unsigned NOT NULL,
            `hash` varchar(50) NOT NULL,
            `added` int(10) default NULL,
            PRIMARY KEY  (`chat_id`),
            KEY `added` (`added`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS users";

        $tableSchema[] = "CREATE TABLE users (
            `id` mediumint(8) unsigned NOT NULL auto_increment,
            `login` varchar(50) NOT NULL,
            `name` varchar(50) default NULL,
            `second_name` varchar(50) default NULL,
            `password` varchar(200) NOT NULL,
            `status` int(1) default 1,
            `two_step` int(1) default 0,
            `two_step_chat_id` int(111) default NULL,
            `reg_date` int(10) default NULL,
            PRIMARY KEY  (`id`),
            UNIQUE KEY `login` (`login`),
            UNIQUE KEY `two_step_chat_id` (`two_step_chat_id`),
            KEY `reg_date` (`reg_date`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS users_login";

        $tableSchema[] = "CREATE TABLE users_login (
            `id` mediumint(11) unsigned NOT NULL auto_increment,
            `user_id` mediumint(8) unsigned NOT NULL,
            `is_two_factory` int(1) default 0,
            `chat_id` int(111) default NULL,
            `status` int(1) default 0,
            `added` int(10) default NULL,
            PRIMARY KEY  (`id`),
            KEY `chat_id` (`chat_id`),
            KEY `user_id` (`user_id`),
            KEY `added` (`added`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $tableSchema[] = "DROP TABLE IF EXISTS users_have_access";

        $tableSchema[] = "CREATE TABLE users_have_access (
            `user_id` mediumint(8) unsigned NOT NULL,
            `access_id` mediumint(8) unsigned NOT NULL,
            UNIQUE KEY `user_id` (`user_id`,`access_id`),
            KEY `access_id` (`access_id`),
            KEY `user_id2` (`user_id`)
        ) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . $collate . " COLLATE " . $collate . "_general_ci";

        $pass_adm = md5($this->post['passwd'] . $config['hash']);
        $time = time();

        $tableSchema[] = "INSERT INTO users (id, login, password, name, second_name, reg_date, status) VALUES (1, '{$this->post['adm']}', '$pass_adm', '', '', '$time', 1)";
        $tableSchema[] = "INSERT INTO users_have_access (user_id, access_id) VALUES (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8)";

        $limit = count($tableSchema);
        $inserted = 0;

        for ( $i = 0; $i < $limit; $i++ ) {
            try {
                if ( isset( $tableSchema[$i] ) ) {
                    $db->query($tableSchema[$i]);
                    $inserted++;
                }
            } catch(Exception $e) {
                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        if($limit == $inserted) {
            return ['status' => 'success'];
        }
    }
}
