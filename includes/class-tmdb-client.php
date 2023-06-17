<?php
require_once plugin_dir_path(__FILE__) . 'class-tmdb-client-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'class-tmdb-client-admin.php';

class TMDb_Client {
    public function __construct() {
        $this->init();
    }

    private function init() {
        $this->shortcodes = new TMDb_Client_Shortcodes($this);
        $this->admin = new TMDb_Client_Admin($this);
    }

    public function run() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function get_template_output($template_name, $atts, $variables = array()) {
        $template = locate_template("tmdb-client/{$template_name}.php");
        
        if (!$template) {
            $template = plugin_dir_path(__FILE__) . "../templates/{$template_name}.php";
        }
    
        extract($variables); // Extract variables to be used in the template
        ob_start();
        include $template;
        return ob_get_clean();
    }
}
