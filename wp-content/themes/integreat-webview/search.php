<?php get_header(); ?>

<?php get_template_part('templates/contentHeader'); ?>

    <main>
        <div class="container">
            <div class="row">
                <section class="contentList searchOnSiteContent">
                    <?php
                        global $wp_query;
                        query_posts(
                            array_merge(
                                array(
                                    // TODO post_type event: filter for current events
                                    'post_type' => array('event','page')
                                ),
                                $wp_query->query
                            )
                        );
                    ?>
                    <?php if ( have_posts() ) : ?>
                        <?php $iterator = 0; ?>
                        <?php while ( have_posts() ) : the_post(); ?>
                            <?php include __DIR__ . '/templates/pageListing.php'; ?>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

<?php get_footer(); ?>