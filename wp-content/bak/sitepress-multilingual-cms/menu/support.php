
<div class="wrap">    
    <h2><?php _e('Support', 'sitepress') ?></h2>
    
    <p style="margin-top: 20px;">
        <?php _e('Technical support for clients is available via <a target="_blank" href="https://wpml.org/forums/">WPML forums</a>.','sitepress'); ?>
    </p>

    <?php
	$wpml_plugins_list = SitePress::get_installed_plugins();

    echo '
        <table class="widefat" style="width: auto;">
            <thead>
                <tr>    
                    <th>' . __('Plugin Name', 'sitepress') . '</th>
                    <th style="text-align:right">' . __('Status', 'sitepress') . '</th>
                    <th>' . __('Active', 'sitepress') . '</th>
                    <th>' . __('Version', 'sitepress') . '</th>
                </tr>
            </thead>    
            <tbody>
        ';

	foreach ( $wpml_plugins_list as $name => $plugin_data ) {

		$plugin_name = $name;
		$file        = $plugin_data['file'];
		$dir = dirname($file);

		echo '<tr>';
		echo '<td><i class="icon18 '. $plugin_data['slug'] . '"></i>' . $plugin_name . '</td>';
		echo '<td align="right">';
		if ( empty( $plugin_data['plugin'] ) ) {
            echo __( 'Not installed', 'sitepress' );
		} else {
            echo __( 'Installed', 'sitepress' );
		}
		echo '</td>';
		echo '<td align="center">';
		echo isset( $file ) && is_plugin_active( $file ) ? __( 'Yes', 'sitepress' ) : __( 'No', 'sitepress' );
		echo '</td>';
		echo '<td align="right">';
		echo isset( $plugin_data['plugin']['Version'] ) ? $plugin_data['plugin']['Version'] : __( 'n/a', 'sitepress' );
		echo '</td>';
		echo '</tr>';

	}

    echo '
            </tbody>
        </table>
    ';

    ?>
    
    <p style="margin-top: 20px;">
    <?php printf(__('For advanced access or to completely uninstall WPML and remove all language information, use the <a href="%s">troubleshooting</a> page.', 'sitepress'), admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php')); ?> 
    </p>
    
    <p style="margin-top: 20px;">
    <?php printf(__('For retrieving debug information if asked by support person, use the <a href="%s">debug information</a> page.', 'sitepress'), admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/debug-information.php')); ?> 
    </p>
	
	<?php do_action( 'wpml_support_page_after' ); ?>
    
</div>
