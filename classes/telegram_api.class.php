<?php
require_once CLASS_DIR . 'curl.class.php';

class TelegramApi {

    protected $curl;
    private $token;
    private $chatId;
    private $parseMode;

    public function __construct() {
        $this->curl = new Curl();
        $this->parseMode = 'HTML';
    }

    public function sendMessage( string $message = 'The Message is empty' ) {
        $settings = [
            'method'     => 'sendMessage',
            'chat_id'    => $this->chatId,
            'parse_mode' => $this->parseMode,
            'text'       => $message,
        ];

        $url     = "https://api.telegram.org/bot{$this->token}/{$settings['method']}";
        $options = [
            ['option' => CURLOPT_HTTPHEADER, 'value' => ['Content-Type: application/json']],
            ['option' => CURLOPT_POSTFIELDS, 'value' => json_encode($settings)],
        ];

        $sendByCurl = $this->curl->send( $url, $options );
        $response   = json_decode($sendByCurl);

        if( !$response->ok ) {
            return $response;
        }

        return $response->ok;
    }

    public function sendPhoto($path, $caption) {
        $settings = [
            'method' => 'sendPhoto',
            'chat_id' => $this->chatId,
            'photo' => new CURLFile(realpath($path)),
            'caption' => $caption
        ];
        $url     = "https://api.telegram.org/{$this->token}/{$settings['method']}";
        $options = [
            ['option' => CURLOPT_HTTPHEADER, 'value' => ['Content-Type:multipart/form-data']],
            ['option' => CURLOPT_POSTFIELDS, 'value' => $settings],
        ];
     
        $sendByCurl = $this->curl->send( $url, $options );
        $response   = json_decode($sendByCurl);

        if( !$response->ok ) {
            return $response;
        }

        return $response->ok;
    }

    public function setToken( string $token ) {
        $this->token = $token;
    }

    public function setChatId( $chatId ) {
        $this->chatId = $chatId;
    }

    public function setParseMode( string $parseMode ) {
        $this->parseMode = $parseMode;
    }
}
