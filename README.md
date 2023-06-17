# TMDb-Client WordPress Plugin

TMDb-Client is a WordPress plugin that interacts with the TMDb API to display movie and TV show information. It provides shortcodes for displaying a person's movie and TV show list, person details, list of genres, and movie or TV show details.

## Installation

1. Download the plugin files and upload them to the `/wp-content/plugins/tmdb-client` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the 'Settings' > 'TMDb Client' menu and enter your TMDb Bearer Token.

## Usage

TMDb-Client provides the following shortcodes:

### Display a person's movie and TV show list

```
[tmdb_person_movies_tv_shows id="PERSON_ID"]
```

Replace `PERSON_ID` with the person's TMDb ID.

### Display a person's details

```
[tmdb_person_details id="PERSON_ID"]
```

Replace `PERSON_ID` with the person's TMDb ID.

### Display a list of genres

```
[tmdb_genres type="movie" person_id="PERSON_ID"]
```

Replace `type` with either `movie` or `tv`. The `person_id` attribute is optional. If provided, the shortcode will display only the genres associated with the specified person.

### Display movie or TV show details

```
[tmdb_movie_tv_details id="MOVIE_TV_ID" type="movie"]
```

Replace `MOVIE_TV_ID` with the movie or TV show's TMDb ID. Replace `type` with either `movie` or `tv`.

## Customizing Templates

To customize the layout of the shortcodes, copy the template files from the `wp-content/plugins/tmdb-client/templates` folder to your theme folder (e.g., `my-theme/tmdb-client/`) and customize the HTML as needed. The plugin will use the customized templates if available, otherwise, it will fall back to the default templates in the plugin folder.

## License

This plugin is licensed under the GPLv2 or later.
