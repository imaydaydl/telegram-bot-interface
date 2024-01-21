<?php
require_once CLASS_DIR . 'telegram_api.class.php';
require_once CLASS_DIR . 'telegram_webhook.class.php';

class Telegram {
    protected $query;
    protected $view;
    protected $db;
    protected $config;
    protected $user;
    private $post;

    public function __construct($query, $post = array()) {
        global $db, $config, $user;

        $this->query = $query;
        $this->db = $db;
        $this->config = $config;
        $this->user = $user;
        if(!empty($post)) $this->post = $post;
    }

    public function run() {
        switch($this->query) {
            default:
            case '':
            case 'message':
                $this->index();
                break;
            case 'webhook':
                $this->webhook();
                break;
        }
    }

    private function index() {
        $tg = new TelegramApi();
        $tg->setToken($this->config['bot_token']);

        if($this->post['reciever'] != null) {
            if($this->post['reciever'] == 'all') {
                $recievers = $this->db->superQuery("SELECT * FROM telegram_users", true);
            } else {
                $recievers = $this->db->superQuery("SELECT * FROM telegram_users WHERE chat_id IN ({$this->post['reciever']})", true);
            }

            $r_count = count($recievers);
            $sended = 0;
    
            for ($i = 0; $i < $r_count; $i++) {
                $r = $recievers[$i];
                $tg->setChatId($r['chat_id']);

                $send = $tg->sendMessage($this->post['message']);

                $this->writeMessageLog($r['chat_id'], $send);

                $sended++;
            }

            if($sended == $r_count) {
                return true;
            }
        } else {
            $tg->setChatId($this->config['chat_id']);
            $send = $tg->sendMessage($this->post['message']);

            $this->writeMessageLog($this->config['chat_id'], $send);

            return true;
        }
        
    }

    private function webhook() {
        $tg = new TelegramWebhook();
        $result = $tg->run();

        $username = trim($result['username']);
        $chat_id = trim($result['chat_id']);
        $name = trim($result['name']);
        $old_id = trim($result['old_id']);
        $time = time();

        if($chat_id && $name && $result['text']) {
            $this->db->query("INSERT INTO telegram_log (username, chat_id, name, action, added) VALUES ('{$username}', '{$chat_id}', '{$name}', '{$result['text']}', '{$time}')");
        }

        if($chat_id == $old_id) return false;

        $this->db->query("INSERT INTO telegram_users (username, chat_id, name, added) VALUES ('{$username}', '{$chat_id}', '{$name}', '{$time}')");

	    return true;
    }

    private function writeMessageLog($chat_id, $status) {
        $time = time();
        $this->db->query("INSERT INTO telegram_messages (user_id, chat_ids, text, added, status) values ('{$this->user['id']}', '{$chat_id}', '{$this->post['message']}', '{$time}', '{$status}')");
    }
}
