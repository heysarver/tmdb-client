<?php

/*
Plugin Name: TMDb Data Retriever
Description: A plugin to retrieve data from TMDb.
Version: 0.0.1
Author: Andrew Sarver
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once 'tmdb-api.php';

function tmdb_person_shortcode($atts) {
    $a = shortcode_atts(['id' => get_option('tmdb_default_person_id')], $atts);
    $api = new TMDb_API(get_option('tmdb_access_token'));
    $person = $api->get("person/{$a['id']}");
    return $person ? json_encode($person) : 'Error retrieving person data.';
}
add_shortcode('tmdb_person', 'tmdb_person_shortcode');

function tmdb_movie_shortcode($atts) {
    $a = shortcode_atts(['id' => ''], $atts);
    if (empty($a['id'])) return 'Error: Movie ID is required.';
    $api = new TMDb_API(get_option('tmdb_access_token'));
    $movie = $api->get("movie/{$a['id']}");
    return $movie ? json_encode($movie) : 'Error retrieving movie data.';
}
add_shortcode('tmdb_movie', 'tmdb_movie_shortcode');

function tmdb_tv_show_shortcode($atts) {
    $a = shortcode_atts(['id' => ''], $atts);
    if (empty($a['id'])) return 'Error: TV Show ID is required.';
    $api = new TMDb_API(get_option('tmdb_access_token'));
    $tv_show = $api->get("tv/{$a['id']}");
    return $tv_show ? json_encode($tv_show) : 'Error retrieving TV show data.';
}
add_shortcode('tmdb_tv_show', 'tmdb_tv_show_shortcode');

function create_tmdb_post($title, $content, $post_type, $tags_input, $category_ids) {
    $existing_post = get_page_by_title($title, 'OBJECT', $post_type);

    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => $post_type,
        'tags_input'   => $tags_input,
    ];

    if ($existing_post) {
        $post_data['ID'] = $existing_post->ID;
        $post_id = wp_update_post($post_data);
    } else {
        $post_id = wp_insert_post($post_data);
    }

    // Set post categories
    if (!is_wp_error($post_id) && !empty($category_ids)) {
        wp_set_post_categories($post_id, $category_ids);
    }

    if (is_wp_error($post_id)) {
        echo '<div class="error"><p>Error creating or updating post: ' . $post_id->get_error_message() . '</p></div>';
    }

    return $post_id;
}

function create_movie_tv_show_pages_for_person(&$person_id) {
    $api = new TMDb_API(get_option('tmdb_access_token'));
    $credits = $api->get("person/{$person_id}/combined_credits");

    if (is_wp_error($credits)) {
        echo '<div class="error"><p>Error: ' . $credits->get_error_message() . '</p></div>';
        return;
    }

    if (!$credits || !isset($credits['cast'])) {
        echo '<div class="error"><p>Error: Unable to fetch combined credits for the person. Check the API response: ' . json_encode($credits) . '</p></div>';
        return;
    }

    foreach ($credits['cast'] as $credit) {
        $title = $credit['title'] ?? $credit['name'] ?? '';
        if (empty($title)) continue;
        $content = '[tmdb_credit id="' . $credit['id'] . '" type="' . $credit['media_type'] . '"]';
        $post_type = $credit['media_type'] === 'movie' ? 'movie' : 'tv-show';

        // Create categories based on genres
        $genre_ids = $credit['genre_ids'] ?? [];
        $category_ids = [];
        foreach ($genre_ids as $genre_id) {
            $genre = $api->get("genre/{$genre_id}");
            if ($genre && isset($genre['name'])) {
                $category = create_or_get_category($genre['name']);
                if ($category && isset($category->term_id)) {
                    $category_ids[] = $category->term_id;
                }
            }
        }

        create_tmdb_post($title, $content, $post_type, [], $category_ids);
    }
}

function create_or_get_category($category_name) {
    $category = get_term_by('name', $category_name, 'category');
    if (!$category) {
        $result = wp_insert_term($category_name, 'category');
        if (!is_wp_error($result)) {
            $category = get_term_by('term_id', $result['term_id'], 'category');
        }
    }
    return $category;
}

function tmdb_data_retriever_menu() {
    add_options_page('TMDb Data Retriever Settings', 'TMDb Data Retriever', 'manage_options', 'tmdb-data-retriever', 'tmdb_data_retriever_options');
}
add_action('admin_menu', 'tmdb_data_retriever_menu');

function tmdb_data_retriever_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_GET['create_pages'])) {
        $person_id = get_option('tmdb_default_person_id');
        if ($person_id) {
            echo '<div class="updated"><p>Creating pages for person ID: ' . $person_id . '</p></div>';
            create_movie_tv_show_pages_for_person($person_id);
        } else {
            echo '<div class="error"><p>Error: Person ID is not set.</p></div>';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        update_option('tmdb_default_person_id', $_POST['tmdb_default_person_id']);
        update_option('tmdb_access_token', $_POST['tmdb_access_token']);
    }

    ?>
    <div class="wrap">
        <h1>TMDb Data Retriever Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Default Person ID</th>
                    <td>
                        <input type="text" name="tmdb_default_person_id" value="<?php echo get_option('tmdb_default_person_id'); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">TMDb Access Token</th>
                    <td>
                        <input type="text" name="tmdb_access_token" value="<?php echo get_option('tmdb_access_token'); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                <a href="?page=tmdb-data-retriever&create_pages=true" class="button">Create Pages for Default Person</a>
            </p>
        </form>
    </div>
    <?php
}

function register_custom_post_types() {
    $movie_args = [
        'public'      => true,
        'label'       => 'Movies',
        'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_menu'=> true,
        'menu_icon'   => 'dashicons-video-alt2',
        'taxonomies'  => ['category'],
        '_builtin'     => false, 
        'has_archive' => true, 
        'rewrite'     => array('slug' => 'movies')
    ];
    $result1 = register_post_type('movie', $movie_args);
    error_log("Movie Registration: " . print_r($result1, true));

    $tv_show_args = [
        'public'      => true,
        'label'       => 'TV Shows',
        'supports'    => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_menu'=> true,
        'menu_icon'   => 'dashicons-video-alt3',
        'taxonomies'  => ['category'],
        '_builtin'     => false, 
        'has_archive' => true, 
        'rewrite'     => array('slug' => 'tv-shows')
    ];
    $result2 = register_post_type('tv-show', $tv_show_args);
    error_log("TV Show Registration: " . print_r($result2, true));

    $error_object1 = is_wp_error($result1) ? $result1->get_error_message() : "";
    $error_object2 = is_wp_error($result2) ? $result2->get_error_message() : "";

    error_log("Movie Registration Error: " . $error_object1);
    error_log("TV Show Registration Error: " . $error_object2);

    $taxonomies = get_taxonomies();
    error_log("Registered Taxonomies: " . print_r($taxonomies, true));
}
add_action('init', 'register_custom_post_types');

function tmdb_credit_shortcode($atts) {
    $a = shortcode_atts(['id' => '', 'type' => ''], $atts);
    if (empty($a['id']) || empty($a['type'])) return 'Error: Credit ID and type are required.';

    $api = new TMDb_API(get_option('tmdb_access_token'));
    $credit = $api->get("{$a['type']}/{$a['id']}");
    return $credit ? json_encode($credit) : 'Error retrieving credit data.';
}
add_shortcode('tmdb_credit', 'tmdb_credit_shortcode');
