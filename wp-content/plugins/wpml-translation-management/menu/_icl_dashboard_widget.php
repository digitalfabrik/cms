<?php
/* included from WPML_Translation_Management::icl_dashboard_widget_content */

global $sitepress, $ICL_Pro_Translation, $sitepress_settings, $sitepress, $current_user;

//Todo: handle TS URLs
$service       = TranslationProxy::get_current_service();

//Todo: implement custom text
if($service) {
	$dashboard_text_url = sprintf( __( '%s offers affordable professional translation via a streamlined process.', 'wpml-translation-management' ), '<a target="_blank" href="' . esc_attr( $service->url ) . '">' . $service->name . '</a>' );
} else {
	$dashboard_text_url = TranslationProxy::get_service_dashboard_info();
}
?>

	<p>
		<?php
		if ( $docs_sent ) {
			printf( __( '%d documents sent to translation.<br />%d are complete, %d waiting for translation.', 'wpml-translation-management' ), $docs_sent, $docs_completed, $docs_waiting );
		}
		?>
	</p>
	<p>
		<a href="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/main.php" class="button secondary">
			<strong><?php _e( 'Translate content', 'wpml-translation-management' ); ?></strong>
		</a>
	</p>

<?php

if(!$service) {
	?>
	<h5 style="margin: 15px 0 0 0;"><?php _e( 'Need translation work?', 'wpml-translation-management' ); ?></h5>

	<p><?php echo $dashboard_text_url; ?></p>

	<p>
		<a href="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/main.php&amp;sm=translators" class="button secondary">
			<strong><?php _e( 'Get translators', 'wpml-translation-management' ) ?></strong>
		</a>
	</p>
	<?php
} else {
	?>
	<p><?php echo $dashboard_text_url; ?></p>
	<?php
}

/** @var WPML_TM_Menus $wpml_tm_menus */
global $wpml_tm_menus;
if ( ! isset( $wpml_tm_menus ) ) {
	if ( ! class_exists( 'WPML_TM_Menus' ) ) {
		$this->ensure_includes( true );
	}
	$wpml_tm_menus = new WPML_TM_Menus();
}
if ( isset( $wpml_tm_menus ) ) {
	$wpml_tm_menus->build_content_dashboard_fetch_translations_box();
}

if ( count( $active_languages = $sitepress->get_active_languages() ) > 1 ) {
	?>
	<div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php
			_e( 'Content translation', 'wpml-translation-management' ) ?></a>
	</div>
	<div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;">
		<?php
		$your_translators          = TranslationManagement::get_blog_translators();
		$other_service_translators = TranslationProxy_Translator::translation_service_translators_list();
		if ( !empty( $your_translators ) || !empty( $other_service_translators ) ) {
			echo '<p><strong>' . __( 'Your translators', 'wpml-translation-management' ) . '</strong></p><ul>';
			if ( !empty( $your_translators ) ) {
				foreach ( $your_translators as $your_translator ) {
					echo '<li>';
					if ( $current_user->ID == $your_translator->ID ) {
						$edit_link = 'profile.php';
					} else {
						$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( esc_url( stripslashes( $_SERVER[ 'REQUEST_URI' ] ) ) ), "user-edit.php?user_id=$your_translator->ID" ) );
					}
					echo '<a href="' . $edit_link . '"><strong>' . $your_translator->display_name . '</strong></a> - ';
					foreach ( $your_translator->language_pairs as $from => $lp ) {
						$tos = array();
						foreach ( $lp as $to => $null ) {
							if ( isset( $active_languages[ $to ] ) ) {
								$tos[ ] = $active_languages[ $to ][ 'display_name' ];
							}
						}
						printf( __( '%s to %s', 'wpml-translation-management' ), $active_languages[ $from ][ 'display_name' ], join( ', ', $tos ) );
					}
					echo '</li>';
				}
			}

			if ( !empty( $other_service_translators ) ) {
				$langs = $sitepress->get_active_languages();
				foreach ( $other_service_translators as $rows ) {
					foreach ( $rows[ 'langs' ] as $from => $lp ) {
						$from = isset( $langs[ $from ][ 'display_name' ] ) ? $langs[ $from ][ 'display_name' ] : $from;
						$tos  = array();
						foreach ( $lp as $to ) {
							$tos[ ] = isset( $langs[ $to ][ 'display_name' ] ) ? $langs[ $to ][ 'display_name' ] : $to;
						}
					}
					echo '<li>';
					echo '<strong>' . $rows[ 'name' ] . '</strong> | ' . sprintf( __( '%s to %s', 'wpml-translation-management' ), $from, join( ', ', $tos ) ) . ' | ' . $rows[ 'action' ];
					echo '</li>';
				}
			}

			echo '</ul><hr />';
		}

		?>
		<p>
			<a href="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/main.php&amp;sm=translators"><strong><?php _e( 'Add translators &raquo;', 'wpml-translation-management' ); ?></strong></a>
		</p>

		<p>
			<a href="admin.php?page=<?php echo WPML_TM_FOLDER; ?>/menu/main.php"><strong><?php _e( 'Translate contents &raquo;', 'wpml-translation-management' ); ?></strong></a>
		</p>
	</div>
<?php
}
