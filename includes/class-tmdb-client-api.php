<?php
class TMDb_Client_API {
    private $bearer_token;
    private $api_base_url = 'https://api.themoviedb.org/3';
    protected $parent;

    public function __construct($parent) {
        $this->parent = $parent;
        $this->bearer_token = get_option('tmdb_client_bearer_token');
    }

    public function tmdb_api_request($endpoint, $query_params = array()) {
        $url = $this->api_base_url . $endpoint;
        $url .= '?' . http_build_query($query_params);
    
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->bearer_token,
            ),
        ));
    
        if (is_wp_error($response)) {
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
