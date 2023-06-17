<?php
/**
 * Plugin Name: TMDb-Client
 * Plugin URI: https://github.com/heysarver/tmdb-client
 * Description: A WordPress plugin to interact with the TMDb API.
 * Version: 0.0.1
 * Author: Andrew Sarver
 * Author URI: https://andrewsarver.com
 */

if (!defined('WPINC')) {
    wp_die('Direct Access Not Allowed');
}

require_once plugin_dir_path(__FILE__) . 'includes/class-tmdb-client.php';

function run_tmdb_client() {
    $plugin = new TMDb_Client();
    //$plugin->run();
}

run_tmdb_client();
