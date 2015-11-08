<?php get_header(); ?>

<section id="main" class="container">
    <div class="row">
        <div id="content" class="site-content col-md-8" role="main">

            <?php if ( have_posts() ) : ?>

                <header class="page-header">
                    <h1 class="page-title"><?php wp_title(' '); ?></h1>
                </header> <!-- .page-header -->

                <?php 
                    $args = array(
                            'posts_per_page'    => 6,
                            'post_type'         => 'post',
                            'orderby'           => 'date',
                            'ORDER'             => DESC,
                        );
                    $blog = get_posts( $args );
                ?>
                <?php foreach ($blog as $post) : setup_postdata( $post ); ?>
                    <?php get_template_part( 'post-format/content', get_post_format() ); ?>
                <?php endforeach; ?>

                <?php echo thm_pagination(); ?>

            <?php else: ?>
                <?php get_template_part( 'post-format/content', 'none' ); ?>
            <?php endif; ?>

        </div> <!-- #content -->

        <div id="sidebar" class="col-md-4" role="complementary">
            <div class="sidebar-inner">
                <aside class="widget-area">
                    <?php dynamic_sidebar('sidebar');?>
                </aside>
            </div>
        </div> <!-- #sidebar -->

    </div> <!-- .row -->
</section> <!-- .container -->

<?php get_footer();