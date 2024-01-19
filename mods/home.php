<?php
    
class Home {
    protected $query;
    protected $view;
    protected $db;
    protected $config;
    protected $user;
    private $post;
    protected $rules = ['send_message', 'history', 'log', 'user_list', 'settings', 'add_users', 'edit_users', 'block_users'];

    public function __construct($query, $post = array()) {
        global $db, $config, $view, $user;

        $this->query = $query;
        $this->view = $view;
        $this->db = $db;
        $this->config = $config;
        $this->user = $user;
        $this->post = $post;
    }

    public function run() {
        switch($this->query) {
            default:
            case '':
                $this->index();
                break;
            case 'history':
                $this->getHistory();
                break;
            case 'log':
                $this->getLog();
                break;
            case 'users':
                $this->getUsers();
                break;
            case 'settings':
                $this->getSettings();
                break;
            case 'creat_connect':
                $this->creatConnect();
                break;
            case 'saveSettings':
                $this->saveSettings();
                break;
        }
    }

    private function index() {
        $this->view->title = 'Відправка повідомлення';
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        if(!isset($this->user['rules']['send_message'])) {
            $view2->templ('child/no_access.html');
            $view2->compile('tg_sends');
        } else {
            $view2->templ('child/send_message.html');
            $view2->compile('tg_sends');
        }

        $view2->templ('child/main.html');
        $view2->tg_sends = $view2->result['tg_sends'];
        foreach($this->rules as $ur) {
            $checker[] = $ur;
            if(isset($this->user['rules'][$ur])) {
                $view2->set("[$ur]", "");
                $view2->set("[/$ur]", "");
            } else {
                $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
            }
        }
        $view2->compile('main');

        $this->view->content = $view2->result['main'];
        $this->view->user_name = $this->user['login'];

        $this->view->render('index.html');
    }

    private function getHistory() {
        $this->view->title = 'Історія повідомлень';
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        if(!isset($this->user['rules']['history'])) {
            $view2->templ('child/no_access.html');
            $view2->compile('history');
        } else {
            $histories = $this->db->superQuery("SELECT tm.*, u.name, u.second_name, u.login FROM telegram_messages tm LEFT JOIN users u ON u.id = tm.user_id ORDER BY tm.id DESC LIMIT 20", true);
            if(count($histories) > 0) {
                $view2->templ('tabs/history.html');
                foreach($histories as $history) {
                    $sname = $history['second_name'] ? $history['second_name'] : '';
                    $fname = $history['name'] ? $history['name'] : '';
                    $fullname = $sname ? $sname . ' ' . ($fname ? $fname . ' ' : '') : ($fname != '' ? $fname . ' ' : '');
                    $name = $fullname != '' ? $fullname . '(' . $history['login'] . ')' : $history['login'];
                    $view2->name = $name;
                    $view2->text = $history['text'];
                    $view2->target = $history['chat_ids'] == '0' ? 'Всім' : $history['chat_ids'];
                    $view2->date = date('Y-m-d H:i:s', $history['added']);
                    $view2->result_class = $history['status'] == 1 ? 'text-success' : 'text-danger';
                    $view2->result_status = $history['status'] == 1 ? 'Успішно' : 'Не успішно';
                    $view2->compile('history');
                }
            } else {
                $view2->templ('tabs/no_history.html');
                $view2->compile('history');
            }
        }

        $view2->templ('child/main.html');
        $view2->history_tab = $view2->result['history'];
        foreach($this->rules as $ur) {
            $checker[] = $ur;
            if(isset($this->user['rules'][$ur])) {
                $view2->set("[$ur]", "");
                $view2->set("[/$ur]", "");
            } else {
                $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
            }
        }
        $view2->compile('main');

        $this->view->content = $view2->result['main'];
        $this->view->user_name = $this->user['login'];

        $this->view->render('index.html');
    }

    private function getLog() {
        $this->view->title = 'Лог бота';
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        if(!isset($this->user['rules']['log'])) {
            $view2->templ('child/no_access.html');
            $view2->compile('log');
        } else {
            $logs = $this->db->superQuery("SELECT * FROM telegram_log ORDER BY id DESC LIMIT 20", true);
            if(count($logs) > 0) {
                $view2->templ('tabs/log.html');
                foreach($logs as $log) {
                    $username = $log['username'] ? $log['username'] : '';
                    $name = $username != '' ? $username . '(' . $log['name'] . ')' : $log['name'];
                    $view2->name = $name;
                    $view2->action = 'Натиснув кнопку ' . $log['action'];
                    $view2->target = $log['chat_id'];
                    $view2->date = date('Y-m-d H:i:s', $log['added']);
                    $view2->compile('log');
                }
            } else {
                $view2->templ('tabs/no_log.html');
                $view2->compile('log');
            }
        }

        $view2->templ('child/main.html');
        $view2->log_tab = $view2->result['log'];
        foreach($this->rules as $ur) {
            $checker[] = $ur;
            if(isset($this->user['rules'][$ur])) {
                $view2->set("[$ur]", "");
                $view2->set("[/$ur]", "");
            } else {
                $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
            }
        }
        $view2->compile('main');

        $this->view->content = $view2->result['main'];
        $this->view->user_name = $this->user['login'];

        $this->view->render('index.html');
    }

    private function getUsers() {
        global $rules_name;

        $this->view->title = 'Список користувачів';
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        if(!isset($this->user['rules']['user_list'])) {
            $view2->templ('child/no_access.html');
            $view2->compile('users');
        } else {
            $users = $this->db->superQuery("SELECT * FROM users ORDER BY id ASC", true);
            $rules = $this->db->superQuery("SELECT * FROM users_have_access", true);
            if(count($users) > 0) {
                $view2->templ('tabs/users.html');
                $r = array();
                foreach($rules as $rule) {
                    $r[$rule['user_id']][] = $rules_name[$rule['access_id']]['name'];
                }
                foreach($users as $user) {
                    $view2->id = $user['id'];
                    $view2->name = $user['name'];
                    $view2->second = $user['second_name'];
                    $view2->login = $user['login'];
                    $view2->reg_date = date('Y-m-d H:i:s', $user['reg_date']);
                    $view2->rules = implode('<br>', $r[$user['id']]);
                    if($user['status'] == 1) {
                        $view2->bicon = 'fa-lock';
                        $view2->bbutton = 'btn-danger';
                        $view2->btitle = 'Заблокувати';
                    } else {
                        $view2->bicon = 'fa-lock-open';
                        $view2->bbutton = 'btn-success';
                        $view2->btitle = 'Розблокувати';
                    }
                    foreach($this->rules as $ur) {
                        $checker[] = $ur;
                        if(isset($this->user['rules'][$ur])) {
                            $view2->set("[$ur]", "");
                            $view2->set("[/$ur]", "");
                        } else {
                            $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
                        }
                    }
                    $view2->compile('users');
                }
            } else {
                $view2->templ('tabs/no_users.html');
                $view2->compile('users');
            }
        }
            
        $view2->templ('child/main.html');
        $view2->users_tab = $view2->result['users'];
        foreach($this->rules as $ur) {
            $checker[] = $ur;
            if(isset($this->user['rules'][$ur])) {
                $view2->set("[$ur]", "");
                $view2->set("[/$ur]", "");
            } else {
                $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
            }
        }
        $view2->compile('main');

        $this->view->content = $view2->result['main'];
        $this->view->user_name = $this->user['login'];

        $this->view->render('index.html');
    }

    private function getSettings() {
        $this->view->title = 'Налаштування';
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        if(!isset($this->user['rules']['settings'])) {
            $view2->templ('child/no_access.html');
            $view2->compile('settings');
        } else {
            $view2->templ('options/bot_buttons.html');
            $buttons = array();
            $buttons[] = [
                'value' => '/start',
                'name' => '/start'
            ];
            // if(file_exists(ROOT_DIR . '/data/bot_menu.php')) {
            //     require_once ROOT_DIR . '/data/bot_menu.php';
            //     foreach($buttons as $k => $d) {
            //         $buttons[] = [
            //             'value' => $k,
            //             'name' => $k
            //         ];
            //     }
            // }
            foreach($buttons as $b) {
                $view2->val = $b['value'];
                $view2->name = $b['name'];
                $view2->compile('buttons');
            }
            $view2->templ('child/settings.html');
            $view2->buttons = $view2->result['buttons'];
            $view2->bot_token = $this->config['bot_token'];
            $view2->tg_url = $this->config['bot_url'];
            $view2->chat_id = $this->config['chat_id'];
            $view2->compile('settings');
        }

        $view2->templ('child/main.html');
        $view2->setting_block = $view2->result['settings'];
        foreach($this->rules as $ur) {
            $checker[] = $ur;
            if(isset($this->user['rules'][$ur])) {
                $view2->set("[$ur]", "");
                $view2->set("[/$ur]", "");
            } else {
                $view2->setBlock("'\\[$ur\\](.*?)\\[/$ur\\]'si", '');
            }
        }
        $view2->compile('main');

        $this->view->content = $view2->result['main'];
        $this->view->user_name = $this->user['login'];

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

    private function saveSettings() {
        try {
            $config = <<<HTML
            <?php

            \$config = array(

            'allowed_host' => {$this->config['allowed_host']},

            'hash' => '{$this->config['hash']}',

            'bot_token' => '{$this->post['tg_bot']}',

            'bot_url' => '{$this->post['tg_url']}',

            'chat_id' => '{$this->post['chat_id']}'

            );

            HTML;

            $con_file = fopen(ROOT_DIR . '/data/config.php', "w+") or die("Неможливо створити файл <b>.data/config.php</b>.<br />Перевірте правильність CHMOD!");
            fwrite($con_file, $config);
            fclose($con_file);
            @chmod(ROOT_DIR . '/data/config.php', 0666);

            return json_encode(['status' => 'success']);
        } catch(Exception $e) {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
