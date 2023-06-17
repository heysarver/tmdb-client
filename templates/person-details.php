<h2><?php echo esc_html($person_details['name']); ?></h2>
<img src="https://image.tmdb.org/t/p/w200<?php echo esc_attr($person_details['profile_path']); ?>" alt="<?php echo esc_attr($person_details['name']); ?>">
<p><strong>Birthday:</strong> <?php echo esc_html($person_details['birthday']); ?></p>
<p><strong>Place of Birth:</strong> <?php echo esc_html($person_details['place_of_birth']); ?></p>
<p><strong>Biography:</strong> <?php echo esc_html($person_details['biography']); ?></p>
