        <footer>
            <div class="container">
                <div class="row">
                    <ul>
                        <?php if( $imprintSite = get_theme_mod('imprintsites_imprint') ): ?>
                            <li><?php icl_link_to_element( $imprintSite, 'page', __( 'Imprint', 'integreat' ), array('sc'=>'1') ); ?></li>
                        <?php endif; ?>
                        <?php if( $privacySite = get_theme_mod('imprintsites_privacy') ): ?>
                            <li><?php icl_link_to_element( $privacySite, 'page', __( 'Privacy', 'integreat' ), array('sc'=>'1') ); ?></li>
                        <?php endif; ?>
                        <?php if( $contactSite = get_theme_mod('imprintsites_contact') ): ?>
                            <li><a href="<?php echo $contactSite; ?>" target="_blank"><?php _e( 'Contact', 'integreat' ) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </footer>

        <?php wp_footer(); ?>

    </div>
</body>
</html>