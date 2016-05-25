<?php
    wp_redirect( home_url() );
    exit;

    get_header();
?>

    <main>
        <div class="container">
            <h1><?php _e( 'The page you were requesting could not be found.', 'integreat-sites' ); ?></h1>
            <a href="<?php bloginfo('wpurl'); ?>" style="text-decoration: underline;"><?php _e( 'Back to homepage', 'integreat-sites' ); ?></a><br /><br />
        </div>
    </main>

<?php get_footer(); ?>