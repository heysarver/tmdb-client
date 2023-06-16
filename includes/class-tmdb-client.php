<?php
class TMDb_Client {
    private $api_base_url = 'https://api.themoviedb.org/3';
    private $bearer_token;

    public function __construct() {
        $this->bearer_token = get_option('tmdb_client_bearer_token');
        $this->init();
    }

    private function init() {
        $this->register_shortcodes();
        $this->register_admin_page();
    }

    private function register_shortcodes() {
        $shortcodes = [
            'tmdb_person_movies_tv_shows' => 'shortcode_person_movies_tv_shows',
            'tmdb_person_details'         => 'shortcode_person_details',
            'tmdb_genres'                 => 'shortcode_genres',
            'tmdb_movie_tv_details'       => 'shortcode_movie_tv_details',
        ];

        foreach ($shortcodes as $code => $callback) {
            add_shortcode($code, [$this, $callback]);
        }
    }

    private function register_admin_page() {
        $actions = [
            'admin_menu'                 => 'add_admin_menu',
            'admin_init'                 => 'register_settings',
            'admin_post_tmdb_client_create_pages' => 'handle_create_pages_form',
        ];

        foreach ($actions as $action => $callback) {
            add_action($action, [$this, $callback]);
        }
    }

    public function handle_create_pages_form() {
        if (!isset($_POST['tmdb_client_create_pages_nonce']) || !wp_verify_nonce($_POST['tmdb_client_create_pages_nonce'], 'tmdb_client_create_pages')) {
            wp_die('Invalid nonce');
        }

        $person_id = intval($_POST['tmdb_client_person_id']);
        if ($person_id > 0) {
            $this->create_pages_for_person($person_id);
        }

        wp_redirect(admin_url('options-general.php?page=tmdb-client&pages_created=1'));
        exit;
    }

    public function add_admin_menu() {
        add_options_page('TMDb Client Settings', 'TMDb Client', 'manage_options', 'tmdb-client', array($this, 'admin_page_html'));
    }

    public function register_settings() {
        register_setting('tmdb_client_options', 'tmdb_client_bearer_token');
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <h2>Create Pages for Person</h2>
            <form method="post">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Person ID</th>
                        <td>
                            <input type="text" name="tmdb_client_person_id" value="" />
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field('tmdb_client_create_pages', 'tmdb_client_create_pages_nonce'); ?>
                <?php submit_button('Create Pages'); ?>
            </form>
            <?php
                if (isset($_GET['pages_created']) && $_GET['pages_created'] == '1') {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>Pages created successfully.</p>
                    </div>
                    <?php
                }
            ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('tmdb_client_options');
                do_settings_sections('tmdb_client_options');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Bearer Token</th>
                        <td>
                            <input type="text" name="tmdb_client_bearer_token" value="<?php echo esc_attr($this->bearer_token); ?>" size="50" />
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Changes'); ?>
            </form>
        </div>
        <?php
    }

    // Shortcode functions

    public function shortcode_person_movies_tv_shows($atts) {
      $atts = shortcode_atts(array('id' => ''), $atts);
      $person_id = intval($atts['id']);

      if ($person_id <= 0) {
          return 'Invalid person ID.';
      }

      $combined_credits = $this->tmdb_api_request("/person/{$person_id}/combined_credits");

      if (!$combined_credits) {
          return 'Error fetching data from TMDb API.';
      }

      return $this->get_template_output('person-movie-tv-shows', $atts);
    }

    public function shortcode_person_details($atts) {
      $atts = shortcode_atts(array('id' => ''), $atts);
      $person_id = intval($atts['id']);
  
      if ($person_id <= 0) {
          return 'Invalid person ID.';
      }
  
      $person_details = $this->tmdb_api_request("/person/{$person_id}");
  
      if (!$person_details) {
          return 'Error fetching data from TMDb API.';
      }
  
      return $this->get_template_output('person-details', $atts);
    }

    public function shortcode_genres($atts) {
        $atts = shortcode_atts(array('type' => 'movie', 'person_id' => ''), $atts);
        $type = in_array($atts['type'], array('movie', 'tv')) ? $atts['type'] : 'movie';
        $person_id = intval($atts['person_id']);
    
        $genres = $this->tmdb_api_request("/genre/{$type}/list");
    
        if (!$genres || !isset($genres['genres'])) {
            return 'Error fetching data from TMDb API.';
        }
    
        if ($person_id > 0) {
            $combined_credits = $this->tmdb_api_request("/person/{$person_id}/combined_credits");
            if (!$combined_credits) {
                return 'Error fetching data from TMDb API.';
            }
    
            $person_genre_ids = array();
            foreach ($combined_credits['cast'] as $credit) {
                if ($credit['media_type'] === $type) {
                    $person_genre_ids = array_merge($person_genre_ids, array_column($credit['genre_ids'], 'id'));
                }
            }
            $person_genre_ids = array_unique($person_genre_ids);
        }
    
        $output = '<ul>';
        foreach ($genres['genres'] as $genre) {
            if ($person_id <= 0 || in_array($genre['id'], $person_genre_ids)) {
                $output .= '<li>' . esc_html($genre['name']) . '</li>';
            }
        }
        $output .= '</ul>';
    
        return $output;
    }

    public function shortcode_movie_tv_details($atts) {
      $atts = shortcode_atts(array('id' => '', 'type' => 'movie'), $atts);
      $id = intval($atts['id']);
      $type = in_array($atts['type'], array('movie', 'tv')) ? $atts['type'] : 'movie';

      if ($id <= 0) {
          return 'Invalid ID.';
      }

      $details = $this->tmdb_api_request("/{$type}/{$id}");
      $videos = $this->tmdb_api_request("/{$type}/{$id}/videos");

      if (!$details || !$videos) {
          return 'Error fetching data from TMDb API.';
      }

      $youtube_trailer = '';
      foreach ($videos['results'] as $video) {
          if ($video['site'] === 'YouTube' && $video['type'] === 'Trailer') {
              $youtube_trailer = $video['key'];
              break;
          }
      }

      return $this->get_template_output('movie-tv-details', $atts);
    }

    private function get_template_output($template_name, $atts) {
        $template = locate_template("tmdb-client/{$template_name}.php");
        
        if (!$template) {
            $template = plugin_dir_path(__FILE__) . "../templates/{$template_name}.php";
        }

        ob_start();
        include $template;
        return ob_get_clean();
    }

    // Helper function to make API requests
    private function tmdb_api_request($endpoint, $query_params = array()) {
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
