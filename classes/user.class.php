<?php
require_once CLASS_DIR . 'session.class.php';
include_once ROOT_DIR . '/vendor/autoload.php';

use Telegram\Bot\Api;
class User {
    protected $session;
    protected $config;
    protected $db;
    protected $rules = [
        1 => [
            'alt_name' => 'send_message',
            'name' => 'Відправка повідомлення'
        ],
        2 => [
            'alt_name' => 'history',
            'name' => 'Історія повідомлень'
        ],
        3 => [
            'alt_name' => 'log',
            'name' => 'Лог користувачів'
        ],
        4 => [
            'alt_name' => 'user_list',
            'name' => 'Список користувачів'
        ],
        5 => [
            'alt_name' => 'settings',
            'name' => 'Налаштування'
        ],
        6 => [
            'alt_name' => 'add_users',
            'name' => 'Додавати користувачів'
        ],
        7 => [
            'alt_name' => 'edit_users',
            'name' => 'Редагувати користувачів'
        ],
        8 => [
            'alt_name' => 'block_users',
            'name' => 'Блокувати користувачів'
        ]
    ];

    public function __construct() {
        global $db, $config;

        $this->session = new Session();
        $this->config = $config;
        $this->db = $db;
    }

    public function createUserSession($post) {
        $this->clearSession();

        if(!isset($post['login'])) {
            return ['error' => 'Не отримано логін'];
        }

        if(!isset($post['password'])) {
            return ['error' => 'Не отримано пароль'];
        }

        $user = $this->db->superQuery("SELECT * FROM users WHERE login = '{$post['login']}'");

        if(!$user) {
            return ['error' => 'Користувача із таким логіном не знайдено'];
        }

        if($user && $user['password'] != md5($post['password'] . $this->config['hash'])) {
            return ['error' => 'Не правильний пароль'];
        }

        if($user && $user['status'] == 0) {
            return ['error' => 'Цей логін заблоковано'];
        }

        $time = time();
        $this->db->query("INSERT INTO users_login (user_id, added) VALUES ('{$user['id']}', '{$time}')");
        $login_id = $this->db->insertId();

        if($user && $user['two_step'] == 1) {
            $this->db->query("UPDATE users_login SET is_two_factory = 1, chat_id = '{$user['two_step_chat_id']}' WHERE id = '{$login_id}'");
            $telegram = new Api($this->config['bot_token']);
            $resp = array();
            $resp['parse_mode'] = 'MarkdownV2';
            $resp['chat_id'] = $user['two_step_chat_id'];
            $resp['text'] = "Ви підтверджуєте вхід в систему\\?";
            $inline = array();
            $but = array();
            $but['text'] = "✅ Підтверджую";
            $but['callback_data'] = "/confirm_login";
            $inline[] = $but;
            $inline = array_chunk($inline, 2);
            $reply_markup['inline_keyboard'] = $inline;
            $resp['reply_markup'] = json_encode($reply_markup);
            $telegram->sendMessage($resp);

            return ['status' => 'two_step', 'chat_id' => $user['two_step_chat_id']];
        }

        $user_data = [
            "login" => $user['login'],
            "password" => $user['password'],
            "user_id" => $user['id'],
            "date" => date('Y-m-d')
        ];

        $this->session->setUserdata($user_data);

        $this->db->query("UPDATE users_login SET status = 1 WHERE id = '{$login_id}'");

        return ['success' => 'Успішний вхід в акаунт'];
    }

    public function createUserSessionTwoStep($chat_id) {
        try {
            $datefrom = strtotime(date('Y-m-d') . ' 00:00:00');
            $dateto = strtotime(date('Y-m-d') . ' 23:59:59');

            $getLogin = $this->db->superQuery("SELECT id, status FROM users_login WHERE chat_id = '{$chat_id}' AND added >= '{$datefrom}' AND added <= '{$dateto}' ORDER BY added DESC LIMIT 1") ?? null;
            if($getLogin != null && $getLogin['status'] == 1) {
                $user = $this->db->superQuery("SELECT * FROM users WHERE two_step_chat_id = '{$chat_id}'");

                $user_data = [
                    "login" => $user['login'],
                    "password" => $user['password'],
                    "user_id" => $user['id'],
                    "date" => date('Y-m-d')
                ];

                $this->session->setUserdata($user_data);

                return ['success' => 'Успішний вхід в акаунт'];
            }
        } catch(Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function clearSession() {
        $this->session->unsetUserdata([
            'login',
            'password',
            'user_id',
            'date'
        ]);

        return true;
    }

    public function getUserData($user_id) {
        $user_data = $this->db->superQuery("SELECT * FROM users WHERE id = '{$user_id}'");
        $users_rules = $this->db->superQuery("SELECT access_id FROM users_have_access WHERE user_id = '{$user_id}'", true);

        foreach($users_rules as $ur) {
            $user_data['rules'][$this->rules[$ur['access_id']]['alt_name']] = [
                'acces_id' => $ur['access_id'],
                'name' => $this->rules[$ur['access_id']]['name']
            ];
        }

        return $user_data;
    }
}
