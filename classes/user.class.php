<?php
require_once CLASS_DIR . 'session.class.php';

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

        $user_data = [
            "login" => $user['login'],
            "password" => $user['password'],
            "user_id" => $user['id'],
            "date" => date('Y-m-d')
        ];

        $this->session->setUserdata($user_data);

        return ['success' => 'Успішний вхід в акаунт'];
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
