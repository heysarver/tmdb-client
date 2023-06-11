<?php

class TMDb_API {
    private $api_base_url = 'https://api.themoviedb.org/3/';
    private $access_token;

    public function __construct($access_token) {
        $this->access_token = $access_token;
    }

    public function get($endpoint, $params = []) {
        $url = $this->api_base_url . $endpoint;
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json;charset=utf-8',
            ],
        ];

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return false;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
