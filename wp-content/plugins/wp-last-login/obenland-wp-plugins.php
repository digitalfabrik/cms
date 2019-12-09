<?php
/**
 * Obenland plugin base class.
 *
 * @author  Konstantin Obenland
 * @version 4
 * @package Obenland Plugins
 */

/**
 * Class Obenland_Wp_Plugins_V4
 */
class Obenland_Wp_Plugins_V4 {

	/**
	 * The plugins' text domain.
	 *
	 * @author Konstantin Obenland
	 * @since  1.1 - 03.04.2011
	 * @access protected
	 *
	 * @var    string
	 */
	protected $textdomain;


	/**
	 * The name of the calling plugin.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 23.03.2011
	 * @access protected
	 *
	 * @var    string
	 */
	protected $plugin_name;


	/**
	 * The donate link for the plugin.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 23.03.2011
	 * @access protected
	 *
	 * @var    string
	 */
	protected $donate_link;


	/**
	 * The path to the plugin file.
	 *
	 * /path/to/wp-content/plugins/{plugin-name}/{plugin-name}.php
	 *
	 * @author Konstantin Obenland
	 * @since  2.0.0 - 30.05.2012
	 * @access protected
	 *
	 * @var    string
	 */
	protected $plugin_path;


	/**
	 * The path to the plugin directory.
	 *
	 * /path/to/wp-content/plugins/{plugin-name}/
	 *
	 * @author Konstantin Obenland
	 * @since  1.2 - 21.04.2011
	 * @access protected
	 *
	 * @var    string
	 */
	protected $plugin_dir_path;

	/**
	 * Constructor
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 23.03.2011
	 * @access public
	 *
	 * @param  array $args {.
	 *      @type string $textdomain
	 *      @type string $plugin_name
	 *      @type string $plugin_path
	 *      @type string $donate_link_id
	 * }
	 */
	public function __construct( $args = array() ) {

		// Set class properties.
		$this->textdomain      = $args['textdomain'];
		$this->plugin_path     = $args['plugin_path'];
		$this->plugin_dir_path = plugin_dir_path( $args['plugin_path'] );
		$this->plugin_name     = plugin_basename( $args['plugin_path'] );

		load_plugin_textdomain( 'obenland-wp', false, $this->textdomain . '/lang' );

		$this->set_donate_link( $args['donate_link_id'] );
		$this->hook( 'plugins_loaded', 'parent_plugins_loaded' );
	}


	/**
	 * Hooks in all the hooks :)
	 *
	 * @author Konstantin Obenland
	 * @since  2.0.0 - 12.04.2012
	 * @access public
	 */
	public function parent_plugins_loaded() {
		$this->hook( 'plugin_row_meta' );

		if ( ! has_action( 'obenland_side_info_column' ) ) {
			$this->hook( 'obenland_side_info_column', 'donate_box', 1 );
			$this->hook( 'obenland_side_info_column', 'feed_box' );
		}
	}


	/**
	 * Adds a Donate link to our plugin row.
	 *
	 * @author Konstantin Obenland
	 * @since  1.0 - 23.03.2011
	 * @access public
	 *
	 * @param  array  $plugin_meta Existing plugin meta.
	 * @param  string $plugin_file Plugin slug.
	 *
	 * @return string
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_name === $plugin_file ) {
			$plugin_meta[] = sprintf(
				'<a href="%1$s" target="_blank" title="%2$s">%2$s</a>',
				$this->donate_link,
				__( 'Donate', 'obenland-wp' )
			);
		}
		return $plugin_meta;
	}


	/**
	 * Displays a box with a donate button and call to action links.
	 *
	 * Props Joost de Valk, as this is almost entirely from his awesome WordPress
	 * SEO Plugin.
	 *
	 * @see    http://plugins.svn.wordpress.org/wordpress-seo/tags/1.1.5/admin/class-config.php
	 *
	 * @author Joost de Valk, Konstantin Obenland
	 * @since  2.0.0 - 31.03.2012
	 * @access public
	 *
	 * @return void
	 */
	public function donate_box() {
		$plugin_data = get_plugin_data( $this->plugin_path );
		?>
		<div id="formatdiv" class="postbox">
			<h3 class="hndle"><span><?php esc_html_e( 'Help spread the word!', 'obenland-wp' ); ?></span></h3>
			<div class="inside">
				<p>
					<strong>
						<?php
						printf(
							/* translators: Plugin name. */
							esc_html_x( 'Want to help make this plugin even better? All donations are used to improve %1$s, so donate $20, $50 or $100 now!', 'Plugin Name', 'obenland-wp' ),
							esc_html( $plugin_data['Name'] )
						);
						?>
					</strong>
				</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="G65Y5CM3HVRNY">
					<input type="image" src="https://www.paypalobjects.com/<?php echo esc_attr( get_locale() ); ?>/i/btn/btn_donate_LG.gif" border="0" name="submit">
					<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">
				</form>
				<p><?php esc_html_e( 'Or you could:', 'obenland-wp' ); ?></p>
				<ul>
					<li><a href="http://wordpress.org/extend/plugins/wp-approve-user/"><?php esc_html_e( 'Rate the plugin 5&#9733; on WordPress.org', 'obenland-wp' ); ?></a></li>
					<li><a href="<?php echo esc_url( $plugin_data['PluginURI'] ); ?>"><?php esc_html_e( 'Blog about it &amp; link to the plugin page', 'obenland-wp' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}


	/**
	 * Displays a box with feed items and social media links.
	 *
	 * Props Joost de Valk, as this is almost entirely from his awesome WordPress
	 * SEO Plugin.
	 *
	 * @see    http://plugins.svn.wordpress.org/wordpress-seo/tags/1.1.5/admin/yst_plugin_tools.php
	 *
	 * @author Joost de Valk, Konstantin Obenland
	 * @since  2.0.0 - 31.03.2012
	 * @access public
	 *
	 * @return void
	 */
	public function feed_box() {

		include_once ABSPATH . WPINC . '/feed.php';
		$feed_url = 'http://en.wp.obenland.it/feed/';
		$rss      = fetch_feed( $feed_url );

		// Bail if feed doesn't work.
		if ( is_wp_error( $rss ) ) {
			return;
		}

		$rss_items = $rss->get_items( 0, $rss->get_item_quantity( 5 ) );

		// If the feed was erroneously.
		if ( ! $rss_items ) {
			$md5 = md5( $feed_url );
			delete_transient( 'feed_' . $md5 );
			delete_transient( 'feed_mod_' . $md5 );
			$rss       = fetch_feed( $feed_url );
			$rss_items = $rss->get_items( 0, $rss->get_item_quantity( 5 ) );
		}
		?>
		<div id="formatdiv" class="postbox">
			<h3 class="hndle"><span><?php esc_html_e( 'News from Konstantin', 'obenland-wp' ); ?></span></h3>
			<div class="inside">
				<ul>
					<?php if ( ! $rss_items ) : ?>
					<li><?php esc_html_e( 'No news items, feed might be broken...', 'obenland-wp' ); ?></li>
					<?php
					else :
						foreach ( $rss_items as $item ) :
							$url = preg_replace( '/#.*/', '#utm_source=WordPress&utm_medium=sidebannerpostbox&utm_term=rssitem&utm_campaign=' . $this->textdomain, $item->get_permalink() );
					?>
						<li><a class="rsswidget" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $item->get_title() ); ?></a></li>
						<?php
						endforeach;
					endif;
					?>
					<li class="twitter"><a href="http://twitter.com/obenland"><?php esc_html_e( 'Follow Konstantin on Twitter', 'obenland-wp' ); ?></a></li>
					<li class="rss"><a href="<?php echo esc_url( $feed_url ); ?>"><?php esc_html_e( 'Subscribe via RSS', 'obenland-wp' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}


	/**
	 * Hooks methods to their WordPress Actions and Filters.
	 *
	 * @example:
	 * $this->hook( 'the_title' );
	 * $this->hook( 'init', 5 );
	 * $this->hook( 'omg', 'is_really_tedious', 3 );
	 *
	 * @author Mark Jaquith
	 * @see    http://sliwww.slideshare.net/markjaquith/creating-and-maintaining-wordpress-plugins
	 * @since  1.5 - 12.02.2012
	 * @access protected
	 *
	 * @param  string $hook Action or Filter Hook name.
	 *
	 * @return boolean true
	 */
	protected function hook( $hook ) {
		$priority = 10;
		$method   = $this->sanitize_method( $hook );
		$args     = func_get_args();
		unset( $args[0] ); // Filter name.

		foreach ( (array) $args as $arg ) {
			if ( is_int( $arg ) ) {
				$priority = $arg;
			} else {
				$method = $arg;
			}
		}

		return add_action( $hook, array( $this, $method ), $priority, 999 );
	}


	/**
	 * Sets the donate link.
	 *
	 * @author Konstantin Obenland
	 * @since  1.1 - 03.04.2011
	 * @access protected
	 *
	 * @param  string $donate_link_id Donate link ID.
	 */
	protected function set_donate_link( $donate_link_id ) {
		$this->donate_link = add_query_arg( array(
			'cmd'              => '_s-xclick',
			'hosted_button_id' => $donate_link_id,
		), 'https://www.paypal.com/cgi-bin/webscr' );
	}


	/**
	 * Sanitizes method names.
	 *
	 * @author Mark Jaquith
	 * @see    http://sliwww.slideshare.net/markjaquith/creating-and-maintaining-wordpress-plugins
	 * @since  1.5 - 12.02.2012
	 * @access private
	 *
	 * @param  string $method Method name to be sanitized.
	 *
	 * @return string Sanitized method name
	 */
	private function sanitize_method( $method ) {
		return str_replace( array( '.', '-' ), '_', $method );
	}

} // End of class Obenland_Wp_Plugins.
