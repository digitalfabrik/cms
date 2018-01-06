<?php
$uri = str_replace("/wordpress","",$_SERVER['REQUEST_URI']);
header('Location: https://web.integreat-app.de'.$uri);
die();
get_header(); ?>

    <?php get_template_part('templates/contentHeader'); ?>

    <main>
        <div class="container">
            <?php if( $_GET['isite'] == event ): ?>
                <?php echo do_shortcode('[events_list]'); ?>
            <?php elseif( is_front_page() ): ?>
                <?php include get_template_directory() . '/templates/frontPage.php'; ?>
            <?php else: ?>
                <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                    <section class="content searchOnSiteContent">
                        <?php the_content(); ?>
                    </section>
                <?php endwhile; endif; ?>
            <?php endif; ?>
        </div>
    </main>

<?php get_footer(); ?>
