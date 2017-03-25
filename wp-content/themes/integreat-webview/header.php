<!DOCTYPE html>
<html>
<head>
    <title>
        <?php $siteInfo = get_current_site(); ?>

        <?php if( is_front_page() ): ?>
            <?php echo $siteInfo->site_name; ?> - <?php bloginfo('name'); ?>
        <?php else: ?>
            <?php if( is_search() ): ?>
                <?php
                    _e( 'Search for', 'integreat' );
                    echo ' "' . $_GET['s'] . '"';
                ?> |
            <?php elseif( get_the_title() ): ?>
                <?php the_title(); ?> |
            <?php endif; ?>
            <?php echo $siteInfo->site_name; ?> - <?php bloginfo('name'); ?>
        <?php endif; ?>
    </title>

    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<? bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>

    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Raleway:400,300" rel="stylesheet" type="text/css" />
    <link href="<?php bloginfo('template_url'); ?>/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php bloginfo('template_url'); ?>/css/jquery.custom-scrollbar.css" rel="stylesheet" type="text/css" />
    <link href="<?php bloginfo('template_url'); ?>/css/responsive-tables.css" rel="stylesheet" type="text/css" />
    <link href="<?php bloginfo('stylesheet_url'); ?>" rel="stylesheet" type="text/css" />

    <!-- FAVICON Start -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php bloginfo('template_url'); ?>/images/favicon/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="<?php bloginfo('template_url'); ?>/images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="<?php bloginfo('template_url'); ?>/images/favicon/android-chrome-192x192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="<?php bloginfo('template_url'); ?>/images/favicon/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="<?php bloginfo('template_url'); ?>/images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="<?php bloginfo('template_url'); ?>/images/favicon/manifest.json">
    <link rel="mask-icon" href="<?php bloginfo('template_url'); ?>/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="<?php bloginfo('template_url'); ?>/images/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-TileImage" content="<?php bloginfo('template_url'); ?>/images/favicon/mstile-144x144.png">
    <meta name="msapplication-config" content="<?php bloginfo('template_url'); ?>/images/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <!-- FAVICON End -->

    <?php if( $analytics_code = get_theme_mod('webview_google_analytics_code') ): ?>
        <script type="text/javascript">
            <?php echo $analytics_code; ?>
        </script>
    <?php endif; ?>
</head>
<body <?php body_class(array('searchOnSite')); ?>>

    <nav id="mainNav" class="default-skin scrollable">
        <a href="<?php echo network_home_url(); ?>" id="changeLocation"><i class="fa fa-location-arrow"></i> <?php _e( 'Change location', 'integreat' ); ?></a>

        <button id="closeMainNav"><i class="fa fa-times"></i></button>

        <a href="<?php echo icl_get_home_url(); ?>?isite=event" id="linkToEvents"><i class="fa fa-calendar"></i> <?php _e('Events','integreat'); ?></a>

        <ul id="mainMenu">
            <?php
                // exclude imprint and privacy site
                $exclude = '';
                if( $imprintSite = get_theme_mod('imprintsites_imprint') ) {
                    $exclude .= $imprintSite;
                    $exclude .= ',';
                }
                if( $privacySite = get_theme_mod('imprintsites_privacy') ) {
                    $exclude .= $privacySite;
                }

                // list pages
                wp_list_pages( array(
                    'title_li'  => '',
                    'exclude'   => $exclude,
                    'walker'    => new Integreat_Walker_Menu()
                ) );
            ?>
        </ul>
    </nav>

    <div id="mainNavMask"></div>
    <div id="siteWrap">
        <?php get_search_form(); ?>

        <header id="topHeader">
            <div class="container">
                <div class="row">
                    <div id="menuToggleContainer">
                        <span id="openMainNav"><i class="fa fa-bars"></i></span>
                    </div>

                    <div id="siteTitle">
                        <?php $siteInfo = get_current_site(); ?>
                        <h1>
                            <a href="<?php bloginfo('url'); ?>">
                                <?php echo $siteInfo->site_name; ?> - <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                    </div>
                    <?php get_template_part('templates/createPDF'); ?>
                    <?php get_template_part('templates/languageSwitcher'); ?>
                </div>
            </div>
        </header>
