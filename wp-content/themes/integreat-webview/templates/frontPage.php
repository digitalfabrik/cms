<div class="row">
    <section class="contentList searchOnSiteContent">
        <?php
        $args = array(
            'posts_per_page'    => -1,
            'post_type'         => 'page',
            'post_status'       => 'publish',
            'orderby'           => 'menu_order',
            'order'             => 'ASC',
        );
        $query = new WP_Query($args);

        $posts = $query->get_posts();
        ?>

        <?php if( !empty($posts) ): ?>
            <?php $iterator = 0; ?>
            <?php foreach ($posts as &$post): setup_postdata( $post ); ?>
                <?php include __DIR__ . '/pageListing.php'; ?>
            <?php endforeach;
            wp_reset_postdata(); ?>
        <?php endif; ?>
    </section>
</div>