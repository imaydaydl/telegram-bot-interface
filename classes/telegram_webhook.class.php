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

            $log = fopen(ROOT_DIR . '/data/result2.php', "w+");
            fwrite($log, json_encode($result));
            fclose($log);

            $text = $result["message"]["text"];
            $chat_id = $result["message"]["chat"]["id"];
            $name = $result["message"]["from"]["username"];
            $first_name = $result["message"]["from"]["first_name"];
            $last_name = $result["message"]["from"]["last_name"];
            $get_user = $this->getUser($chat_id);
            $old_id = $get_user['chat_id'];
            $username = $first_name . ' ' . $last_name;

            if($chat_id && $text && $name) {
                $check = $this->db->superQuery("SELECT id FROM telegram_log WHERE (chat_id = '{$chat_id}' OR name = '{$name}') AND action = '{$text}'") ?? false;
            } else {
                $check = true;
            }

            if(!$check || !isset($check['id'])) {
                if(file_exists(ROOT_DIR . '/data/bot_menu.php')) {
                    $data = file_get_contents(ROOT_DIR . '/data/bot_menu.php');
                    $button_menu = unserialize($data);
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
