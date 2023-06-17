<h2><?php echo esc_html($details['title'] ?? $details['name']); ?> (<?php echo substr(esc_html($details['release_date']),0,4) ?>)</h2>
<img src="https://image.tmdb.org/t/p/w500<?php echo esc_attr($details['poster_path']); ?>" alt="<?php echo esc_attr($details['title'] ?? $details['name']); ?>">
<p><strong>Overview:</strong> <?php echo esc_html($details['overview']); ?></p>

<?php if ($youtube_trailer) : ?>
    <p><strong>Trailer:</strong></p>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_trailer); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
<?php endif; ?>
<?php var_dump($details); ?>
