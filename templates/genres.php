<ul>
<?php foreach ($genres['genres'] as $genre) : ?>
    <li><?php echo esc_html($genre['name']); ?></li>
<?php endforeach; ?>
</ul>
