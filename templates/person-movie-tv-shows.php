<h3>Movies</h3>
<ul>
<?php foreach ($combined_credits['cast'] as $credit) : ?>
    <?php if ($credit['media_type'] === 'movie') : ?>
        <li><?php echo esc_html($credit['title']); ?> (<?php echo esc_html($credit['release_date']); ?>)</li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>

<h3>TV Shows</h3>
<ul>
<?php foreach ($combined_credits['cast'] as $credit) : ?>
    <?php if ($credit['media_type'] === 'tv') : ?>
        <li><?php echo esc_html($credit['name']); ?> (<?php echo esc_html($credit['first_air_date']); ?>)</li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>
