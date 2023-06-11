<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

    <?php
    if (have_posts()) : ?>

        <header class="page-header">
            <h1 class="page-title">
                <?php
                if (is_post_type_archive('movie')) {
                    echo 'Movies';
                } elseif (is_post_type_archive('tv-show')) {
                    echo 'TV Shows';
                }
                ?>
            </h1>
        </header>

        <?php
        while (have_posts()) : the_post();
            get_template_part('template-parts/content', get_post_type());
        endwhile;

        the_posts_pagination([
            'prev_text' => __('Previous', 'textdomain'),
            'next_text' => __('Next', 'textdomain'),
            'screen_reader_text' => __('Posts navigation', 'textdomain')
        ]);

    else :
        get_template_part('template-parts/content', 'none');
    endif;
    ?>

    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
