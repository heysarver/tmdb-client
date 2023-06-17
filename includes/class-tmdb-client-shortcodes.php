<?php
require_once plugin_dir_path(__FILE__) . 'class-tmdb-client-api.php';
class TMDb_Client_Shortcodes {
    protected $parent;
    protected $api;

    public function __construct($parent) {
        $this->parent = $parent;
        $this->api = new TMDb_Client_API($parent);
        $this->register_shortcodes();
    }

    protected function register_shortcodes() {
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

    protected function shortcode_person_movies_tv_shows($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $person_id = intval($atts['id']);

        if ($person_id <= 0) {
            return 'Invalid person ID.';
        }

        $combined_credits = $this->api->tmdb_api_request("/person/{$person_id}/combined_credits");

        if (!$combined_credits) {
            return 'Error fetching data from TMDb API.';
        }

        return $this->parent->get_template_output('person-movie-tv-shows', $atts);
    }

    protected function shortcode_person_details($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $person_id = intval($atts['id']);
    
        if ($person_id <= 0) {
            return 'Invalid person ID.';
        }
    
        $person_details = $this->api->tmdb_api_request("/person/{$person_id}");
    
        if (!$person_details) {
            return 'Error fetching data from TMDb API.';
        }
    
        return $this->parent->get_template_output('person-details', $atts);
    }

    protected function shortcode_genres($atts) {
        $atts = shortcode_atts(array('type' => 'movie', 'person_id' => ''), $atts);
        $type = in_array($atts['type'], array('movie', 'tv')) ? $atts['type'] : 'movie';
        $person_id = intval($atts['person_id']);
    
        $genres = $this->api->tmdb_api_request("/genre/{$type}/list");
    
        if (!$genres || !isset($genres['genres'])) {
            return 'Error fetching data from TMDb API.';
        }
    
        if ($person_id > 0) {
            $combined_credits = $this->api->tmdb_api_request("/person/{$person_id}/combined_credits");
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

        $details = $this->api->tmdb_api_request("/{$type}/{$id}");
        $videos = $this->api->tmdb_api_request("/{$type}/{$id}/videos");

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

        return $this->parent->get_template_output('movie-tv-details', $atts, array('details' => $details, 'youtube_trailer' => $youtube_trailer));
    }
}
