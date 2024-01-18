<?php

class Curl {
    public function send( string $url, array $settings = null ) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false );

        if( !is_null($settings) ) {
            foreach( $settings as $option ) {
                curl_setopt($curl, $option['option'], $option['value'] );
            }
        }

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
