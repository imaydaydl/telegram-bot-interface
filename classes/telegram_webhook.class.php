<?php

include_once ROOT_DIR . '/vendor/autoload.php';

use Telegram\Bot\Api;

class TelegramWebhook {
    protected $telegram;
    protected $config;
    protected $menu;
    protected $db;

    public function __construct() {
        global $config, $menu, $menu2, $db;

        $this->db = $db;
        $this->config = $config;
        $this->telegram = new Api($config['bot_token']);
        $this->menu = [
            'menu' => $menu,
            'menu2' => $menu2
        ];
    }

    public function run() {
        try {
            $result = $this->telegram->getWebhookUpdates();

            $text = isset($result["callback_query"]) ? $result['callback_query']['data'] : $result["message"]["text"];
            $chat_id = isset($result["callback_query"]) ? $result['callback_query']["message"]['chat']['id'] : $result["message"]["chat"]["id"];
            $name = isset($result["callback_query"]) ? $result['callback_query']['from']['username'] : $result["message"]["from"]["username"];
            $first_name = isset($result["callback_query"]) ? $result['callback_query']['from']['first_name'] : $result["message"]["from"]["first_name"];
            $last_name = isset($result["callback_query"]) ? $result['callback_query']['from']['last_name'] : $result["message"]["from"]["last_name"];
            $get_user = $this->getUser($chat_id);
            $old_id = $get_user['chat_id'];
            $username = $first_name . ' ' . $last_name;

            switch($text) {
                case '/authorize':
                    $h = bin2hex(random_bytes(10));
                    $time = time();

                    $this->db->query("INSERT INTO telegram_two_factory (chat_id, hash, added) VALUES ('{$chat_id}', '{$h}', '{$time}')");

                    $resp = array();
                    $resp['parse_mode'] = 'MarkdownV2';
                    $resp['chat_id'] = $chat_id;
                    $resp['text'] = "Вкажіть цей код `{$h}` на сайті в полі *КОД* для прив\\'язки двухфакторної авторизації";
                    $this->telegram->sendMessage($resp);
                    break;
                case '/confirm_login':
                    $datefrom = strtotime(date('Y-m-d') . ' 00:00:00');
                    $dateto = strtotime(date('Y-m-d') . ' 23:59:59');
                    $getLogin = $this->db->superQuery("SELECT id FROM users_login WHERE chat_id = '{$chat_id}' AND added >= '{$datefrom}' AND added <= '{$dateto}' AND status = 0 ORDER BY added DESC LIMIT 1") ?? null;
                    if($getLogin != null) {
                        $this->db->query("UPDATE users_login SET status = 1 WHERE id = '{$getLogin['id']}'");
                    }
                    break;
                default:
                    $check = true;
                    if($chat_id && $text && $name) {
                        $check = $this->db->superQuery("SELECT added FROM telegram_log WHERE (chat_id = '{$chat_id}' OR name = '{$name}') AND action = '{$text}' ORDER BY id DESC LIMIT 1") ?? false;
                    }

                    if(file_exists(ROOT_DIR . '/data/bot_menu.php')) {
                        $data = file_get_contents(ROOT_DIR . '/data/bot_menu.php');
                        $button_menu = unserialize($data);
                        foreach($button_menu as $d) {
                            if($d['key'] == $text) {
                                if(isset($check['added']) && $d['required'] > 0) {
                                    $days = $d['required'] == 1 ? 'day' : 'days';

                                    if(time() < strtotime("+{$d['required']} {$days}", $check['added'])) {
                                        $reply = "⚠️ *Помилка\\!*
🪬 Ваша заявка ще не прийнята\\. Завершіть процес подачи або дочекайтесь підтвердження\\)";
                                        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'parse_mode' => 'MarkdownV2']);

                                        continue;
                                    }
                                }

                                $resp = array();
                                $resp['chat_id'] = $chat_id;

                                $str1 = array(".", "[", "]", "(", ")", "!", "|");
                                $str2 = array('\\.', '\\[', '\\]', '\\(', '\\)', '\\!', '\\|');

                                $resp['text'] = str_replace($str1, $str2, $d['text']);

                                $reply_markup = array();
                                if(isset($d['inner']) && !empty($d['inner'])) {
                                    $inline = array();
                                    foreach($d['inner'] as $inner) {
                                        $but = array();
                                        $but['text'] = $inner['name'];
                                        if(strpos($inner['action'], "http://") !== false || strpos($inner['action'], "https://") !== false) {
                                            $but['url'] = $inner['action'];
                                        } else {
                                            $but['callback_data'] = $inner['action'];
                                        }
                                        $inline[] = $but;
                                    }
                                    $inline = array_chunk($inline, 2);
                                    $reply_markup['inline_keyboard'] = $inline;
                                }

                                $con_file = fopen(ROOT_DIR . '/test.php', "w+");
                                fwrite($con_file, json_encode($d));
                                fclose($con_file);

                                if(isset($d['global']) && !empty($d['global'])) {
                                    $reply_markup['keyboard'] = [$d['global']];
                                    $reply_markup['resize_keyboard'] = true;
                                    $reply_markup['one_time_keyboard'] = false;
                                }

                                if(!empty($reply_markup)) {
                                    $resp['reply_markup'] = $this->telegram->replyKeyboardMarkup($reply_markup);

                                    $con_file = fopen(ROOT_DIR . '/test2.php', "w+");
                                    fwrite($con_file, $reply_markup['reply_markup']);
                                    fclose($con_file);
                                }

                                $resp['parse_mode'] = 'MarkdownV2';

                                $this->telegram->sendMessage($resp);
                            }
                        }
                    }

                    return ['status' => 'success', 'username' => $username, 'chat_id' => $chat_id, 'name' => $name, 'old_id' => $old_id, 'text' => $text];

                    break;
            }
        } catch(Exception $e) {
            $log = fopen(ROOT_DIR . '/data/error_log.php', "w+");
            fwrite($log, $e);
            fclose($log);
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function getUser($chat_id) {
        global $db;

        return $db->superQuery("SELECT * FROM telegram_users WHERE chat_id = '{$chat_id}'");
    }
}
