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

            $webhook_file = fopen(ROOT_DIR . '/data/webhook.php', "w+");
            fwrite($webhook_file, $result);
            fclose($webhook_file);

            $text = isset($result["callback_query"]) ? $result['callback_query']['data'] : $result["message"]["text"];
            $chat_id = isset($result["callback_query"]) ? $result['callback_query']["message"]['chat']['id'] : $result["message"]["chat"]["id"];
            $name = isset($result["callback_query"]) ? $result['callback_query']['from']['username'] : $result["message"]["from"]["username"];
            $first_name = isset($result["callback_query"]) ? $result['callback_query']['from']['first_name'] : $result["message"]["from"]["first_name"];
            $last_name = isset($result["callback_query"]) ? $result['callback_query']['from']['last_name'] : $result["message"]["from"]["last_name"];
            $get_user = $this->getUser($chat_id);
            $old_id = $get_user['chat_id'];
            $username = $first_name . ' ' . $last_name;

            $webhook_r_file = fopen(ROOT_DIR . '/data/webhook_r.php', "w+");
            fwrite($webhook_r_file, $text);
            fclose($webhook_r_file);

            if($chat_id && $text && $name) {
                $check = $this->db->superQuery("SELECT id, added FROM telegram_log WHERE (chat_id = '{$chat_id}' OR name = '{$name}') AND action = '{$text}'") ?? false;
            } else {
                $check = true;
            }

            switch($text) {
                case '/authorize':
                    try {
                        $webhook_r2_file = fopen(ROOT_DIR . '/data/webhook_r2.php', "w+");
                        fwrite($webhook_r2_file, $text);
                        fclose($webhook_r2_file);
                        $h = bin2hex(random_bytes(10));
                        $time = time();

                        $this->db->query("INSERT INTO telegram_two_factory (chat_id, hash, added) VALUES ('{$chat_id}', '{$h}'), '{$time}'");

                        $resp = array();
                        $resp['parse_mode'] = 'MarkdownV2';
                        $resp['chat_id'] = $chat_id;
                        $resp['text'] = "Вкажіть цей код `{$h}` на сайті в полі *КОД* для прив\\'язки двухфакторної авторизації";
                        $tg = $this->telegram->sendMessage($resp);
                        $webhook_r4_file = fopen(ROOT_DIR . '/data/webhook_r4.php', "w+");
                        fwrite($webhook_r4_file, $tg);
                        fclose($webhook_r4_file);
                    } catch(Exception $e) {
                        $webhook_r3_file = fopen(ROOT_DIR . '/data/webhook_r3.php', "w+");
                        fwrite($webhook_r3_file, $e->getMessage());
                        fclose($webhook_r3_file);
                    }
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
                    if(file_exists(ROOT_DIR . '/data/bot_menu.php')) {
                        $data = file_get_contents(ROOT_DIR . '/data/bot_menu.php');
                        $button_menu = unserialize($data);
                        foreach($button_menu as $d) {
                            if($d['key'] == $text && isset($check['added'])) {
                                if($d['required'] > 0) {
                                    if($d['required'] == 1) {
                                        $days = 'day';
                                    } else {
                                        $days = 'days';
                                    }
                                    if($check['added'] >= strtotime("+{$d['required']} {$days}")) {
                                        $check = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!$check || !isset($check['id'])) {
                        if(file_exists(ROOT_DIR . '/data/bot_menu.php')) {
                            foreach($button_menu as $d) {
                                if($d['key'] == $text) {
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

                                    if(isset($d['global']) && !empty($d['global'])) {
                                        $reply_markup['keyboard'] = $d['global'];
                                        $reply_markup['resize_keyboard'] = true;
                                        $reply_markup['one_time_keyboard'] = false;
                                    }

                                    if(!empty($reply_markup)) {
                                        $resp['reply_markup'] = $this->telegram->replyKeyboardMarkup($reply_markup);
                                    }

                                    $resp['parse_mode'] = 'MarkdownV2';

                                    $this->telegram->sendMessage($resp);
                                }
                            }
                        }

                        return ['status' => 'success', 'username' => $username, 'chat_id' => $chat_id, 'name' => $name, 'old_id' => $old_id, 'text' => $text];
                    } else {
                        $reply = "⚠️ *Ошибка\\!*
🪬 Ваша заявка еще не принята\\. Завершите процес подачи или дождитесь подтверждения\\)";
                        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'parse_mode' => 'MarkdownV2']);
                    }
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
