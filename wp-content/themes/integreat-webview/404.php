<?php 
$uri = str_replace("/wordpress","",$_SERVER['REQUEST_URI']);
header('Location: https://web.integreat-app.de'.$uri, true, 301);
die(); ?>

<?php get_header(); ?>

    <?php get_template_part('templates/contentHeader'); ?>

    <main>
        <div class="container">
            <section class="content">
                <h1><?php _e( 'The page you were requesting could not be found.', 'integreat-sites' ); ?></h1>
                <a href="<?php bloginfo('wpurl'); ?>/<?php echo ICL_LANGUAGE_CODE; ?>" style="text-decoration: underline;"><?php _e( 'Back to homepage', 'integreat-sites' ); ?></a><br /><br />
            </section>
        </div>
    </main>

<?php get_footer(); ?>
