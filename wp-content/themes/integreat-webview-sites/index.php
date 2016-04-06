<?php get_header(); ?>

<main>
    <div class="container">

        <?php if( !$_GET['loc'] ): ?>

            <?php
                $args = array(
                    'public'    => 1
                );
                $sites = wp_get_sites( $args );
            ?>

            <div id="sitesToSelect">

                <h2 class="title">
                    <?php _e( 'SELECT A LOCATION', 'integreat-sites' ); ?><br />
                    <?php _e( 'Where are you?', 'integreat-sites' ); ?>
                </h2>

                <div id="searchSites" class="search">
                    <input type="search" placeholder="<?php _e( 'Search for a location', 'integreat-sites' ); ?>" />
                </div>

                <div id="searchContent" class="sites">
                    <?php
                        // set id of current blog to exclude it from listing
                        $currentBlogID = get_current_blog_id();
                        $sitesArr = array();

                        function umlaut($string){
                            $upas = Array("ä" => "ae", "ü" => "ue", "ö" => "oe", "Ä" => "Ae", "Ü" => "Ue", "Ö" => "Oe");
                            return strtr($string, $upas);
                        }
                    ?>
                    <?php foreach($sites as &$site): ?>
                        <?php $siteID = $site['blog_id']; ?>
                        <?php if( $siteID != $currentBlogID ): ?>
                            <?php
                                switch_to_blog($siteID);
                                $siteTitle = get_bloginfo( 'name' );
                                $siteHeaderImageURL = get_header_image();
                                restore_current_blog();

                                // sort instances by name and save them in a new array
                                $key = strtolower(umlaut($siteTitle));
                                $sitesArr[$key] = array(
                                        'title'             => $siteTitle,
                                        'id'                => $siteID,
                                        'header_image_url'  => $siteHeaderImageURL
                                    );
                            ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php asort($sitesArr); ?>

                    <?php foreach($sitesArr as &$site): ?>
                        <div class="site col-xs-6 col-sm-4 col-lg-3" data-search="<?php echo $site['title']; ?>">
                            <a href="?loc=<?php echo $site['id']; ?>"<?php if( $site['header_image_url'] ): ?> class="bg" style="background-image: url(<?php echo $site['header_image_url']; ?>);"<?php endif; ?>>
                                <span><?php echo $site['title']; ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>

            <?php
                $blogID = $_GET['loc'];
                switch_to_blog($blogID);
                $blogURL = get_bloginfo('wpurl');

                // get active languages of instance
                global $wpdb;
                $table_prefix = $wpdb->prefix;
                $languagesArr = $wpdb->get_results('SELECT * FROM '.$table_prefix.'icl_languages WHERE active = 1');
                $languages = array();
                foreach($languagesArr as &$language) {
                    $currentLanguageTranslations = $wpdb->get_results('SELECT * FROM '.$table_prefix.'icl_languages_translations WHERE language_code = "'.$language->code.'" AND display_language_code = "'.$language->code.'"');
                    $currentLanguageFlags = $wpdb->get_results('SELECT * FROM '.$table_prefix.'icl_flags WHERE lang_code = "'.$language->code.'"');
                    $currentLanguageInCurrentLanguage = $wpdb->get_row('SELECT * FROM '.$table_prefix.'icl_languages_translations WHERE language_code = "'.$language->code.'" AND display_language_code = "'.ICL_LANGUAGE_CODE.'"');
                    $homeURL = home_url();
                    $homeURL = parse_url($homeURL);
                    $homeURL = $homeURL['path'];
                    $languages[$language->english_name] = array(
                            'code'                  => $language->code,
                            'english_name'          => $language->english_name,
                            'native_name'           => $currentLanguageTranslations[0]->name,
                            'name_current_language' => $currentLanguageInCurrentLanguage->name,
                            'flag_url'              => "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $homeURL . '/wp-content/plugins/sitepress-multilingual-cms/res/flags/' . $currentLanguageFlags[0]->flag
                        );
                }
                restore_current_blog();
            ?>

            <div id="languagesToSelect">

                <h2 class="title">
                    <?php _e( 'SELECT A LANGUAGE', 'integreat-sites' ); ?><br />
                    <?php _e( 'What language do you speak?', 'integreat-sites' ); ?>
                </h2>

                <div id="searchLanguages" class="search">
                    <input type="search" placeholder="<?php _e( 'Search for a language', 'integreat-sites' ); ?>" />
                </div>

                <div id="searchContent" class="languages">
                    <?php foreach($languages as &$language): ?>
                        <div class="language col-xs-6 col-sm-4 col-lg-3" data-search="<?php echo $language['native_name'] . ' ' . $language['english_name']  . ' ' . $language['name_current_language']; ?>">
                            <a href="<?php echo $blogURL . '/' . $language['code'] . '/?sc=1'; ?>"<?php if( $language['flag_url'] ): ?> class="bg" style="background-image: url(<?php echo $language['flag_url']; ?>);"<?php endif; ?>>
                                <span><?php echo $language['native_name']; ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="<?php echo home_url(); ?>" id="backToLocationSelect"><?php _e( 'back to selection of location', 'integreat-sites' ); ?></a>

            </div>

        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>