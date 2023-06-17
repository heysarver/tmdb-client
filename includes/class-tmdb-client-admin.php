<?php
require_once plugin_dir_path(__FILE__) . 'class-tmdb-client-api.php';
class TMDb_Client_Admin {
    private $bearer_token;
    protected $parent;
    protected $api;

    public function __construct($parent) {
        $this->parent = $parent;
        $this->bearer_token = get_option('tmdb_client_bearer_token');
        $this->api = new TMDb_Client_API($parent);
        $this->register_admin_page();
    }

    protected function register_admin_page() {
        $actions = [
            'admin_menu'                 => 'add_admin_menu',
            'admin_init'                 => 'register_settings',
            'admin_post_tmdb_client_create_posts' => 'handle_create_posts_form',
        ];

        foreach ($actions as $action => $callback) {
            add_action($action, [$this, $callback]);
        }
    }

    public function handle_create_posts_form() {
        if (!isset($_POST['tmdb_client_create_posts_nonce']) || !wp_verify_nonce($_POST['tmdb_client_create_posts_nonce'], 'tmdb_client_create_posts')) {
            wp_die('Invalid nonce');
        }
        
        $person_id = intval($_POST['tmdb_client_person_id']);
        if ($person_id > 0) {
            $this->create_posts_for_person($person_id);
        }

        wp_redirect(admin_url('options-general.php?page=tmdb-client&posts_created=1'));
        exit;
    }

    protected function create_posts_for_person($person_id) {
        // Fetch genres
        $movie_genres = $this->api->tmdb_api_request("/genre/movie/list")['genres'];
        $tv_genres = $this->api->tmdb_api_request("/genre/tv/list")['genres'];
    
        // Fetch combined credits for the person
        $combined_credits = $this->api->tmdb_api_request("/person/{$person_id}/combined_credits");
    
        // Create categories for genres
        foreach (array_merge($movie_genres, $tv_genres) as $genre) {
            wp_insert_term($genre['name'], 'category', array('slug' => 'genre-' . $genre['id']));
        }
    
        // Create posts for movies and TV shows
        foreach ($combined_credits['cast'] as $credit) {
            $post_title = ($credit['title'] ?? $credit['name']) . ' (' . substr(esc_html($credit['release_date'] ?? $credit['air_date']),0,4) . ')';
            $post_content = '[tmdb_movie_tv_details id="' . $credit['id'] . '" type="' . $credit['media_type'] . '"]';
            $genre_ids = array_map(function($genre_id) {
                return 'genre-' . $genre_id;
            }, $credit['genre_ids']);
    
            $post_id = wp_insert_post(array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_status' => 'publish',
                'post_type' => 'post',
            ));
    
            // Assign categories (genres) to the post
            wp_set_object_terms($post_id, $genre_ids, 'category');
        }
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }
    
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings updated successfully.</p>
            </div>
            <?php
        }
    
        if (isset($_GET['posts_created']) && $_GET['posts_created'] == '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Posts created successfully.</p>
            </div>
            <?php
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
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
    
            <!-- Add the form for creating posts for a specific person -->
            <h2>Create Posts for Person</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tmdb_client_create_posts">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Person ID</th>
                        <td>
                            <input type="text" name="tmdb_client_person_id" value="" />
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field('tmdb_client_create_posts', 'tmdb_client_create_posts_nonce'); ?>
                <?php submit_button('Create Posts'); ?>
            </form>
        </div>
        <?php
    }

    public function add_admin_menu() {
        add_options_page('TMDb Client Settings', 'TMDb Client', 'manage_options', 'tmdb-client', array($this, 'admin_page_html'));
    }

    public function register_settings() {
        register_setting('tmdb_client_options', 'tmdb_client_bearer_token');
    }
}
