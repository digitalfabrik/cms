<?php
    if(!empty(get_theme_mod('webview_contentheader_img'))) {
        $headerImageURL = wp_get_attachment_image_url(get_theme_mod('webview_contentheader_img'), 'full');
    } else if(get_header_image()) {
        $headerImageURL = get_header_image();
    }
?>
<header id="contentHeader"<?php if( $headerImageURL ): ?> class="bg" style="background-image: url(<?php echo $headerImageURL; ?>);"<?php endif; ?>>
    <?php if( is_front_page() or is_404() ): ?>
        <h2><?php bloginfo('name'); ?></h2>
    <?php elseif( is_search() ): ?>
        <h2>
            <?php _e('Search results for:','integreat'); ?>
            <i><?php echo $_GET['s']; ?></i>
        </h2>
    <?php else: ?>
            <h2><?php the_title(); ?></h2>
    <?php endif; ?>
</header>

<?php get_template_part('templates/breadcrumb'); ?>