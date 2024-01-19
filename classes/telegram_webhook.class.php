<?php

include_once ROOT_DIR . '/vendor/autoload.php';
include_once ROOT_DIR . '/data/bot_menu.php';

use Telegram\Bot\Api;

class TelegramWebhook {
    protected $telegram;
    protected $config;
    protected $menu;

    public function __construct() {
        global $config, $menu, $menu2;

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

            $text = $result["message"]["text"];
            $chat_id = $result["message"]["chat"]["id"];
            $name = $result["message"]["from"]["username"];
            $first_name = $result["message"]["from"]["first_name"];
            $last_name = $result["message"]["from"]["last_name"];
            $get_user = $this->getUser($chat_id);
            $old_id = $get_user['chat_id'];
            $username = $first_name . ' ' . $last_name;

            switch($text) {
                default:
                case '/start':
                    $reply = "Menu: ";
                    $reply_markup = $this->telegram->replyKeyboardMarkup([ 'keyboard' => $this->menu['menu'], 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
                    $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
                    break;
                case 'button 1':
                    $img = 'img_url';
                    $reply = "Hello " . $first_name . " " . $last_name;
                    $reply_markup = $this->telegram->replyKeyboardMarkup([ 'keyboard' => $this->menu['menu'], 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
                    $this->telegram->sendPhoto(['chat_id' => $chat_id, 'photo' => $img, 'caption' => $reply, 'parse_mode' => 'HTML']);
                    break;
                case 'button 2':
                    $reply = "Hello " . $first_name . " " . $last_name . " it's button 2";
                    $reply_markup = $this->telegram->replyKeyboardMarkup([ 'keyboard' => $this->menu['menu2'], 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
                    $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
                    break;
                case 'Google News':
                    $reply = "Наука и технологии: \n\n";
                    $xml = simplexml_load_file('https://news.google.com/rss/topics/CAAqKAgKIiJDQkFTRXdvSkwyMHZNR1ptZHpWbUVnSnlkUm9DVlVFb0FBUAE?hl=ru&gl=UA&ceid=UA%3Aru');
                    $i = 0;
                    foreach ($xml->channel->item as $item) {
                        $i++;
                        if($i > 10){
                            break;
                        }
                        $reply .= "\xE2\x9E\xA1 ".$item->title."\nДата: ".$item->pubDate."(<a href='".$item->link."'>Читать полностью</a>)\n\n";
                    }
                    $this->telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true, 'text' => $reply ]);
                    break;
                case 'Inline':
                    $reply = "Inline keyboard";
                    $inline[] = ['text'=>'Test', 'url'=>$_SERVER['HTTP_HOST']];
                    $inline[] = ['text'=>'Test Chat', 'url' => $this->config['tg_url']];
                    $inline = array_chunk($inline, 2);
                    $reply_markup = ['inline_keyboard'=>$inline];
                    $inline_keyboard = json_encode($reply_markup);
                    $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $inline_keyboard]);
                    break;
            }

            return ['username' => $username, 'chat_id' => $chat_id, 'name' => $name, 'old_id' => $old_id, 'text' => $text];
        } catch(Exception $e) {
            $log = fopen(ROOT_DIR . '/log.php', "w+");
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
