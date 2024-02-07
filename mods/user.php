<?php
require_once CLASS_DIR . 'user.class.php';

class UserMod {
    protected $view;
    protected $query;
    protected $db;
    protected $config;
    private $post;
    protected $user;
    protected $currentUser;
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

    public function __construct($query = '', $post = array()) {
        global $view, $db, $user, $config;

        $this->view = $view;
        $this->query = $query;
        $this->db = $db;
        $this->post = $post;
        if($user) $this->currentUser = $user;
        $this->config = $config;
        $this->user = new User();
    }

    public function run() {
        switch($this->query) {
            default:
            case '':
            case 'login':
                $this->index();
                break;
            case 'autorize':
                $this->autorize();
                break;
            case 'add':
                $this->createUser();
                break;
            case 'userRules':
                $this->userRules();
                break;
            case 'edit':
                $this->editUser();
                break;
            case 'block':
                $this->blockUser();
                break;
            case 'profile':
                $this->profile();
                break;
            case 'saveProfile':
                $this->saveProfile();
                break;
            case 'logout':
                $this->clearSession();
                break;
            case 'check_two_step':
                $this->checkTwoStep();
                break;
        }
    }

    private function index() {
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';
        $view2->templ('child/login.html');
        $view2->compile('login');

        $this->view->content = $view2->result['login'];
        $this->view->user_name = 'Користувач';
        $this->view->title = 'Авторизація';

        $this->view->render('index.html');
    }

    private function autorize() {
        $createSession = $this->user->createUserSession($this->post);

        if(isset($createSession['error'])) {
            echo json_encode(['status' => 'error', 'message' => $createSession['error']]);
        } elseif(isset($createSession['status']) && $createSession['status'] == 'two_step') {
            echo json_encode(['status' => 'two_step', 'message' => 'У Вас увімкнено двухфакторну авторизацію. Підтвердіть вхід через телеграм бот', 'chat_id' => $createSession['chat_id']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $createSession['success']]);
        }
    }

    private function createUser() {
        if(!isset($this->currentUser['rules']['add_users'])) {
            echo json_encode(['status' => 'error', 'message' => 'У Вас немає прав на створення користувачів']);
            die();
        }

        $checkLogin = $this->db->superQuery("SELECT id FROM users WHERE login = '" . $this->post['new_login'] . "'");
        if($checkLogin && isset($checkLogin['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Користувач з таким логіном вже існує']);
            die();
        }

        $new_password = md5($this->post['password'] . $this->config['hash']);
        $time = time();

        $this->db->query("INSERT INTO users (login, password, name, second_name, reg_date, status) values ('{$this->post['new_login']}', '$new_password', '', '', '$time', 1)");
        $new_id = $this->db->insertId();

        $rules = array();
        foreach($this->post['rules'] as $rule) {
            $this->db->query("INSERT INTO users_have_access (user_id, access_id) values ($new_id, $rule)");
            $rules[] = $this->rules[$rule]['name'];
        }

        $resp = [
            'status' => 'success',
            'id' => $new_id,
            'reg_date' => date('Y-m-d H:i:s', $time),
            'rules' => implode('<br>', $rules)
        ];

        if(isset($this->currentUser['rules']['edit_users'])) {
            $resp['edit'] = true;
        } else {
            $resp['edit'] = false;
        }

        if(isset($this->currentUser['rules']['block_users'])) {
            $resp['block'] = true;
        } else {
            $resp['block'] = false;
        }

        echo json_encode($resp);
    }

    private function editUser() {
        if(!isset($this->currentUser['rules']['edit_users'])) {
            echo json_encode(['status' => 'error', 'message' => 'У Вас немає прав на редагування користувачів']);
            die();
        }

        $check = $this->db->superQuery("SELECT id FROM users WHERE id = '{$this->post['user_id']}'");
        if($check && $check['id']) {
            $check2 = $this->db->superQuery("SELECT id FROM users WHERE login = '{$this->post['login']}'");
            if($check2 && $check2['id'] != $this->post['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'Такий логін вже існує']);
            } else {
                $update = array();
                if(isset($this->post['name'])) {
                    $update[] = "`name` = '{$this->post['name']}'";
                }
                if(isset($this->post['second'])) {
                    $update[] = "`second_name` = '{$this->post['second']}'";
                }
                if(isset($this->post['login'])) {
                    $update[] = "`login` = '{$this->post['login']}'";
                }
                if(isset($this->post['password'])) {
                    $password = md5($this->post['password'] . $this->config['hash']);
                    $update[] = "`password` = '{$password}'";
                }

                $query = implode(',', $update);

                $this->db->query("UPDATE `users` SET " . $query . " WHERE `id` = '{$this->post['user_id']}'");
                $this->db->query("DELETE FROM `users_have_access` WHERE user_id = '{$this->post['user_id']}'");

                $rules = array();
                foreach($this->post['rules'] as $rule) {
                    $this->db->query("INSERT INTO users_have_access (user_id, access_id) values ('{$this->post['user_id']}', $rule)");
                    $rules[] = $this->rules[$rule]['name'];
                }

                echo json_encode(['status' => 'success', 'rules' => implode('<br>', $rules)]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Такого користувача не знайдено']);
        }
    }

    private function blockUser() {
        if(!isset($this->currentUser['rules']['block_users'])) {
            echo json_encode(['status' => 'error', 'message' => 'У Вас немає прав на блокування користувачів']);
            die();
        }

        $check = $this->db->superQuery("SELECT id, status FROM users WHERE id = '{$this->post['user_id']}'");
        if($check && $check['id']) {
            if($check['status'] == 1) {
                $new_status = 0;
            } else {
                $new_status = 1;
            }
            $this->db->query("UPDATE `users` SET `status` = '{$new_status}' WHERE id = '{$this->post['user_id']}'");

            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Такого користувача не знайдено']);
        }
    }

    private function userRules() {
        $user_rules = $this->db->superQuery("SELECT * FROM users_have_access WHERE user_id = '{$this->post['user_id']}'", true);
        $rules = array();
        foreach($user_rules as $ur) {
            $rules[] = $ur['access_id'];
        }

        echo json_encode($rules);
    }

    private function profile() {
        $getUserData = $this->db->superQuery("SELECT * FROM users WHERE id = '{$this->currentUser['id']}'");
        $view2 = new Template();
        $view2->path = ROOT_DIR . '/views/';

        $view2->templ('child/main_menu.html');
        foreach($this->rules as $ur) {
            if(isset($this->currentUser['rules'][$ur['alt_name']])) {
                $view2->set("[{$ur['alt_name']}]", "");
                $view2->set("[/{$ur['alt_name']}]", "");
            } else {
                $view2->setBlock("'\\[{$ur['alt_name']}\\](.*?)\\[/{$ur['alt_name']}\\]'si", '');
            }
        }
        $view2->compile('menu');

        $view2->templ('child/profile.html');
        $view2->menu = $view2->result['menu'];
        $view2->id = $getUserData['id'];
        $view2->name = $getUserData['name'];
        $view2->second = $getUserData['second_name'];
        $view2->login = $getUserData['login'];
        $view2->reg_date = date('Y-m-d H:i:s', $getUserData['reg_date']);
        $view2->status = $getUserData['status'] == 1 ? '<span class="text-success">Активний</span>' : '<span class="text-danger">Заблокований</span>';
        $view2->compile('profile');

        $this->view->content = $view2->result['profile'];
        $this->view->user_name = $this->currentUser['login'];
        $this->view->title = 'Профіль';

        $this->view->render('index.html');
    }

    private function checkTwoStep() {
        $createSession = $this->user->createUserSessionTwoStep($this->post['chat_id']);

        if(isset($createSession['error'])) {
            echo json_encode(['status' => 'error', 'message' => $createSession['error']]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $createSession['success']]);
        }
    }

    private function saveProfile() {
        $old_pass = $this->post['old_pass'] ?? '';
        $user_id = $this->post['user_id'];

        if($old_pass == '') {
            echo json_encode(['status' => 'error', 'message' => 'Старий пароль не може бути пустим']);
        } else {
            $mdold_pass = md5($old_pass . $this->config['hash']);
            $check = $this->db->superQuery("SELECT password, two_step FROM users WHERE id = '{$user_id}'");
            if($mdold_pass != $check['password']) {
                echo json_encode(['status' => 'error', 'message' => 'Старий пароль не правильний']);
            } else {
                $check2 = $this->db->superQuery("SELECT id FROM users WHERE login = '{$this->post['login']}'");
                if($check2 && $check2['id'] != $user_id) {
                    echo json_encode(['status' => 'error', 'message' => 'Такий логін вже існує']);
                } else {
                    $mdpassword = $this->post['password'] ? md5($this->post['password'] . $this->config['hash']) : $mdold_pass;
                    $chat_id = '';
                    if($check['two_step'] != $this->post['two_step'] && $this->post['two_step'] == 1) {
                        $chat_query = $this->db->superQuery("SELECT chat_id FROM telegram_two_factory WHERE hash = '{$this->post['two_factory_code']}' ORDER BY added DESC LIMIT 1") ?? null;
                        if($chat_query != null) $chat_id = ", two_step_chat_id = '{$chat_query['chat_id']}'";
                    }
                    $this->db->query("UPDATE users SET `name` = '{$this->post['name']}', `second_name` = '{$this->post['second']}', `password` = '{$mdpassword}', `login` = '{$this->post['login']}', `two_step` = '{$this->post['two_step']}'{$chat_id} WHERE id = '{$user_id}'");
                    echo json_encode(['status' => 'success']);
                }
            }
        }
    }

    public function getUser($user_id) {
        $user = $this->user->getUserData($user_id);

        if($user['status'] == 0) {
            $this->clearSession();
            return null;
        }

        if($user) return $user;
        else return null;
    }

    public function clearSession() {
        $this->user->clearSession();
        if($_SERVER['REQUEST_URI'] != '/login') {
            header('Location: /login');
        }
    }

    public function getRules() {
        return $this->rules;
    }
}
